<?php

class Member_Card_model extends CI_Model {
    
    public $cache_file;
    
    /**
     * 条件查询
     *
     * @param	object	$select	查询对象
     * @param	array	$param	条件参数
     * @return	array
     */
    private function _card_where(&$select, $param) {

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
            $data['card'] && $select->where('card', $data['card']);
            if (strlen($data['status']) > 0 && !$data['status']) {
                $select->where('uid=0');
            } elseif ($data['status']) {
                $select->where('uid>0');
            }
            $data['username'] && $select->where('username', $data['username']);
        }

        return $_param;
    }

    /**
     * 数据分页显示
     *
     * @param	array	$param	条件参数
     * @param	intval	$page	页数
     * @param	intval	$total	总数据
     * @return	array
     */
    public function card_limit_page($param, $page, $total) {

        if (!$total) {
            $select	= $this->db->select('count(*) as total');
            $this->_card_where($select, $param);
            $data = $select->get('member_paycard')->row_array();
            unset($select);
            $total = (int)$data['total'];
            if (!$total) return array(array(), array('total' => 0));
        }

        $select	= $this->db->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1));
        $_param	= $this->_card_where($select, $param);
        $data = $select->order_by('inputtime DESC')->get('member_paycard')->result_array();
        $_param['total'] = $total;

        return array($data, $_param);
    }

    // 卡密充值
    public function add_for_card($id, $money, $card) {

        if (!$id || $money < 0) {
            return NULL;
        }

        // 更新RMB
        $this->db->where('uid', $this->uid)->set('money', 'money+'.$money, FALSE)->update('member');

        // 更新记录表
        $this->db->insert('member_paylog', array(
            'uid' => $this->uid,
            'type' => 0,
            'note' => L('卡号：%s', $card),
            'order' => 0,
            'value' => $money,
            'module' => '',
            'status' => 1,
            'inputtime' => SYS_TIME
        ));

        // 更新卡密状态
        $this->db->where('id', $id)->update('member_paycard', array(
            'uid' => $this->uid,
            'usetime' => SYS_TIME,
            'username' => $this->member['username'],
        ));

        return $money;
    }

    // 生成充值卡
    public function add($money, $endtime, $i) {

        if (!$money || !$endtime) {
            return NULL;
        }

        mt_srand((double)microtime() * (1000000 + $i));
        $data = array(
            'uid' => 0,
            'card' => date('Ys').strtoupper(substr(md5(uniqid()), rand(0, 20), 8)).mt_rand(100000, 999999),
            'money' => $money,
            'usetime' => 0,
            'endtime' => $endtime,
            'username' => '',
            'password' => mt_rand(100000, 999999),
            'inputtime' => SYS_TIME,
        );

        return $this->db->insert('member_paycard', $data) ? $data : NULL;
    }    
}