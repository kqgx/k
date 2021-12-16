<?php

class Module_Order_model extends CI_Model {
	
	public $item;
	public $prefix;
	private $cache_file;

    /**
     * 订单模型类
     */
    public function __construct() {
        parent::__construct();
		$this->prefix = $this->db->dbprefix(SITE_ID.'_order_');
        $this->cache_file = md5($this->duri->uri(1).$this->uid.SITE_ID.$this->input->ip_address().$this->input->user_agent());
    }

    // 提醒通知
    public function notice($id, $order, $buy = 0, $sell = 0) {

        $cfg = isset($this->ci->mconfig['notice'][$id]) ? $this->ci->mconfig['notice'][$id] : array();
        if (!$cfg) {
            return;
        }

        $data = array(
            'sn' => $order['sn'],
            'id' => $order['id'],
            'time' => dr_date(SYS_TIME),
            'phone' => $order['shipping_phone'],
            'price' => number_format($order['order_price'], 2),
            'score' => intval($order['order_score']),
        );

        // 发送短信提醒
        if (isset($cfg['phone']) && $cfg['phone'] && $order['shipping_phone']) {
            $tpl = $cfg['phone'];
            // 兼容php5.5
            if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
                $rep = new php5replace($data);
                $content = preg_replace_callback('#{(.*)}#U', array($rep, 'php55_replace_data'), $tpl);
                $content = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $content);
                unset($rep);
            } else {
                @extract($data);
                $content = preg_replace("/{(.*)}/Ue", "\$\\1", $tpl);
                $content = preg_replace('#{([a-z_0-9]+)\((.*)\)}#Uie', "\\1(safe_replace('\\2'))", $content);
            }
            // 通知买家
            if ($buy) {
                $this->member_model->sendsms($order['shipping_phone'], $content);
            }
            // 通知商家
            if ($sell) {
                $m = dr_member_info($sell);
                if ($m['phone']) {
                    $this->member_model->sendsms($m['phone'], $content);
                }
            }
        }

        // 发送邮件提现
        if (isset($cfg['email']) && $cfg['email']) {
            $tpl = $cfg['email'];
            // 兼容php5.5
            if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
                $rep = new php5replace($data);
                $content = preg_replace_callback('#{(.*)}#U', array($rep, 'php55_replace_data'), $tpl);
                $content = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $content);
                unset($rep);
            } else {
                @extract($data);
                $content = preg_replace("/{(.*)}/Ue", "\$\\1", $tpl);
                $content = preg_replace('#{([a-z_0-9]+)\((.*)\)}#Uie', "\\1(safe_replace('\\2'))", $content);
            }
            // 通知买家
            if ($buy && $m = dr_member_info($buy)) {
                $this->ci->sendmail_queue($m['email'], L('订单%s提醒', $order['sn']), nl2br($content));
            }
            // 通知商家
            if ($sell && $m = dr_member_info($sell)) {
                $this->ci->sendmail_queue($m['email'], L('订单%s提醒', $order['sn']), nl2br($content));
            }
        }

    }

    // 交易完成
    public function wc($order) {

        if ($order['order_price'] > 0 || $order['order_score'] >0) {
            if ($order['order_score']) {
                // 将虚拟币打给卖家
                $this->member_model->update_score(1, $order['sell_uid'], intval($order['order_score']), '', L('商品销售收入，订单号：%s', '<a href="'.SITE_URL.'index.php?s=member&mod=order&c=home&m=info&id='.$order['id'].'" target="_blank">'.$order['sn'].'</a>'));
            } else {
                // 将钱打给卖家
                $this->load->model('pay_model');
                $this->pay_model->add(
                    $order['sell_uid'],
                    $order['order_price'],
                    L('商品销售收入，订单号：%s', '<a href="'.SITE_URL.'index.php?s=member&mod=order&c=home&m=info&id='.$order['id'].'" target="_blank">'.$order['sn'].'</a>')
                );
            }
        }

        // 入库商品索引表
        $goods = $this->db->where('oid', $order['id'])->get(SITE_ID.'_order_goods')->result_array();
        foreach ($goods as $t) {
            // 商品购买记录信息入库
            $this->db->insert(SITE_ID.'_order_buy', array(
                'mid' => $t['mid'],
                'cid' => $t['cid'],
                'gid' => $t['id'],
                'oid' => $order['id'],
                'uid' => $order['buy_uid'],
                'catid' => $t['catid'],
                'comment' => 0,
                'quantity' => $t['quantity'],
                'specification' => $t['specification'],
            ));
            // 更新商品销售量
            if (isset($this->mconfig['module'][$t['mid']]['volume']) && $this->mconfig['module'][$t['mid']]['volume']
                && empty($this->mconfig['module'][$t['mid']]['k_volume'])) {
                $this->db->where('id', (int)$t['cid'])->set('order_volume', 'order_volume+'.intval($t['quantity']), FALSE)->update(SITE_ID.'_'.$t['mid']);
                $this->ci->clear_cache('show'.$t['mid'].SITE_ID.$t['cid']);
                $this->hooks->call_hook('order_volume', array('good' => $t, 'order' => $order)); // 订单更新销量时的挂钩点
            }
        }

        // 交易完成通知
        $this->notice(3, $order, $order['buy_uid'], $order['sell_uid']);

    }

    // 支付成功，付款扣钱
    public function pay($orders, $price = 0, $pay_id = 0) {

        if (!$orders) {
            return;
        }

        foreach ($orders as $order) {
            if ($order['order_price'] > 0 || $order['order_score'] > 0) {
                if ($order['order_score']) {
                    // 虚拟币支付时
                    $pay_id = 0;
                    $this->member_model->update_score(1, $order['buy_uid'], -intval($order['order_price']), '', L('购物消费，订单号：%s', '<a href="'.SITE_URL.'index.php?s=member&mod=order&c=home&m=info&id='.$order['id'].'" target="_blank">'.$order['sn'].'</a>'));
                } else {
                    // 扣钱
                    $this->load->model('pay_model');
                    $pay_id = $this->pay_model->add_for_buy(
                        $order['order_price'],
                        array('id' => $order['id'], 'sn' => $order['sn'], 'list' => ''),
                        'order'
                    );
                }
            }
            // 支付成功，更新商品销售量
            if (isset($this->mconfig['module'][$order['mid']]['volume']) && $this->mconfig['module'][$order['mid']]['volume']
                && $this->mconfig['module'][$order['mid']]['k_volume']) {
                // 查询此订单的商品
                $goods = $this->db->where('oid', (int)$order['id'])->get(SITE_ID.'_order_goods')->result_array();
                foreach ($goods as $t) {
                    $this->db->where('id', (int)$t['cid'])->set('order_volume', 'order_volume+'.intval($t['quantity']), FALSE)->update(SITE_ID.'_'.$t['mid']);
                    $this->ci->clear_cache('show'.$t['mid'].SITE_ID.$t['cid']);
                    $this->hooks->call_hook('order_volume', array('good' => $t, 'order' => $order)); // 订单更新销量时的挂钩点
                }
            }
            // 订单状态判断
            $status = 2; // 配货
            switch ($order['buy_step']) {
                case 0:
                    # 付款-发货-收货-完成
                    $status = 2; // 发货状态
                    break;
                case 1:
                    # 付款-发货-完成
                    $status = 4; // 待收货状态
                    break;
                case 2:
                    # 付款-完成 （不要物流）
                    $status = 3;
                    break;
                case 3:
                    # 付款-收货-完成 （不要物流）
                    $status = 4; // 待收货状态
                    break;
            }
            // 改变支付状态
            $order['pay_id'] = $pay_id;
            $order['pay_time'] = SYS_TIME;
            $order['pay_status'] = 3;
            $order['order_status'] = $status;
            $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
                'pay_id' => $order['pay_id'],
                'pay_time' => $order['pay_time'],
                'pay_status' => $order['pay_status'], // 支付成功
                'order_status' => $order['order_status'], //订单状态
            ));
            // 付款日志
            $this->log($order, L('买家已付款'));
            // 买家付款之后，提醒卖家
            $this->notice(2, $order, 0, $order['sell_uid']);
            // 付款之后更新商品库存
            if (isset($this->mconfig['module'][$order['mid']]['quantity'])
                && $this->mconfig['module'][$order['mid']]['quantity']) {
                $goods = $this->db->where('oid', $order['id'])->get(SITE_ID.'_order_goods')->result_array();
                foreach ($goods as $t) {
                    $row = $this->db->where('id', $t['cid'])->get(SITE_ID.'_'.$t['mid'])->row_array();
                    if ($row) {
                        $update = array();
                        if ($t['specification']) {
                            // sku 更新
                            $spec = string2array($row['order_specification']);
                            $nums = isset($spec['value'][$t['specification']]['quantity']) ? intval($spec['value'][$t['specification']]['quantity']) : 0;
                            $spec['value'][$t['specification']]['quantity'] = max(0, $nums - $t['quantity']);
                            $update['order_specification'] = array2string($spec);
                        }
                        $update['order_quantity'] = max(0, $row['order_quantity'] - $t['quantity']);
                        $this->db->where('id', $t['cid'])->update(SITE_ID.'_'.$t['mid'], $update);
                        $this->ci->clear_cache('show'.$t['mid'].SITE_ID.$t['cid']);
                    }
                }

            }

            if ($status == 3) {
                // 完成交易
                $this->wc($order);
            } elseif ($status == 4) {
                // 发货
                $this->shopping($order, array(
                    'sn' => '',
                    'type' => '',
                ));
            }
        }
    }

    // 发货处理
    public function shopping($order, $data) {

        if (!$order) {
            return;
        }

        // 改变订单状态
        $order['order_status'] = 4;
        $order['shipping_time'] = SYS_TIME;
        $order['shipping_status'] = 3;
        $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
            'order_status' => $order['order_status'], //订单状态
            'shipping_time' => $order['shipping_time'],
            'shipping_status' => $order['shipping_status'],
        ));

        // 物流信息录入
        $order['shipping_sn'] = $data['sn'];
        $order['shipping_type'] = $data['type'];
        $this->db->where('id', $order['id'])->update(SITE_ID.'_order_data_'.$order['tableid'], array(
            'shipping_sn' => $order['shipping_sn'],
            'shipping_type' => $order['shipping_type'],
        ));

        // 记录日志
        $this->log($order, L('卖家已发货，承运：%s，运货单号：%s', $data['name'], $data['sn']));
        // 发货之后提醒买家
        $this->notice(5, $order, $order['buy_uid']);
    }

    // 确认收货处理
    public function shouhuo($order) {

        if (!$order) {
            return;
        }

        // 改变订单状态
        $order['order_status'] = 3;
        $order['shipping_stime'] = SYS_TIME;
        $order['shipping_status'] = 5;

        $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
            'order_status' => $order['order_status'], //订单状态
            'shipping_stime' => $order['shipping_stime'],
            'shipping_status' => $order['shipping_status'],
        ));

        // 记录日志
        $this->log($order, L('买家已收货'));

        if ($order['pay_type'] == 1) {
            // 货到付款时
            $pay_id = 0;
            if ($order['order_price'] > 0) {
                $this->load->model('pay_model');
                $pay_id = $this->pay_model->add_for_buy(
                    $order['order_price'],
                    array('id' => $order['id'], 'sn' => $order['sn'], 'list' => ''),
                    'order'
                );
            }
            // 改变支付状态
            $order['pay_id'] = $pay_id;
            $order['pay_time'] = SYS_TIME;
            $order['pay_status'] = 3;
            $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
                'pay_id' => $order['pay_id'],
                'pay_time' => $order['pay_time'],
                'pay_status' => $order['pay_status'], // 支付成功
            ));
        }

        // 收货之后交易完成
        $this->wc($order);
    }

    // 确认到账处理
    public function daozhang($order) {

        if (!$order) {
            return;
        }

        // 处理付款方法，网银转账后，付款金额为0，直接付款
        $this->pay(array($order), 0, 0);
    }

    // 关闭处理
    public function close($order, $body) {

        if (!$order) {
            return;
        }

        // 改变订单状态
        $order['order_status'] = 9;
        $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
            'order_status' => $order['order_status'], //订单状态
        ));

        // 记录日志
        $this->log($order, $body);
    }

    // 记录订单日志
    public function log($order, $note) {

        $this->db->insert($this->prefix.'operate', array(
            'oid' => $order['id'],
            'uid' => $this->uid,
            'username' => $this->member['username'],
            'order_status' => $order['order_status'],
            'pay_status' => $order['pay_status'],
            'shipping_status' => $order['shipping_status'],
            'note' => $note,
            'inputtime' => SYS_TIME,
            'inputip' => $this->input->ip_address(),
        ));
    }
	
	/**
	 * 获取商品的物流信息
	 */
	public function get_shipping_info($shipping, $city) {
		
		$free = string2array($shipping['free']);
        $freight = string2array($shipping['freight']);
        unset($shipping['free'], $shipping['freight']);

        $info = dr_linkage('address', $city);
        if (!$info) {
            return;
        }

        $city = explode(',', $info['pids'].','.$info['ii']);
        if (!$city[0]) {
            unset($city[0]);
        }

        // 包邮策略
        $value = $default = array();
        if ($free) {
            foreach ($free as $t) {
                if ($t['city']) {
                    if (array_intersect($t['city'], $city)) {
                        $value = $t;
                        break;
                    }
                } else {
                    $default = $t;
                }
            }
            $free = $value ? $value : $default;
            if ($free) {
                $free['value'] = $free[$free['type']];
                unset($free[1], $free[2], $free[3]);
            }
        }

        // 按地区获取运费信息
        $value = array();
        $default = $freight['default'];
        unset($freight['default']);
        if ($freight) {
            foreach ($freight as $t) {
                if ($t['city']) {
                    if (array_intersect($t['city'], $city)) {
                        $value = $t;
                        break;
                    }
                } else {
                    $default = $t;
                }
            }
        }
        $freight = $value ? $value : $default;
		
		return array($free, $freight);
	}

	/**
	 * 计算运费和总价
     * @param   城市id
     * @param   当前商品数据
     * @goods   总价和订单的价
	 */
    public function get_price($city, $goods) {

		$data = $shipping = array();
		$price = $score = 0;
		foreach ($goods as $u => $store) {
			$data[$u] = array(
                'price' => 0,
                'score' => 0,
				'yunfei' => 0,
				'youhui' => 0,
                '_score' => 0,
                '_price' => 0,
				'_yunfei' => 0,
                '_youhui' => 0,
			);
			foreach ($store['goods'] as $t) {
                $youhui = $yunfei = 0;
                if ($t['order_score']) {
                    $t['score'] = $t['price'];
                    $t['price'] = 0;
                }
				if ($t['shipping'] && $city > 0) {
					$sp = (int)$t['shipping'];
					if (!isset($shipping[$sp]) || !$shipping[$sp]) {
						$shipping[$sp] = $this->db->where('id', $sp)->where('uid', $store['uid'])->get(SITE_ID.'_'.$t['mid'].'_shipping')->row_array();
                        if (!$shipping[$sp]) {
							unset($shipping[$sp]); // 运费模板不存在时默认为包邮
						}
					}

					if (isset($shipping[$sp]) && $shipping[$sp]) {
						list($free, $freight) = $this->get_shipping_info($shipping[$sp], $city);
						switch ($shipping[$sp]['valuation']) {
							case 1: // 按数量
								// 首数量内容的价格
								$yunfei+= $freight['postage'];
								// 购买数量超过规定是首数量时计算续费价格
								if ($t['quantity'] > $freight['start']) {
									$yunfei+= ceil(($t['quantity'] - $freight['start']) / $freight['plus']);
								}
								if ($free) {
									switch ($free['type']) {
										case 1:
											// 满x件包邮
											if ($t['quantity'] >= $free['value']) {
												$yunfei = 0;
											}
											break;
										case 2:
											// 满x元包邮
											if ($t['price'] - $free['value'] >= 0) {
												$yunfei = 0;
											}
											break;
										case 3:
											// 满x件， 满x元包邮
											if ($t['quantity'] >= $free['value']['dw'] && $t['price'] - $free['value']['price'] >= 0) {
												$yunfei = 0;
											}
											break;
									}
								}
								break;
							case 2: // 按重量
								// 首重量内的价格
								$yunfei+= $freight['postage'];
								if (isset($t['shipping_param']['kg']) && $t['shipping_param']['kg']) {
									// 购买重量超过规定是首重量时计算续费价格
									$v = $t['quantity'] * $t['shipping_param']['kg']; // 总重量
									if ($v > $freight['start']) {
										$yunfei+= ceil(($v - $freight['start']) / $freight['plus']);
									}
									if ($free) {
										switch ($free['type']) {
											case 1:
												// 在xkg内包邮
												if ($v >= $free['value']) {
													$yunfei = 0;
												}
												break;
											case 2:
												// 满x元包邮
												if ($t['price'] - $free['value'] >= 0) {
													$yunfei = 0;
												}
												break;
											case 3:
												// 在xkg内， 满x元包邮
												if ($v >= $free['value']['dw'] && $t['price'] - $free['value']['price'] >= 0) {
													$yunfei = 0;
												}
												break;
										}
									}
								}
								break;
							case 3: // 按体积
								// 首体积内的价格
								$yunfei+= $freight['postage'];
								if (isset($t['shipping_param']['m3']) && $t['shipping_param']['m3']) {
									// 购买体积超过规定是首体积时计算续费价格
									$v = $t['quantity'] * $t['shipping_param']['m3']; // 总重量
									if ($v > $freight['start']) {
										$yunfei+= ceil(($v - $freight['start']) / $freight['plus']);
									}
									if ($free) {
										switch ($free['type']) {
											case 1:
												// 在x m³内包邮
												if ($v >= $free['value']) {
													$yunfei = 0;
												}
												break;
											case 2:
												// 满x 元包邮
												if ($t['price'] - $free['value'] >= 0) {
													$yunfei = 0;
												}
												break;
											case 3:
												// 在x m³内， 满x元包邮
												if ($v >= $free['value']['dw'] && $t['price'] - $free['value']['price'] >= 0) {
													$yunfei = 0;
												}
												break;
										}
									}
								}
								break;
						}
					}
				}
				// 计算单品的价格和运费
				$data[$u]['_score']+= $t['score']; // 统计虚拟币
				$data[$u]['_youhui']+= $youhui; // 统计优惠价
				$data[$u]['_yunfei']+= $yunfei; // 统计运费
				$data[$u]['_price']+= $t['price'] + $yunfei - $youhui; // 统计含运费的价格
			}
			// 格式化商铺的价格值
            $price+= $data[$u]['_price'];
            $score+= $data[$u]['_score'];
			$data[$u]['yunfei'] = number_format($data[$u]['_yunfei'], 2);
			$data[$u]['price'] = number_format($data[$u]['_price'], 2);
			$data[$u]['youhui'] = number_format($data[$u]['_youhui'], 2);
			$data[$u]['score'] = intval($data[$u]['_score']);
		}

		return array($score, $price, $data);
	}

	/**
	 * 检测商品数据
     * @param   当前参数
     * @goods   当前商品数据
	 */
    public function check_goods($param, $goods) {

        // 库存验证
        if ($this->ci->mconfig['module'][$param['mid']]['quantity']) {
            if ( $goods['order_quantity'] < $param['quantity']) {
                return L('库存不足', $param['cid']);
            }
        }

        return 0;
    }

	/**
	 * 获取商品数据
     * @return  返回字符串时就是错误信息
	 */
	public function get_goods($param) {

        if (!$param['cid'] || !$param['mid']) {
            return L('缺少关键参数cid和mid'); // 参数不存在
        } elseif (!isset($this->ci->mconfig['module'][$param['mid']])
            || !$this->ci->mconfig['module'][$param['mid']]['use']) {
            return L('模块【%s】没有开启电商功能', $param['mid']); // 未开启电商功能
        }

        // 查询商品
        $data = $this->db->where('id', $param['cid'])->get(SITE_ID.'_'.$param['mid'])->row_array();
        if (!$data) {
            return L('商品不存在(#%s)', $param['cid']); // 商品不存在
        } elseif (isset($data['order_status']) && !$data['order_status']) {
            return L('商品(#%s)已经下架', $param['cid']); // 商品下架
        }
        $data['thumb'] = $this->_get_thumb($data['thumb']);
        // 商品价格、库存计算
        if ($data['order_specification']) {
            $data['order_specification'] = string2array($data['order_specification']);
            $data['order_price'] = (float)$data['order_specification']['value'][$param['specification']]['price'];
            $data['order_quantity'] = (int)$data['order_specification']['value'][$param['specification']]['quantity'];
        }
        // 检测商品数据
        $error = $this->check_goods($param, $data);
        if ($error) {
            return $error;
        }

        return $data;
    }

	/**
	 * 购物车的商品数量
	 */
	public function get_cart_nums($uid, $agent = '') {

	    if (!$uid) {
	        return 0;
        }

		$this->db->where('uid', $uid);
		$data = $this->db->select_sum('quantity')->get($this->prefix.'cart')->row_array();

		return (int)$data['quantity'];
	}
	
	
	/**
	 * 添加到购物车
	 */
	public function add_cart($data) {

        if (!$data['uid'] && $data['agent']) {
            $this->db->where('agent', $data['agent']);
        }
		$cart = $this->db
					 ->where('uid', $data['uid'])
					 ->where('mid', $data['mid'])
					 ->where('cid', $data['cid'])
					 ->where('specification', $data['specification'])
					 ->get($this->prefix.'cart')
					 ->row_array();
		if ($cart) {
			$this->db->where('id', $cart['id'])->update($this->prefix.'cart', array(
				'agent' => $data['agent'],
				'quantity' => $cart['quantity'] + $data['quantity'],
				'inputtime' => SYS_TIME,
			));
		} else {
			$data['inputtime'] = SYS_TIME;
			$this->db->replace($this->prefix.'cart', $data);
		}
		
		return $this->get_cart_nums($data['uid'], $data['agent']);
	}
	
	/**
	 * 获取购物车单条记录
	 */
	public function get_cart_row($uid, $id) {
		
		return $this->db->where('id', $id)->get($this->prefix.'cart')->row_array();
	}
	
	/**
	 * 获取购买商品详情
	 */
	public function get_goods_info($data, $buy = 0) {

        // 统计订单
        $total = array(
            'num' => 0,
            'price' => 0,
            'score' => 0,
        );
        $oids = ''; // 购买参数id组装
        $this->item = $list = array();
        /*
        if (is_dir(FCPATH.'module/mall/')) {
            $this->load->add_package_path(FCPATH.'module/mall/');
            $this->load->model('spec_model');
        }*/
        // 按卖家分类
        foreach ($data as $t) {
            $mark = $t['mid'].'-'.$t['cid'];
            if (!isset($this->item[$mark])) {
                $row = $this->db->where('id', $t['cid'])->get(SITE_ID.'_'.$t['mid'])->row_array();
                // 商品本身状态验证
                $check = isset($row['order_status']) ? $row['order_status'] : 1;
                if (isset($row['order_stime'])
                    && $row['order_stime']
                    && $row['order_stime'] > SYS_TIME) {
                    // 验证商品的开始有效期，开始时间大于当前时间，表示此商品还没开始销售
                    $check = 0; // 验证不通过
                } elseif (isset($row['order_etime'])
                    && $row['order_etime']
                    && $row['order_etime'] <= SYS_TIME) {
                    // 验证商品的结束有效期，结束时间小于当前时间，表示此商品销售时间到了
                    $check = 0; // 验证不通过
                }
                if ($row && $check){
                    $row['sku'] = array();
                    $row['thumb'] = $this->_get_thumb($row['thumb']);
                    $row['order_buy_step'] = (int)$this->ci->mconfig['module'][$t['mid']]['buy_step']; // 订单交易步骤;
                    $row['order_specification'] = $row['order_specification'] ? string2array($row['order_specification']) : '';
                    if ($row['order_specification']
                        && is_file(FCPATH.'module/mall/models/Spec_model.php')) {
                        $row['sku'] = dr_mall_spec($row['catid'], $row['order_specification']);
                    }
                    $row['order_score'] = (int)$this->ci->mconfig['module'][$t['mid']]['pay']; // 判断是否是虚拟币支付
                    $this->item[$mark] = $row;
                } else {
                    // 删除无效商品
                    $this->db->where('id', $t['id'])->delete($this->prefix.'cart');
                    continue;
                }
            }
            if (!$this->item[$mark]) {
                // 删除无效商品
                $this->db->where('id', $t['id'])->delete($this->prefix.'cart');
                continue;
            }
            $t['url'] = $this->item[$mark]['url'];
            $t['title'] = $this->item[$mark]['title'];
            $t['catid'] = $this->item[$mark]['catid'];
            $t['thumb'] = $this->item[$mark]['thumb'];
            $t['buy_step'] = $this->item[$mark]['order_buy_step'];
            $t['shipping'] = $this->item[$mark]['order_shipping'] ? (int)$this->item[$mark]['order_shipping'] : 0;
            $t['specification'] = $t['specification'] ? $t['specification'] : 0;
            $t['shipping_param'] = $this->item[$mark]['order_shipping_param'] ? string2array($this->item[$mark]['order_shipping_param']) : 0;
            $t['sn'] = $t['order_sn'] ? $t['order_sn'] : '';
            $t['sku'] = array();
            // 商品价格、库存计算
            if ($this->item[$mark]['order_specification']) {
                $this->item[$mark]['order_price'] = (float)$this->item[$mark]['order_specification']['value'][$t['specification']]['price'];
                $this->item[$mark]['order_quantity'] = (int)$this->item[$mark]['order_specification']['value'][$t['specification']]['quantity'];
                $t['sn'] = $this->item[$mark]['order_specification']['value'][$t['specification']]['sn'];
                $my_id = 0;
                $my_value = @explode('_', $t['specification']);
                foreach ($this->item[$mark]['sku'] as $sv) {
                    $t['sku'][$sv['name']] = isset($sv['value'][$my_value[$my_id]]) ? $sv['value'][$my_value[$my_id]] : '';
                    $my_id ++;
                }
            }
            $t['order_score'] = (int)$this->ci->mconfig['module'][$t['mid']]['pay']; // 判断是否是虚拟币支付
            $t['order_price'] = $this->item[$mark]['order_price'];
            $t['order_quantity'] = $this->item[$mark]['order_quantity'];
            $t['quantity'] = max(1, $t['quantity']);
            $t['price'] =  $t['order_price'] * $t['quantity'];
            // 检测商品数据
            $error = $this->check_goods($t, $this->item[$mark]);
            $t['use'] = $error ? 0 : 1; // 是否有货
            if ($t['use']) {
                // 计算总价
                $total['num'] += $t['quantity'];
                if ($t['order_score']) {
                    $total['score'] += $t['price']; // 按虚拟币
                } else {
                    $total['price'] += $t['price']; // 按价格
                }
            } elseif ($buy) {
                continue;
            }
            // 归类卖家
            $cate = $t['mid'].'_'.$this->item[$mark]['uid'].'_'.$this->item[$mark]['catid'];
            if (!isset($list[$cate]['name'])) {
                // 卖家信息
                $list[$cate]['mid'] = $t['mid'];
				$list[$cate]['uid'] = $this->item[$mark]['uid'];
                $list[$cate]['price'] = 0;
                $list[$cate]['order_score'] = $t['order_score']; // 判断是否是虚拟币支付
                $list[$cate]['url'] = dr_space_url($list[$cate]['uid']);
                $list[$cate]['username'] = $list[$cate]['name'] = $this->item[$mark]['author'];
				$list[$cate]['buy_step'] = $this->item[$mark]['order_buy_step']; // 订单购买流程
                // 表示是商家店铺
                if (function_exists('dr_store_info') && $store = dr_store_info($this->item[$mark]['uid'])) {
                    $list[$cate]['url'] = $store['url'];
                    $list[$cate]['username'] = $list[$cate]['name'] = $store['title'];
                }
            }
            $list[$cate]['price'] += $t['price'];
            $list[$cate]['goods'][] = $t; // 商品列表
            $oids.= '-'.(int)$t['id'];
        }
        //echo '<pre>';print_r($list);

        $url = SITE_URL.'index.php?s='.APP_DIR.'&oid='.trim($oids, '-');

        return array($list, $total, $url);

    }
	/**
	 * 获取购物车
	 */
	public function get_cart($uid, $agent) {
		
		$this->db->where('uid', $uid);
		if (!$uid) {
			$this->db->where('agent', $agent);
		}
		$data = $this->db->order_by('inputtime desc')->get($this->prefix.'cart')->result_array();
		if (!$data) {
			return NULL;
		}

		return $this->get_goods_info($data);
	}

    // 订单主表信息
    public function get_info_row($id) {

        $data = $this->db->where('id', $id)->get(SITE_ID.'_order')->row_array();
        if (!$data) {
            return 0; // 订单不存在
        }

        return $data;
    }
	
	/**
	 * 订单详情
	 */
	public function get_info($id) {
		
		$data = $this->db->where('id', $id)->get(SITE_ID.'_order')->row_array();
		if (!$data) {
			return 0; // 订单不存在
		}

        $temp = $this->db->where('id', $id)->get($this->prefix.'data_'.intval($data['tableid']))->row_array();
        if ($temp) {
            $data+= $temp;
        }

		$data['goods'] = $this->db->where('oid', $id)->get($this->prefix.'goods')->result_array();

		// 付款详情
		$data['pay'] = $data['pay_id'] ? $this->db->where('id', $data['pay_id'])->get('member_paylog')->row_array() : 0;

        // 判断过期订单
        if ($this->clear($data)) {
            $data['order_status'] = 0;
        }

		return $data;
	}
	
    /**
     * 条件查询
     *
     * @param	object	$select	查询对象
     * @param	array	$param	条件参数
     * @return	array
     */
    private function _where(&$select, $status, $transfer, $param) {

        // 存在POST提交时，重新生成缓存文件
        if (IS_POST) {
            $data = $this->input->post('data');
            foreach ($data as $i => $t) {
                if ($t == '') {
                    unset($data[$i]);
                }
            }
            unset($_GET['page']);
        } else {
			$data = $param;
		}

        // 状态查询
        if ($status) {
            $select->where('order_status', $status);
        }

        // 存在条件时做筛选
        if ($data) {
			if (isset($data['keyword']) && $data['keyword'] != '' && $data['field']) {
                $field = $this->field;
                if ($data['field'] == 'id') {
                    // 按id查询
                    $id = array();
                    $ids = explode(',', $data['keyword']);
                    foreach ($ids as $i) {
                        $id[] = (int)$i;
                    }
                    $select->where_in('id', $id);
                } elseif ($data['field'] == 'sn') {
                    $select->where('sn', $data['keyword']);
                } elseif ($field[$data['field']]['fieldtype'] == 'Linkage'
                    && $field[$data['field']]['setting']['option']['linkage']) {
                    // 联动菜单搜索
                    if (is_numeric($data['keyword'])) {
                        // 联动菜单id查询
                        $link = dr_linkage($field[$data['field']]['setting']['option']['linkage'], (int)$data['keyword'], 0, 'childids');
                        if ($link) {
                            $select->where($data['field'] . ' IN (' . $link . ')');
                        }
                    } else {
                        // 联动菜单名称查询
                        $id = (int)$this->ci->get_cache('linkid-' . SITE_ID, $field[$data['field']]['setting']['option']['linkage']);
                        if ($id) {
                            $select->where($data['field'] . ' IN (select id from `' . $select->dbprefix('linkage_data_' . $id) . '` where `name` like "%' . $data['keyword'] . '%")');
                        }
                    }
                } elseif ($data['field'] == 'cid') {
                    $this->db->where('`id` IN (select oid from `'.$this->db->dbprefix(SITE_ID.'_order_goods').'` where `cid` = '.(int)$data['keyword'].')');
                } elseif ($data['field'] == 'title') {
                    $this->db->where('`id` IN (select oid from `'.$this->db->dbprefix(SITE_ID.'_order_goods').'` where `title` LIKE "%'.$data['keyword'].'%")');
                } else {
                    $select->like($data['field'], urldecode($data['keyword']));
                }
            }
			// 时间搜索
            if (isset($data['start']) && $data['start']) {
                $data['end'] = strtotime(date('Y-m-d 23:59:59', $data['end'] ? $data['end'] : SYS_TIME));
                $data['start'] = strtotime(date('Y-m-d 00:00:00', $data['start']));
                $select->where('order_time BETWEEN '.$data['start'].' AND '.$data['end']);
            } elseif (isset($data['end']) && $data['end']) {
                $data['end'] = strtotime(date('Y-m-d 23:59:59', $data['end']));
                $data['start'] = 0;
                $select->where('order_time BETWEEN '.$data['start'].' AND '.$data['end']);
            }
            // 模块搜索
            if (isset($data['mid']) && $data['mid']) {
                $select->where('mid', $data['mid']);
            }
            // 转账的订单筛选
            if ($transfer) {
                $select->where('pay_type', 3);
            }
        }

        return $data;
    }

    /**
     * 数据分页显示
     *
     * @return	array
     */
    public function limit_page($param, $status, $transfer = 0) {
		
		$page = max((int)$_GET['page'], 1);
		$total = (int)$_GET['total'];
		
        if (!$total || IS_POST) {
            $select	= $this->db->select('count(*) as total');
            $_param = $this->_where($select, $status, $transfer, $param);
            $data = $select->get(SITE_ID.'_order')->row_array();
            unset($select);
            $total = $_param['total'] = (int)$data['total'];
            if (!$_param['total']) {
                $_param['total'] = 0;
                return array(array(), $_param);
            }
        }

        $_order = isset($_GET['order']) && strpos($_GET['order'], "undefined") !== 0 ? $this->input->get('order') : 'order_time DESC';
		
        $select	= $this->db->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1));
        $_param = $this->_where($select, $status, $transfer, $param);
        $data = $select->order_by($_order)->get(SITE_ID.'_order')->result_array();
		
        $_param['order'] = $_order;
        $_param['total'] = (int)$total;

        return array($data, $_param);
    }

    // 处理失效订单
    public function clear($order) {

        $otime = (int)$this->mconfig['config']['otime'] * 3600;
        if (!$otime) {
            return 0;
        }

        if ($order['order_status'] == 1 && SYS_TIME - $order['order_time'] > $otime) {
            // 改变订单状态
            $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
                'order_status' => 0, //订单状态
            ));
            return 1;
        }

        return 0;
    }

    //
    private function _get_thumb($thumb) {

        $data = string2array($thumb);
        // 判断是否是数组格式的图片
        if ($data && is_array($data)) {
            return isset($data[0]) ? $data[0] : '';
        }

        return $thumb;
    }

    /**
     * 完成订单
     */
    public function complete_model($id)
    {
        $data = $this->db->where('id', $id)->get('1_order')->row_array();
        if (empty($data)) {
            return -1;
        } else {
            // 更新订单状态
            $this->db->where('id', $id)->update('1_order', ['order_status' => 3]);
            //订单分销金额
            $darr = $this->db->select('dis_price, quantity')
                             ->from('1_order_goods as g')
                             ->join('1_mall as m', 'g.cid=m.id', 'inner')
                             ->where('g.oid', $id)
                             ->get()->result_array();
            $dis_price = 0;
            foreach ($darr as $v) {
                $dis_price = $dis_price + ($v['dis_price'] * $v['quantity']);
            }
            // 统计薪酬
            $invitation_code = dr_member_info($data['buy_uid'])['invitation_code'];
            $first = $this->db->select('uid, invitation_code')->where('randcode', $invitation_code)->get('member')->row_array();
            if (!empty($first)) {
                $this->models('system/pay')->add($first['uid'], ($dis_price * (dr_block('first')/100)), L('完成订单'));
                $second = $this->db->select('uid, invitation_code')->where('randcode', $first['invitation_code'])->get('member')->row_array();
                if (!empty($second)) {
                    $this->models('system/pay')->add($second['uid'], ($dis_price * (dr_block('second')/100)), L('完成订单'));
                }
            }
            $data['order_status'] = 3;
            $this->log($data, L('完成订单'));
            return 1;
        }
    }
}