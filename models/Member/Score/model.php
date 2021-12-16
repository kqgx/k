<?php

class Member_Score_model extends CI_Model{

	public $cache_file;
    
    public $prefix;
    public $tablename;
    
    public $pagesize = 10;
    public $cache = 0;
    
    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_scorelog';
    }

    public function get($uid){
        return $this->where('uid', $uid)->result();
    }
    
	/*
	 * 条件查询
	 *
	 * @param	object	$select	查询对象
	 * @param	array	$param	条件参数
	 * @return	array	
	 */
	private function _where(&$select, $param) {
	
		$_param = array();
		$this->cache_file = md5($this->duri->uri(1).$this->uid.SITE_ID.$this->input->ip_address().$this->input->user_agent()); // 缓存文件名称
		
		// 存在POST提交时，重新生成缓存文件
		if (IS_POST) {
			$data = $this->input->post('data');
			$this->cache->file->save($this->cache_file, $data, 3600);
			$param['search'] = 1;
		}
		
		// 存在search参数时，读取缓存文件
		if ($param['search'] == 1) {
			$data = $this->cache->file->get($this->cache_file);
			$_param['search'] = 1;
			isset($data['start']) && $data['start'] && $data['start'] != $data['end'] && $select->where('inputtime BETWEEN '.$data['start'].' AND '. $data['end']);
		}
		
		$select->where('uid', $param['uid']);
		$_param['uid'] = $data['uid'];
		
		return $_param;
	}
	
	/*
	 * 数据分页显示
	 *
	 * @param	array	$param	条件参数
	 * @param	intval	$page	页数
	 * @param	intval	$total	总数据
	 * @return	array	
	 */
	public function limit_page($param, $page, $total) {
		
		if (!$total) {
			$select	= $this->db->select('count(*) as total');
			$this->_where($select, $param);
			$data = $select->get($this->tablename)->row_array();
			unset($select);
			$total = (int)$data['total'];
			if (!$total) return array(array(), array('total' => 0));
		}
		
		$select	= $this->db->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1));
		$_param	= $this->_where($select, $param);
		$data = $select->order_by('inputtime DESC')->get($this->tablename)->result_array();
		$_param['total'] = $total;
		
		return array($data, $_param);
	}

    /**
     * 更新分数
     *
     * @param	intval	$uid	会员id
     * @param	intval	$value	分数变动值
     * @param	string	$mark	标记
     * @param	string	$note	备注
     * @param	intval	$count	统计次数
     * @return	intval
     */
    public function edit($uid, $val, $mark, $note = '', $count = 0) {

        if (!$uid || !$val) {
            return NULL;
        }

        if ($count && $this->db->where('mark', $mark)->count_all_results($this->tablename) >= $count) {
            return NULL;
        }

        $member = $this->db->select('score, username')->where('uid', $uid)->get('member')->row_array();
        $score = (int)$member['score'];
        $value = $score + $val;
        $value = $value > 0 ? $value : 0; // 不允许积分或虚拟币小于0
        unset($member);

        // 更新
        $this->db->where('uid', (int)$uid)->update('member', array('score' => $value));

        unset($value);

        $this->db->insert($this->tablename, array(
            'uid' => $uid,
            'mark' => $mark,
            'note' => $note,
            'value' => $val,
            'inputtime' => SYS_TIME,
        ));

        return $this->db->insert_id();
    }	
}