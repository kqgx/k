<?php
	
class Site_Block_model extends CI_Model {
    
    public function get(){
        
    }
    
    public function cache($siteid = SITE_ID) {

        $this->ci->clear_cache('block-'.$siteid);
        $this->ci->dcache->delete('block-'.$siteid);

        $data = $this->db->get($siteid.'_block')->result_array();

        if (!$this->db->field_exists('code', $siteid.'_block')) {
            $this->db->query('ALTER TABLE `'.$this->db->dbprefix($siteid.'_block').'` ADD `code` VARCHAR(100) NOT NULL');
        }

        $cache = array();
        if ($data) {
            foreach ($data as $t) {
                if (!$t['code']) {
                    $t['code'] = $t['id'];
                    $this->db->where('id', $t['id'])->update($siteid.'_block', array(
                        'code' => $t['id'],
                    ));
                }
                $t = dr_get_block_value($t);
                switch (intval($t['i'])) {
                    case 1:
                        // 文本内容
                        $value = $t['value_1'];
                        break;
                    case 2:
                        // 丰富文本
                        $value = htmlspecialchars_decode($t['value_2']);
                        break;
                    case 3:
                        // 单文件
                        $value = $t['value_3'];
                        break;
                    case 4:
                        // 多文件
                        $value = string2array($t['value_4']);
                        break;
                }

                $cache[$t['code']] = array(
                    1 => $t['name'],
                    0 => $value,
                );
            }
            $this->ci->dcache->set('block-'.$siteid, $cache);
        }
        return $cache;
    }
    
}