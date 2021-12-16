<?php


	
class Address extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->load->model('address_model');
    }
	
    /**
     * 获取收货地址
     */
    public function index() {

        ob_start();
		$default = array();
        if ($this->uid) {
            $list = $this->db->where('uid', $this->uid)->order_by('id asc')->get('member_address')->result_array();
			if ($list) {
				foreach ($list as $t) {
					if ($t['default']) {
						$default = $t;
					}
				}
				if (!$default) {
					$default = $list[0];
				}
			}
        } else {
            $data = string2array($this->session->userdata('guest_address'));
            if ($data) {
                $list = array( 0 => $data );
                $default = $list[0];
            }
        }
		
        
		$this->template->assign(array(
			'list' => $list,
			'default' => $default,
            'is_guest' => !$this->uid,
		));
		$this->template->display('address_data.html');
        $html = ob_get_contents();
        ob_clean();

        $this->return_jsonp(json_encode(array('html' => $html)));
    }
	
	/**
     * 添加地址
     */
    public function add() {
	
		if ($this->input->get('submit')) {
			$data = $this->input->get('data');
			if (!$data['city'] || !$data['name'] || !$data['phone'] || !$data['zipcode'] || !$data['address']) {
                $this->return_jsonp(dr_json(0, L('请认真填写内容')));
			} else {
                if ($this->uid) {
                    $this->address_model->add_address($data);
                } else {
                    $this->session->set_userdata('guest_address', array2string($data));
                }
                $this->return_jsonp(dr_json(1, 'ok'));
			}
		}

        $this->load->helper('system');
		$this->template->display('address_add.html');
    }
	
	/**
	 * 修改收货地址
	 */
	public function edit() {
	
		$id = (int)$this->input->get('id');
        if ($this->uid) {
            $data = $this->address_model->get_address($id);
        } else {
            $data = string2array($this->session->userdata('guest_address'));
        }

        if ($this->input->get('submit')) {
            $data = $this->input->get('data');
            if (!$data['city'] || !$data['name'] || !$data['phone'] || !$data['zipcode'] || !$data['address']) {
                $this->return_jsonp(dr_json(0, L('请认真填写内容')));
            } else {
				if ($this->uid) {
					$this->address_model->edit_address($id, $data);
                } else {
                    $this->session->set_userdata('guest_address', array2string($data));
                }
                $this->return_jsonp(dr_json(1, 'ok'));
            }
        }

        $this->load->helper('system');
		$this->template->assign(array(
			'data' => $data,
		));
		$this->template->display('address_add.html');
	}
	
	/**
	 * 删除收货地址
	 */
	public function del() {

		$id = (int)$this->input->get('id');
        if ($this->uid) {
            $this->db->where('id', $id)->where('uid', $this->uid)->delete('member_address');
        }

        $this->return_jsonp(dr_json(1, 'ok'));
	}

	/**
	 * 默认收货地址
	 */
	public function set_default() {

		$id = (int)$this->input->get('id');
        if ($this->uid) {
            $this->db->where('uid', $this->uid)->update('member_address', array('default' => 0));
            $this->db->where('id', $id)->where('uid', $this->uid)->update('member_address', array('default' => 1));
        }

        $this->return_jsonp(dr_json(1, 'ok'));
	}
}