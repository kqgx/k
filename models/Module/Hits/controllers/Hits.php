<?php

class Hits extends M_Controller {

	public function index() {
	
	    $id = (int)$this->input->get('id');
	    $dir = safe_replace($this->input->get('module', TRUE));
        $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
        if (!$mod) {
            $this->json(0);
        }

        // 获取主表时间段
        $data = $this->db
                     ->where('id', $id)
                     ->select('hits,updatetime')
                     ->get($this->db->dbprefix(SITE_ID.'_'.$dir))
                     ->row_array();
        $hits = (int)$data['hits'] + 1;

        // 更新主表
		$this->db->where('id', $id)->update(SITE_ID.'_'.$dir, array('hits' => $hits));

        // 获取统计数据
        $total = $this->db->where('id', $id)->get($this->db->dbprefix(SITE_ID.'_'.$dir.'_hits'))->row_array();
        if (!$total) {
            $total['day_hits'] = $total['week_hits'] = $total['month_hits'] = $total['year_hits'] = 1;
        }

        // 更新到统计表
        $this->db->replace($this->db->dbprefix(SITE_ID.'_'.$dir.'_hits'), array(
            'id' => $id,
            'hits' => $hits,
            'day_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', SYS_TIME)) ? $hits : 1,
            'week_hits' => (date('YW', $data['updatetime']) == date('YW', SYS_TIME)) ? ($total['week_hits'] + 1) : 1,
            'month_hits' => (date('Ym', $data['updatetime']) == date('Ym', SYS_TIME)) ? ($total['month_hits'] + 1) : 1,
            'year_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', strtotime('-1 day'))) ? $hits : $total['year_hits'],
        ));

        $this->json($hits);
	}

	public function extend() {

	    $id = (int)$this->input->get('id');
	    $dir = $this->input->get('module', TRUE);
        $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
        if (!$mod) {
            $this->json(0);
        }

        $name = 'ehits'.$dir.SITE_ID.$id;
        $hits = (int)$this->get_cache_data($name);
		if (!$hits) {
			$data = $this->db->where('id', $id)->select('hits')->get(SITE_ID.'_'.$dir.'_extend')->row_array();
			$hits = (int)$data['hits'];
		}

		$hits++;
		$this->set_cache_data($name, $hits, (int)SYS_CACHE_MSHOW);

		$this->db->where('id', $id)->update(SITE_ID.'_'.$dir.'_extend', array('hits' => $hits));
        
        $this->json($hits);
	}	
}