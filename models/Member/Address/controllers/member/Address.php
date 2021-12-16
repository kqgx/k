<?php


	
class Address extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
	
    /**
     * 收货地址
     */
    public function index() {
		$this->render($this->models('admin/address')->limit_page(), 'address_index.html');
    }
	
	/**
     * 添加地址
     */
    public function add() {
	
		if (IS_POST) {
			$data = $this->validate_filter($this->models('admin/address')->get_address_field());
			if (isset($data['error'])) {
				(IS_AJAX || IS_API_AUTH) && $this->msg(0, $data['msg'], $data['error']);
				$error = $data['error'].$data['msg'];
			} else {
				$this->models('admin/address')->add($data[1]);
				$this->member_msg(L('操作成功，正在刷新...'), dr_member_url('address/index'), 1);
			}
		}
		
		$this->render(array(
			'data' => $data,
			'result_error' => $error,
		), 'address_add.html');
    }
	
	/**
	 * 修改收货地址
	 */
	public function edit() {
	
		$id = (int)$this->input->get('id');
		$data = $this->models('admin/address')->get_address($id);
		
		if (IS_POST) {
			$data = $this->validate_filter($this->models('admin/address')->get_address_field(), $data);
			if (isset($data['error'])) {
				$error = $data['error'];
				(IS_AJAX || IS_API_AUTH) && $this->msg(0, $error['msg'], $error['error']);
			} else {
				$this->models('admin/address')->edit_address($id, $data[1]);
				$this->member_msg(L('操作成功，正在刷新...'), dr_member_url('address/index'), 1);
			}
		}
		$this->render(array(
			'data' => $data,
			'result_error' => $error,
		), 'address_add.html');
	}
	
	/**
	 * 删除收货地址
	 */
	public function del() {
		$id = (int)$this->input->get('id');
		if($id){
		    $this->models('admin/address')->del($id);
		}
	}
	
	/**
	 * 默认收货地址
	 */
	public function set_default() {
	    $id = (int)$this->input->get('id');
	    if($id){
	        $this->models('admin/address')->set_default($id);
	    }
	}
}