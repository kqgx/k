<?php

class Group extends M_Controller {


    public function __construct(){
        parent::__construct();

    }

    /**
     * 会员组升级
     */
    public function upgrade() {
        $id = (int)$_GET['id'];
        $num = (int)$_GET['num'] ?: 1;
        if (!$id || $num < 0) {
            $this->json(0, '参数错误');
        } else {
            $group = $this->get_cache('member', 'group', $id);
            if ($group['limit'] == 4) {
                $num = 1;
            }
            if (!$group) {
                $this->json(0, L('会员组不存在'));
            } elseif (!$group['allowapply']) {
                $this->json(0, L('该会员组不允许自助升级'));
            }
            if ($id == $this->member['groupid']) {
                // 表示续费
                $time = $this->member['overdue'];
                $renew = TRUE;
                $time > 2000000000 && $this->json(0, L('当前会员组已永久，无需升级'));
            } else {
                // 表示申请其他组
                $time = 0;
                $renew = FALSE;
                !$this->get_cache('member', 'group', $this->member['groupid'], 'allowapply_orther') && $this->json(0, L('该会员组不允许申请其他组'));
            }

            $value = $group['price'] * $num;
            if ($group['unit'] == 1) {
                // 虚拟币扣减
                $this->member['score'] - $value < 0 && $this->json(0, L(SITE_SCORE.'不足！'));
                $this->models('member/score')->edit(1, $this->uid, -$value, '', '会员组升级消费：'.$group['name']);

                $time = $this->models('member')->upgrade($this->uid, $id, $group['limit'], $time, $num);
                $time = $time > 2000000000 ? L('永久') : dr_date($time);
                $subject = $renew ? L('会员续费成功') : L('会员升级成功');
                $message = dr_lang($renew ? @file_get_contents(WEBPATH.'cache/email/xufei.html') : @file_get_contents(WEBPATH.'cache/email/group.html'), $this->member['name'] ? $this->member['name'] : $this->member['username'], $group['name'], $time);

                // 邮件提醒
                $this->models('system/email')->queue($this->member['email'], $subject, $message);
                $this->json(1, $subject);
            } else {
                // 人民币支付
                $orders = $this->models('system/pay')->getOrders($this->uid, 'memberGroup', "$id|$num", 0);
                if ($orders && abs(reset($orders)['value']) == $value) {
                    $oid = reset($orders)['id'];
                } else {
                    $oid = $this->models('system/pay')
                        ->create($this->uid, $value, 'memberGroup', "$id|$num", '升级'.$group['name']);
                }
                $this->json(101, [
                    'payid' => $oid,
                    'price' => $value,
                    'unit_price' => $group['price']
                ], 'wait pay');
            }
        }
    }
}
