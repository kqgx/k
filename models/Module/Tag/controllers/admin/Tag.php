<?php

class Tag extends M_Controller {
	
	public $module;
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->module = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR);
		!$this->module && $this->admin_msg(L('模块不存在，请尝试更新缓存'));
    }
	
	/**
     * tag
     */
	protected function _tag() {
		
		$code = $this->input->get('name', TRUE);
		$data = $this->models('Module/tag')->tag($code);
		!$data && $this->msg(L('Tag(%s)不存在', $code));

		$sql = 'SELECT * FROM '.$this->models('module/content')->prefix.' WHERE ';
		$tag = $where = array();
		foreach ($data as $t) {
			$tag[] = $t['name'];
			$where[] = '`title` LIKE "%'.$t['name'].'%" OR `keywords` LIKE "%'.$t['name'].'%"';
		}

		$tag = implode(',', $tag);
		$sql.= '`status`=9 AND ('.implode(' OR ', $where).')';

		$sql.= ' ORDER BY `updatetime` DESC';

		$this->render(array(
			'tag' => $tag,
			'code' => $code,
			'list' => $data,
			'tagsql' => $sql,
			'urlrule' => dr_tag_url($this->module, $code, '{page}'),
			'meta_title' => $tag.(SITE_SEOJOIN ? SITE_SEOJOIN : '_').$this->module['name'],
			'meta_keywords' => $this->module['setting']['seo']['meta_keywords'],
			'meta_description' => $this->module['setting']['seo']['meta_description']
		), 'tag.html');
	}
	
	/**
     * 后台菜单
     */
	private function _menu() {
		$this->template->assign('menu', $this->get_menu_v3(array(
			L('标签管理') => array(APP_DIR.'/admin/tag/index', 'tag'),
			L('添加') => array(APP_DIR.'/admin/tag/add_js', 'plus'),
		)));
	}

    /**
     * 管理
     */
    public function index() {
		
		if ($this->input->post('action') == 'del') {
			!$this->is_auth(APP_DIR.'/admin/tag/del') &&  $this->msg(0, L('您无权限操作'));
			$id = $this->input->post('ids');
			$id && $this->db->where_in('id', $id)->delete($this->models('Module/tag')->tablename);
            $this->system_log('删除站点【#'.SITE_ID.'】模块【'.APP_DIR.'】Tag内容【#'.@implode(',', $id).'】'); // 记录日志
            $this->msg(1, L('操作成功，正在刷新...'));
		}
		
		// 数据库中分页查询
		$kw = $this->input->get('kw') ? $this->input->get('kw') : '';
		list($data, $param)	= $this->models('Module/tag')->limit_page($kw, max((int)$this->input->get('page'), 1), (int)$this->input->get('total'));

        // 菜单选择
        if (isset($_GET['kw'])) {
            $this->template->assign('menu', $this->get_menu_v3(array(
                L('标签管理') => array(APP_DIR.'/admin/tag/index/kw/', 'tag'),
                L('添加') => array(APP_DIR.'/admin/tag/add_js', 'plus'),
            )));
        } else {
            $this->_menu();
        }

		$this->template->assign(array(
			'mod' => $this->module,
			'list' => $data,
			'param'	=> $param,
			'pages'	=> $this->get_pagination(dr_url(APP_DIR.'/tag/index', $param), $param['total'])
		));
		$this->template->display('tag_index.html');
    }
	
	/**
     * 添加
     */
    public function add() {
	
		if (IS_POST) {
			$data = $this->input->post('data', TRUE);
			$result	= $this->models('Module/tag')->add($data);
			switch ($result) {
				
				case -1:
					$this->msg(0, L('数据错误'), 'name');
					break;
					
				case -2:
					$this->msg(0, L('Tag名称重复了，请更换名称'), 'name');
					break;
				
				default:
                    $this->system_log('添加【#'.SITE_ID.'】模块【'.APP_DIR.'】Tag内容【'.$data['name'].'】'); // 记录日志
					$this->msg(1, L('操作成功，正在刷新...'));
					break;
			}
		}
		
		$this->template->assign(array(
			'data' => array()
		));
		$this->template->display('tag_add.html');
	}
	
	/**
     * 修改
     */
    public function edit() {
	
		$id = (int)$this->input->get('id');
		$data = $this->models('Module/tag')->get($id);
		!$data && exit(L('对不起，数据被删除或者查询不存在'));
		
		if (IS_POST) {
			
			$data = $this->input->post('data', TRUE);
			$result	= $this->models('Module/tag')->edit($id, $data);
			switch ($result) {
				
				case -1:
					$this->msg(0, L('数据错误'));
					break;
					
				case -2:
					$this->msg(0, L('Tag名称重复了，请更换名称'));
					break;
				
				default:
                    $this->system_log('修改【#'.SITE_ID.'】模块【'.APP_DIR.'】Tag内容【#'.$id.'】'); // 记录日志
					$this->msg(1, L('操作成功，正在刷新...'));
					break;
			}
		}
		
		$this->template->assign(array(
			'id' => $id,
			'data' => $data
		));
		$this->template->display('tag_add.html');
	}
	
	/**
     * 删除
     */
    public function del() {
		$this->db->where('id', (int)$this->input->get('id'))->delete($this->models('Module/tag')->tablename);
        $this->system_log('删除【#'.SITE_ID.'】模块【'.APP_DIR.'】Tag内容【#'.$this->input->get('id').'】'); // 记录日志
		$this->msg(1, L('操作成功，正在刷新...'));
	}
	
}