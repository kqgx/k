<?php

/* v3.1.0  */
class Sell extends M_Controller {


    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        // 使用权限判断
        if (!@in_array($this->groupid, $this->mconfig['permission']['sell']['use'])) {
            if ($this->groupid) {
                $this->member_msg(L('您无权限使用商家功能'));
            } else {
                $this->member_msg(L('您还没有登录，无权限使用'), dr_member_url('login/index'), 3, 2);
            }
        }
    }

    // 全部
    public function index() {
        $this->_list(1);
    }


    /**
     * 待付款
     */
    public function fk() {
        $this->_list(1, 1);
    }

    /**
     * 待发货
     */
    public function fh() {
        $this->_list(1, 2);
    }

    /**
     * 交易完成
     */
    public function wc() {
        $this->_list(1, 3);
    }

    /**
     * 交易关闭
     */
    public function close() {
        $this->_list(1, 9);
    }

    /**
     * 订单详情
     */
    public function info() {

        $id = (int)$this->input->get('id');
        $data = $this->models('Module/order')->get_info($id);
        if (!$data) {
            $this->member_msg(L('订单不存在'));
        }

        // 权限验证
        if ($data['sell_uid'] != $this->uid) {
            $this->member_msg(L('您无权限操作此订单'));
        }

        // 操作记录（卖家和管理员可查看）
        $log = array();
        if ($data['sell_uid'] == $this->uid || $this->member['admin']) {
            $log = $this->db->where('oid', $id)->order_by('inputtime desc')->get(SITE_ID.'_order_operate')->result_array();
            $this->load->library('dip');
        }

        // 快递配置文件
        $kd = require APPPATH.'libraries/Kd.php';

        // 商家信息
        $store = is_dir(WEBPATH.'store') ? $this->db->where('uid', intval($data['sell_uid']))->get(SITE_ID.'_store')->row_array() : array();

        $this->template->assign(array(
            'id' => $id,
            'kd' => $kd,
            'log' => $log,
            'order' => $data,
            'store' => $store,
            'field' => $this->get_cache('module-'.SITE_ID.'-order', 'field'),
            'paytype' => $this->mconfig['paytype'],
            'meta_name' => L('订单详情'),
        ));
        $this->template->display(is_file(APPPATH.'templates/'.MODULE_TEMPLATE.'/sell_info_'.$data['mid'].'.html') ? 'sell_info_'.$data['mid'].'.html' : 'sell_info.html');
    }

    // 快递接口
    public function kd() {
        echo str_replace(
            array('ickd_return'),
            array('dr_table dr_order_kd'),
            $this->_kd_info($_GET['name'], $_GET['sn'])
        );
        exit;
    }

    // 订单评论
    public function comment() {

        $id = (int)$this->input->get('id');
        $data = $this->models('Module/order')->get_info($id);
        if (!$data) {
            $this->member_msg(L('订单不存在'));
        }

        // 权限验证
        if ($data['sell_uid'] != $this->uid) {
            $this->member_msg(L('您无权限操作此订单'));
        }

        // 订单状态
        if ($data['order_status'] != 3) {
            $this->member_msg(L('订单状态不匹配'));
        }

        $this->template->assign(array(
            'order' => $data,
        ));
        $this->template->display('order_comment.html');
    }
}