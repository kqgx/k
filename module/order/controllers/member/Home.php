<?php

/* v3.1.0  */
class Home extends M_Controller {


    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    // 买家
    public function index() {
        $this->_list(0);
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
        if ($data['buy_uid'] != $this->uid) {
            $this->member_msg(L('您无权限操作此订单'));
        }


        // 快递配置文件
        $kd = require APPPATH.'libraries/Kd.php';

        // 是否购买成功并评论
        $comment = array();
        $goods = $this->db->where('oid', $id)->get(SITE_ID.'_order_buy')->result_array();
        foreach ($goods as $t) {
            $comment[$t['gid']] = $t['comment'] ? 0 : 1;
        }

        $this->template->assign(array(
            'id' => $id,
            'kd' => $kd,
            'order' => $data,
            'comment' => $comment,
            'field' => $this->get_cache('module-'.SITE_ID.'-order', 'field'),
            'paytype' => $this->mconfig['paytype'],
            'meta_name' => L('订单详情'),
        ));
        $this->template->display('order_info.html');
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

        exit;
        $id = (int)$this->input->get('id');
        $data = $this->models('Module/order')->get_info($id);
        if (!$data) {
            $this->member_msg(L('订单不存在'));
        }

        // 权限验证
        if ($data['buy_uid'] != $this->uid && $data['sell_uid'] != $this->uid) {
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