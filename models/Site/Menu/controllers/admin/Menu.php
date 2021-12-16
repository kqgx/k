<?php

/* v3.1.0  */
	
class Menu extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->template->assign('menu', $this->get_menu_v3(array(
		    L('后台菜单') => array('menu/admin/index', 'table'),
		)));
    }
	
	/**
     * 菜单管理
     */
    public function index() {
	
		if (IS_POST) {
			$ids = $this->input->post('ids');
			if (!$ids) {
                $this->msg(0, L('您还没有选择呢'));
            }
			// 可以不用判断权限
			if ($this->input->post('action') == 'order') {
				$_data = $this->input->post('data');
				foreach ($ids as $id) {
					$this->db->where('id', $id)->update('admin_menu',  array('displayorder' => (int)$_data[$id]['displayorder']));
				}
				$this->models('site/menu')->set('admin')->cache();
                $this->system_log('排序后台菜单项【#'.@implode(',', $ids).'】'); // 记录日志
				$this->msg(1, L('操作成功，请按F5刷新整个页面'));
			} else {
				$this->models('site/menu')->set('admin')->delete($ids);
                $this->system_log('删除后台菜单项【#'.@implode(',', $ids).'】'); // 记录日志
				$this->models('site/menu')->set('admin')->cache();
				$this->msg(1, L('操作成功，请按F5刷新整个页面'));
			}
		}
		
		$this->load->library('dtree');
		$this->dtree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$this->dtree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$left = $this->models('site/menu')->set('admin')->get_left_id();
		$data = $this->db->order_by('displayorder ASC,id ASC')->get('admin_menu')->result_array();
		$tree = array();
		
		if ($data) {
			foreach($data as $t) {
                $t['name'] = '<i class="iconm '.$t['icon'].'"></i> '.$t['name'];
				$t['option'] = '';
				if ($this->is_auth('menu/admin/add') && !in_array($t['pid'], $left)) {
					$t['option'].= '<a class="aadd" title="'.L('添加').'" href="'.dr_dialog_url(dr_url('menu/add', array('pid' => $t['id'])), 'add').'"> <i class="fa fa-plus"></i> '.L('添加').'</a>';
				} else {
					$t['option'].= '';
				}
				if ($this->is_auth('menu/admin/edit')) {
                    $t['hidden'] = '<a href="javascript:;" onClick="return dr_dialog_set(\''.($t['hidden'] ? L('<font color=blue><b>你确定要启用它？启用后将正常使用</b></font>') : L('<font color=red><b>你确定要禁用它？禁用后将无法使用</b></font>')).'\',\''.dr_url('menu/hidden',array('id'=>$t['id'])).'\');"><img src="'.THEME_PATH.'admin/images/'.($t['hidden'] ? 0 : 1).'.gif"></a>';
                } else {
                    $t['hidden'] = '<img src="'.THEME_PATH.'/admin/images/'.($t['hidden'] ? 0 : 1).'.gif">';
                }
				if ($this->is_auth('menu/admin/edit')) {
					$t['option'].= '<a class="aedit" title="'.L('修改').'" href="'.dr_dialog_url(dr_url('menu/edit', array('id' => $t['id'])), 'edit').'"> <i class="fa fa-edit"></i> '.L('修改').'</a>';
					$t['name'] = '<a title="'.L('修改').'" href="'.dr_dialog_url(dr_url('menu/edit', array('id' => $t['id'])), 'edit').'">'.$t['name'].'</a>&nbsp;&nbsp;';
				}
				if ($this->is_auth('menu/admin/del')) {
					$t['option'].= '<a class="adel" title="'.L('删除').'" href="javascript:;" onClick="return dr_dialog_del(\''.L('您确定要这样操作吗？').'\',\''.dr_url('menu/del',array('id' => $t['id'])).'\');"> <i class="fa fa-trash"></i> '.L('删除').'</a>';
				}
				$tree[$t['id']] = $t;
			}
		}

		$str = "<tr>
					<td><input name='ids[]' type='checkbox' class='dr_select toggle md-check' value='\$id' /></td>
					<td><input class='input-text displayorder' type='text' name='data[\$id][displayorder]' value='\$displayorder' /></td>
					<td>\$hidden</td>
					<td>\$spacer\$name</td>
					<td class='dr_option'>\$option</td>
				</tr>";
		$this->dtree->init($tree);

		$this->template->assign(array(
			'list' => $this->dtree->get_tree(0, $str),
		));
		$this->template->display('menu_index.html');
    }

	/**
     * 添加
     */
    public function add() {
	
		if (IS_POST) {
			$_data = $this->input->post('data');
			if ($this->input->post('_type') == 2) {
				$_data['url'] = '';
			} else {
				$_data['directory'] = $_data['dir'] = $_data['class'] = $_data['method'] = $_data['param'] = '';
			}
            $data = $this->models('site/menu')->set('admin')->add($_data);
            $this->system_log('添加后台菜单项【#'.$data['id'].'】'.$data['name']); // 记录日志
			$this->msg(1, L('操作成功，更新缓存生效'), $data);
		}
		
		$top = $this->models('site/menu')->set('admin')->get_top_id();
		$menu_name = $menu_type	= '';
		$data['pid'] = (int)$this->input->get('pid');
		if ($data['pid']) {
			if (in_array($data['pid'], $top)) {
				$menu_type = 0;
				$menu_name = L('分组菜单');
			} else {
				$menu_type = 1;
				$menu_name = L('链接菜单');
			}
		} else {
			$menu_type = 0;
			$menu_name = L('顶级菜单');
		}
		
		$this->template->assign(array(
			'data' => $data,
			'menu_url' => 2,
			'menu_name'	=> $menu_name,
			'menu_type'	=> $menu_type
		));
		$this->template->display('menu_add.html');
    }

	/**
     * 修改
     */
    public function edit() {
	
		$id = (int)$this->input->get('id');
		$data = $this->db->where('id', $id)->get('admin_menu')->row_array();
		!$data && $this->msg(0, '对不起，数据被删除或者查询不存在');

		if (IS_POST) {
			$_data = $this->input->post('data', TRUE);
			if ($this->input->post('_type') == 2) {
				$_data['url'] = '';
			} else {
				$_data['directory'] = $_data['dir'] = $_data['class'] = $_data['method'] = $_data['param'] = '';
			}
            $this->system_log('修改后台菜单项【#'.$id.'】'.$data['name']); // 记录日志
			$this->msg(1, L('操作成功，更新缓存生效'), $this->models('site/menu')->set('admin')->edit($id, $_data));
		}
		
		$top = $this->models('site/menu')->set('admin')->get_top_id();
		$uri = $this->duri->uri2ci($data['uri']);
		$uri['dir']	= $uri['app'] ? $uri['app'] : ($uri['path'] ? $uri['path'] : '');
		$menu_name = $menu_type = '';
		$select = '<select name="data[pid]">';
		if ($data['pid']) {
			if (in_array($data['pid'], $top)) { // 分组菜单
				$menu_type = 0;
				$menu_name = L('分组菜单');
				$select = $this->models('site/menu')->set('admin')->parent_select(1, $data['pid']);
			} else { // 链接菜单
				$menu_type = 1;
				$menu_name = L('链接菜单');
				$select = $this->models('site/menu')->set('admin')->parent_select(2, $data['pid']);
			}
		} else { // 顶级菜单
			$menu_type = 0;
			$menu_name = L('顶级菜单');
			$select = $this->models('site/menu')->set('admin')->parent_select(0, $data['pid']);
		}

		$this->template->assign(array(
			'uri' => $uri,
			'data' => $data,
			'select' => $select,
			'menu_url' => $data['uri'] ? 2 : 1,
			'menu_name'	=> $menu_name,
			'menu_type'	=> $menu_type
		));
		$this->template->display('menu_add.html');
    }
	
	/**
     * 隐藏/显示
     */
    public function hidden() {

		$id = (int)$this->input->get('id');
        $data = $this->db->where('id', $id)->get('admin_menu')->row_array();
        if ($data) {
            $update = array();
            $update[] = $id;
            $data2 = $this->db->where('pid', $id)->get('admin_menu')->result_array();
            if ($data2) {
                // 查询二级菜单
                foreach ($data2 as $t2) {
                    $update[] = $t2['id'];
                    $data3 = $this->db->where('pid', $t2['id'])->get('admin_menu')->result_array();
                    if ($data3) {
                        // 查询3及菜单
                        foreach ($data3 as $t3) {
                            $update[] = $t3['id'];
                        }
                    }
                }
            }
            // 更新状态
            $this->db->where_in('id', $update)->update('admin_menu', array('hidden' => $data['hidden'] ? 0 : 1));
            $this->system_log(($data['hidden'] ? '启用' : '禁用').'后台菜单项【#'.$id.'】'.$data['name']); // 记录日志
        }

		$this->models('site/menu')->set('admin')->cache();
		$this->msg(1, L('操作成功，请按F5刷新整个页面'));
	}

	/**
     * 删除
     */
    public function del() {
        $id = (int)$this->input->get('id');
		$this->models('site/menu')->set('admin')->delete($id);
        $this->system_log('删除后台菜单项【#'.$id.'】'); // 记录日志
		$this->models('site/menu')->set('admin')->cache();
		$this->msg(1, L('操作成功，请按F5刷新整个页面'));
	}

    // 图标大全
    public function icon() {
        $this->template->display('icon.html');exit;
    }

	/**
     * 初始化菜单
     */
    public function init() {
		$this->models('site/menu')->set('admin')->init();
		$this->models('site/menu')->set('admin')->cache();
        $this->system_log('初始化后台菜单项'); // 记录日志
		$this->admin_msg(L('操作成功，请按F5刷新整个页面'), '', 1);
	}
	
	/**
     * 缓存
     */
    public function cache() {
		$this->models('site/menu')->set('admin')->cache();
        (int)$_GET['admin'] or $this->admin_msg(L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 1);
	}
}