<?php

class Attachment extends M_Controller {

    public function index() {

        $ext = safe_replace($this->input->get('ext'));
        $table = $this->input->get('module');

        $page = max((int)$this->input->get('page'), 1);
        
        // 检测可管理的模块
        $module = array();
        $modules = $this->get_cache('module', SITE_ID);
        if ($modules) {
            foreach ($modules as $dir) {
                $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
                $this->_module_post_catid($mod, $this->markrule) && $module[$dir] = $mod['name'];
            }
        }
        
        // 查询结果
        list($total, $data) = $this->models('system/attachment')->limit($this->uid, $page, $this->pagesize, $ext, $table);
        
        $acount = $this->get_cache('member', 'setting', 'permission', $this->markrule, 'attachsize');
        $acount = $acount ? $acount : 1024000;
        $ucount = $this->db->select('sum(`filesize`) as total')->where('uid', (int)$this->uid)->limit(1)->get('attachment')->row_array();
        $ucount = (int)$ucount['total'];
        $acount = $acount * 1024 * 1024;
        if ($acount && $ucount > $acount) {
            $ucount = $acount; // 表示空間滿了
        }
        $scount = max($acount - $ucount, 0);
        
        $this->template->assign(array(
            'ext' => $ext,
            'list' => $data,
            'table' => $table,
            'module' => $module,
            'acount' => $acount,
            'ucount' => $ucount,
            'scount' => $scount,
            'pages'	=> $this->get_member_pagination(dr_member_url($this->router->class.'/'.$this->router->method, array('ext' => $ext)), $total),
            'page_total' => $total,
        ));
        $this->template->display('account_attachment_list.html');
    }

    // 删除附件
    public function delete() {
        $id = (int)$this->input->post('id');
        $this->models('system/attachment')->delete($this->uid, '', $id) ? $this->msg(1, L('操作成功，正在刷新...'))) :  exit(dr_json(0, 'Error');
    }
}