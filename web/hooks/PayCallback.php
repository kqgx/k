<?php

class PayCallback extends CI_Model
{
    private $order;
    private $notify;
    private $type;
    private $user;

    public function _call(array $data)
    {
        $this->type = $data['type'];
        $this->order = $data['data'];
        $this->notify = $data['notify'];
        $this->user = $data['member'];
        if ($this->order['module'] && method_exists($this, $this->order['module'])) {
            $this->{$this->order['module']}();
        } else {
            $this->_other();
        }
    }

    private function memberGroup()
    {
        list($id, $num) = explode('|', $this->order['order']);
        if (!is_numeric($id) || !is_numeric($num)) {
            throw new Exception('paylog order format error');
        }
        $group = $this->models('member/group')->get($id);
        if ($id == $this->user['groupid']) {
            $time = $this->user['overdue'];
            $renew = true;
        } else {
            $time = 0;
            $renew = false;
        }
        $time = $this->models('member')->upgrade($this->user['uid'], $id, $group['limit'], $time);
        $time = $time > 2000000000 ? L('永久') : dr_date($time);
        $subject = $renew ? L('会员续费成功') : L('会员升级成功');
        $message = dr_lang($renew ? @file_get_contents(WEBPATH . 'cache/email/xufei.html') : @file_get_contents(WEBPATH . 'cache/email/group.html'), $this->user['name'] ? $this->user['name'] : $this->user['username'], $group['name'], $time, $num);
        // 邮件提醒
        $this->models('system/email')->queue($this->user['email'], $subject, $message);
    }

    private function recharge()
    {
        // $this->models('member')->modify_money($this->order['uid'], $this->order['value']);
    }

    private function _other()
    {
        $this->recharge();
    }
}
