<?php

/* v3.1.0  */

class Buy extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 订单付款
     */
    public function index() {

        $ids = @explode('-', $this->input->get('id'));
        if (!$ids) {
            $this->msg(L('订单不存在'));
        }

        $is_score = $score = $price = 0;
        $pay_type = $sn = $order_data = $order = array();
        foreach ($ids as $id) {
            $row = $this->db->where('id', intval($id))->get(SITE_ID.'_order')->row_array();
            if ($row) {
                if ($row['buy_uid'] && $row['buy_uid'] != $this->uid) {
                    // 购买者判断
                    $this->msg(L('您无权限操作此订单'));
                } elseif ($row['pay_status'] != 0) {
                    // 状态判断
                    $this->msg(L('此订单已经付款'));
                }
                $sn[] = $row['sn'];
                $order[] = $id;
                $order_data[] = $row;
                if ($row['order_score']) {
                    $score+= $row['order_price'];
                    $is_score = 1;
                } else {
                    $price+= $row['order_price'];
                }
                $pay_type[$row['pay_type']] = $row['pay_type'];
            }
        }

        if (!$order) {
            $this->msg(L('订单不存在'));
        }

        // 付款方式不一致
        if (count($pay_type) > 1) {
            $this->msg(L('组合订单付款方式不一致，无法批量付款'), dr_member_url('order/home/index'), 2);
        } else {
            $pay_type = array_shift($pay_type);
        }

        if ($price > 0 || $score > 0) {
            // 付款
            $pay_price = max(0, $price - $this->member['money']);
            $pay_score = max(0, $score - $this->member['score']);
            $this->template->assign(array(
                'price' => $price,
                'score' => $score,
                'orders' => $order_data,
                'pay_type' => $pay_type,
                'pay_list' => $is_score ? array() : $this->get_pay_api(1),
                'pay_price' => $pay_price,
                'meta_title' => $this->mconfig['config']['buy_title'] ? $this->mconfig['config']['buy_title'] : '我的收银台',
                'meta_keywords' => SITE_KEYWORDS,
                'meta_description' => SITE_DESCRIPTION,
            ));
            if ($pay_type == 1) {
                // 货到付款
                foreach ($order_data as $order) {
                    // 改变订单状态
                    if ($order['order_status'] != 2) {
                        $order['order_status'] = 2;
                        $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
                            'order_status' => $order['order_status'],
                        ));
                        // 记录日志
                        $this->models('Module/order')->log($order, L('买家已下单'));
                    }
                }
                $this->template->display('buy_cod.html');
            } elseif ($pay_type == 2) {
                // 在线支付
                if (IS_POST) {
                    $type = $this->input->post('pay');
                    // 判断是否余额付款
                    if ($is_score || $type == 'yu') { // 当存在虚拟币时强制余额支付
                        if ($price > 0 && (!($this->member['money'] > 0) || $pay_price > 0)) {
                            // 判断rmb 余额不足
                            $this->msg(L('账户余额不足，当前余额￥%s', number_format($this->member['money'], 2)));
                        } elseif ($score > 0 && (!($this->member['score'] > 0) || $pay_score > 0)) {
                            // 判断虚拟币 余额不足
                            $this->msg(L('账户'.SITE_SCORE.'余额不足，当前'.SITE_SCORE.'%s', intval($this->member['score'])));
                        } else {
                            // 直接扣款
                            $this->models('Module/order')->pay($order_data, $price, 0);
                            $this->msg(L('付款成功'), count($order) > 1 ? dr_member_url('order/home/index') : dr_member_url('order/home/info', array('id'=>$order[0])), 2);
                        }
                    }
                    // 网银支付
                    $this->load->model('pay_model');
                    $oids = array();
                    foreach ($order_data as $t) {
                        $oids[] = $t['id'];
                    }
                    if ($data = $this->pay_model->add_for_online($type, $price, 'order', $oids)) {
                        if (!isset($data['error'])) {
                            // 更改订单状态为付款中（不改）
                            if (isset($data['form']) && $data['form']) {
                                $this->msg(L('正在为您跳转到支付页面，请稍后...').'<div style="display:none">'.$data['form'].'</div>', 'javascript:;', 2, 0);
                            } elseif (isset($data['url']) && $data['url']) {
                                $this->msg(L('正在为您跳转到支付页面，请稍后...'), $data['url'], 2, 0);
                            } else {
                                $this->template->assign(array(
                                    'pay' => $data,
                                ));
                                $this->template->display('pay_result.html');
                                exit;
                            }
                        } else {
                            $this->msg($data['error']);
                        }
                    } else {
                        $this->msg(L('充值失败，未知错误'));
                    }
                }
                $this->template->display('buy_online.html');
            } elseif ($pay_type == 3) {
                // 银行转账
                if (IS_POST) {
                    foreach ($order_data as $order) {
                        // 改变订单状态
                        $order['order_status'] = 6;
                        $order['pay_status'] = 4;
                        $order['pay_time'] = SYS_TIME;
                        $this->db->where('id', $order['id'])->update(SITE_ID.'_order', array(
                            'order_status' => $order['order_status'],
                            'pay_status' => $order['pay_status'],
                            'pay_time' => $order['pay_time'],
                        ));
                        // 记录日志
                        $this->models('Module/order')->log($order, L('买家已通过银行转账的方式付款'));
                    }
                    $this->msg(L('提交成功，我们会在1-3内处理转账信息'), count($order_data) > 1 ? dr_member_url('order/home/index') : dr_member_url('order/home/info', array('id'=>$order_data[0]['id'])), 2);
                }
                $this->template->assign('sn', @implode('、', $sn));
                $this->template->assign('bank', nl2br($this->mconfig['paytype'][3]['text']));
                $this->template->display('buy_bank.html');
            }

        } else {
            // 价格为0时无需付款
            $this->models('Module/order')->pay($order_data, 0, 0);
            $this->msg(L('付款成功'), count($order_data) > 1 ? dr_member_url('order/home/index') : dr_member_url('order/home/info', array('id'=>$order_data[0]['id'])), 2);
        }
    }


}