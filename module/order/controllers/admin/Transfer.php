<?php


class Transfer extends M_Controller {

    public function __construct() {
        parent::__construct();
        $this->field = array(
            'bank_note' => array(
                'ismain' => 1,
                'fieldname' => 'thumb',
                'fieldtype' => 'File',
                'setting' => array(
                    'option' => array(
                        'ext' => 'jpg,gif,png',
                        'size' => 10,
                    )
                )
            ),
        );
    }

    /**
     * 转账受理主页
     */
    public function index() {
        $this->_admin_list(L('转账受理'), 0, 1);
    }

    /**
     * 录入转账信息方法
     */
    public function info() {

        $id = (int)$this->input->get('id');
        $order = $this->models('Module/order')->get_info($id);
        if (!$order) {
            $this->admin_msg(L('订单不存在'), $_SERVER['HTTP_REFERER']);
        } else if ($order['pay_type'] != 3) {
            $this->admin_msg(L('订单支付方式不匹配'), $_SERVER['HTTP_REFERER']);
        }

        $data = $this->db->where('oid', $id)->get(SITE_ID.'_order_transfer')->row_array();

        if (IS_POST) {
            $this->validate_filter($this->field);
            $post = $this->input->post('data');
            if(!$post['note'] || !$post['bank_name'] || !$post['bank_sn']) {
                $this->admin_msg(L('录入信息不完整'));
            }
            $post['oid'] = $order['id'];
            $post['uid'] = $this->uid;
            $post['buy_uid'] = $order['buy_uid'];
            $post['sell_uid'] = $order['sell_uid'];
            $post['username'] = $this->member['username'];
            $post['inputtime'] = SYS_TIME;
            if (!$data) {
                // 新增记录
                $this->db->insert(SITE_ID.'_order_transfer', $post);
                $data['id'] = $this->db->insert_id();
                $this->models('Module/order')->daozhang($order); // 录入之后自动到账
            } else {
                // 更新记录
                $this->db->where('id', $data['id'])->update(SITE_ID.'_order_transfer', $post);
            }
            $this->attachment_handle($this->uid, SITE_ID.'_order_transfer' .'-'.$data['id'], $this->field);
            $this->admin_msg(L('操作成功，正在刷新...'), dr_url('order/transfer/index'), 1);

        }

        $this->template->assign(
            array(
                'data' => $data,
                'menu' => $this->get_menu_v3(array(
                    L('转账受理') => array('order/admin/transfer/index', 'shopping-cart'),
                    L('录入/查看转账') => array('order/admin/transfer/info/id/'.$id, 'rmb'),
                )),
                'order' => $order,
                'field' =>$this->field,
            )
        );
        $this->template->display('transfer_info.html');
    }

}