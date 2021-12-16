<?php
	
class Member_Address_model extends CI_Model {
    
    public $tablename;
	/**
	 * 收货地址模型类
	 */
    public function __construct() {
        parent::__construct();
        $this->tablename = 'member_address';
    }
	
	/**
	 * 收货地址字段
	 *
	 * @return	array
	 */
	public function get_address_field() {
		return  array(
			'city' => array(
				'ismain' => 1,
				'ismember' => 1,
				'fieldname' => 'city',
				'fieldtype' => 'Linkage',
				'setting' => array(
					'option' => array(
						'linkage' => 'address',
					),
					'validate' => array(
						'required' => 1
					)
				)
			),
			'zipcode' => array(
				'ismain' => 1,
				'ismember' => 1,
				'fieldtype' => 'Text',
				'fieldname' => 'zipcode',
				'setting' => array(
					'option' => array(
						'width' => 100,
					),
					'validate' => array(
						'required' => 1
					)
				)
			),
			'address' => array(
				'ismain' => 1,
				'ismember' => 1,
				'fieldtype' => 'Text',
				'fieldname' => 'address',
				'setting' => array(
					'option' => array(
						'width' => 250,
					),
					'validate' => array(
						'xss' => 1,
						'required' => 1
					)
				)
			),
			'name' => array(
				'ismain' => 1,
				'ismember' => 1,
				'fieldname' => 'name',
				'fieldtype' => 'Text',
				'setting' => array(
					'option' => array(
						'width' => 100,
					),
					'validate' => array(
						'xss' => 1,
						'required' => 1
					)
				)
			),
			'phone' => array(
				'ismain' => 1,
				'ismember' => 1,
				'fieldtype' => 'Text',
				'fieldname' => 'phone',
				'setting' => array(
					'option' => array(
						'width' => 150,
					),
					'validate' => array(
						'xss' => 1,
						'required' => 1
					)
				)
			),
			'default' => array(
				'ismain' => 1,
				'ismember' => 1,
				'fieldtype'	=> 'Radio',
				'fieldname' => 'default',
				'setting' => array(
					'option' => array(
						'value' => '0',
						'options' => L('验证码不正确，请重新发送验证码').'|1'.PHP_EOL.L('操作失败').'|0',
					)
				)
			),
		);
	}
	
	/**
	 * 添加收货地址
	 *
	 * @param	array	$data
	 * @return	id
	 */
	public function add($data) {
	
		if (!$data) {
            return NULL;
        }

        (int)$data['default'] && $this->db->where('uid', $this->uid)->update('member_address', array('default' => 0));
		
		$data['uid'] = $this->uid;
        $data['name'] = dr_strcut($data['name'],20);
        $data['phone'] = $data['phone'];
		$data['default'] = (int)$data['default'];
        $data['zipcode'] = (int)$data['zipcode'];

		$this->db->insert('member_address', $data);
		
		return $this->db->insert_id();
	}
	
	/**
	 * 修改收货地址
	 *
	 * @param	intval	$id
	 * @param	array	$data
	 * @return	intavl
	 */
	public function update($id, $data) {
	
		if (!$data || !$id) {
            return NULL;
        }

		$data['default'] && $this->db->where('uid', $this->uid)->update('member_address', array('default' => 0));
		
        $data['name'] = dr_strcut($data['name'],20);
        $data['phone'] = $data['phone'];
        $data['zipcode'] = (int)$data['zipcode'];

		$this->db
			 ->where('id', $id)
			 ->where('uid', $this->uid)
			 ->update('member_address', $data);
		
		return $id;
	}
	
	/**
	 * 获取单个收货地址
	 *
	 * @param	intval	$id
	 * @return	array
	 */
	public function get($id) {
		if ($id) {
             $this->db
					->where('id', $id)
					->where('uid', $this->uid)
					->limit(1)
					->get('member_address')
					->row_array();
        }
		
	}
	
	public function del($id){
		$this->db
			 ->where('id', $id)
			 ->where('uid', $this->uid)
			 ->delete('member_address');
	    
	}
	
	public function limit_page(){
        return  $this->db->where('uid', $this->uid)
                        ->order_by('id asc')
                        ->get($this->tablename)
                        ->result_array();    
	}
	
	/**
	 * 默认收货地址
	 */
	public function set_default($id) {
        if ($this->uid) {
            $this->db->where('uid', $this->uid)->update('member_address', array('default' => 0));
            $this->db->where('id', $id)->where('uid', $this->uid)->update('member_address', array('default' => 1));
            return true;
        }
        return false; 
	}
}