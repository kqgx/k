<?php

/* v3.1.0  */
	
class Pay extends M_Controller {
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

	// 支付成功后的跳转
	public function call() {

        if (!$this->uid) {
            $this->member_msg(L('付款成功'), '', 1);
        }

		$url = SITE_URL.'index.php?s=member&c=pay';
		$module = $this->input->get('module');
		
		// 订单模块跳转到买家中心
		$module == 'order' && $url = SITE_URL.'index.php?s=member&mod=order&c=home&m=index';

		redirect($url, 'refresh');
	}
	
	/**
     * 在线充值付款跳转
     */
	public function go() {
		if ($data = $this->models('system/pay')->pay_for_online((int)$this->input->get('id'))) {
            if (!isset($data['error'])) {
                if (isset($data['form']) && $data['form']) {
                    $this->member_msg(L('正在为您跳转到支付页面，请稍后...').'<div style="display:none">'.$data['form'].'</div>', 'javascript:;', 2, 0);
                } elseif (isset($data['url']) && $data['url']) {
                    $this->member_msg(L('正在为您跳转到支付页面，请稍后...'), $data['url'], 2, 0);
                } else {
                    $this->template->assign(array(
                        'pay' => $data,
                    ));
                    $this->template->display('pay_result.html');
                    exit;
                }
            } else {
                $this->member_msg($data['error']);
            }
		} else {
			$this->member_msg(L('充值失败，未知错误'));
		}
	}
	
	/**
     * 在线充值
     */
    public function add() {
	
		$money = (double)$this->input->get('money');
		
		if (IS_POST) {
			
			$pay = $this->input->post('pay');
			$money = (double)$this->input->post('money');
			
			if (!$money > 0) {
				$error = L('请输入一个有效的充值金额');
			} elseif (!$pay) {
				$error = L('请选择一种支付方式');
			} else {
				if ($data = $this->models('system/pay')->add_for_online($pay, $money)) {
                    if (!isset($data['error'])) {
                        if (isset($data['form']) && $data['form']) {
                            $this->member_msg(L('正在为您跳转到支付页面，请稍后...').'<div style="display:none">'.$data['form'].'</div>', 'javascript:;', 2, 0);
                        } elseif (isset($data['url']) && $data['url']) {
                            $this->member_msg(L('正在为您跳转到支付页面，请稍后...'), $data['url'], 2, 0);
                        } else {
							(IS_AJAX || IS_API_AUTH) && $this->msg(1, $data['html']);
                            $this->template->assign(array(
                                'pay' => $data,
                            ));
                            $this->template->display('pay_result.html');
                            exit;
                        }
                    } else {
                        $this->member_msg($data['error']);
                    }
				} else {
					$error = L('充值失败，未知错误');
				}
			}
		}
		$this->render(array(
            'pay' => $pay,
			'list' => [],
			'money' => $money > 0 ? $money : '',
			'error' => $error,
		), 'pay_add.html');
	}
	
	/**
     *  转账服务
     */
    public function transfer() {

        $error = '';
        if (IS_POST) {
            $data = $this->input->post('data');
            $member = $this->db->where('username', safe_replace($data['username']))->get('member')->row_array();
            if (!$member) {
                $error = L('会员不存在');
            } elseif ($this->uid == $member['uid']) {
                $error = L('不能对自己转账');
            } else {
                if ($data['type']) {
                    $value = abs((int)$data['value']);
                    if ($value <= 0) {
                        $error = L('请输入请输入数量');
                    } elseif ($value > $this->member['score']) {
                        $error = L(SITE_SCORE.'不足！本次需要%s'.SITE_SCORE.'，当前余额%s'.SITE_SCORE.'', $value, $this->member['score']);
                    } else {
                        $this->models('member/score')->edit(1, $this->uid, -$value, '', L('为会员【%s】转账'.SITE_SCORE.'%s', $member['username'], $value));
                        $this->models('member/score')->edit(1, $member['uid'], $value, '', L('收到会员【%s】转账'.SITE_SCORE.'%s', $member['username'], $value));
                        $this->member_msg(L('转账成功'), dr_member_url('pay/score'), 1);
                    }
                } else {
                    $value = abs((float)$data['value']);
                    if ($value < 0.01) {
                        $error = L('金额无效，请重新填写');
                    } elseif ($value > $this->member['money']) {
                        $error = L(SITE_MONEY.'不足！本次需要%s'.SITE_MONEY.'，当前余额%s'.SITE_MONEY.'', $value, $this->member['money']);
                    } else {
                        $this->models('system/pay')->add($this->uid, -$value, '为会员【%s】转账￥%s元'.$member['username'].','.$value);
                        $this->models('system/pay')->add($member['uid'], $value, '收到会员【%s】转账的￥%s元'.$this->member['username'].','.$value);
                        $this->member_msg(L('转账成功'), dr_member_url('pay/index'), 1);
                    }
                }
            }
			(IS_AJAX || IS_API_AUTH) && $this->msg(0, $error);
        }

        $this->template->assign(array(
            'data' => $data,
            'result_error' => $error,
        ));
        $this->template->display('pay_transfer.html');

    }

	/**
     * 资金兑换
     */
    public function convert() {
		
		$error = '';
		if (IS_POST) {
			$type = (int)$this->input->post('type');
			if ($type) {
				// 兑换人民币
				$money = abs((int)$this->input->post('score1'));
				$score = (float)$money * SITE_CONVERT;
				if (!$money) {
					$error = L('请输入'.SITE_MONEY.'金额');
				} elseif ($score > $this->member['score']) {
					$error = L('账号余额不足');
				} else {
					// 虚拟币减少
					$this->models('member/score')->edit(1, $this->uid, -$score, '', '自助兑换服务');
					// 人民币增加
					$this->models('system/pay')->add($this->uid, $money, '自助兑换服务');
					$this->member_msg(L('兑换成功'), dr_member_url('pay/index'), 1);
				}
			} else {
				// 兑换虚拟币
				$score = abs((int)$this->input->post('score0'));
				$money = (float)$score/SITE_CONVERT;
				if (!$score) {
					$error = L('请输入'.SITE_SCORE.'数量');
				} elseif ($money > $this->member['money']) {
					$error = L('账号余额不足');
				} else {
					// 虚拟币增加
					$this->models('member/score')->edit(1, $this->uid, $score, '', '自助兑换服务');
					// 人民币减少
					$this->models('system/pay')->add($this->uid, -$money, '自助兑换服务');
					$this->member_msg(L('兑换成功'), dr_member_url('pay/score'), 1);
				}
			}

			(IS_AJAX || IS_API_AUTH) && $this->msg(0, $error);
		}
	
		$this->template->assign(array(
			'result_error' => $error,
		));
		$this->template->display('pay_convert.html');
	}
    
    public function index(){
        
    }
    
    public function log() {
		$this->_log($type = (int)$this->input->get('type'));
    }
    
    public function _log($type){
        
        $pay = $this->models('system/pay');
        
        switch ($type) {
            case 1:
                $pay->where('value>0');
                break;
                
            case 2:
                $pay->where('value<0');
                break; 
                
            default:
                break;
        }
        $result = $pay->get($this->uid);
        
		$this->render(array(
			'type' => [],
            'list' => $result['list'],
            'pages'	=> $pay->pages(url_build("system/pay/log/type/{$type}"), $result['total']),
		), 'pay_index.html');
    }

}