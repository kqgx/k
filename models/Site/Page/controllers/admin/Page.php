<?php

require MODELS.'Site/Page/Extends_Page.php';

class Page extends Extends_Page {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $menu = array(
            L('页面管理') => array(APP_DIR.'/admin/page/index', 'adn'),
            L('添加') => array(APP_DIR.'/admin/page/add', 'plus'),
            // L('自定义字段') => array('admin/field/index/rname/page/rid/'.SITE_ID, 'plus-square'),
        );
        if (APP_DIR) {
            unset($menu[L('自定义字段')]);
        }
		$this->template->assign('menu', $this->get_menu_v3($menu));

        $this->field = array(
            'name' => array(
                'name' => IS_ADMIN ? L('页面名称') : '',
                'ismain' => 1,
                'fieldname' => 'name',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 150,
                    ),
                    'validate' => array(
                        'required' => 1,
                        'formattr' => 'onblur="d_topinyin(\'dirname\',\'name\');"',
                    )
                )
            ),
            'dirname' => array(
                'name' => IS_ADMIN ? L('页面目录') : '',
                'ismain' => 1,
                'fieldname' => 'dirname',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 150,
                    ),
                    'validate' => array(
                        'required' => 1,
                    )
                )
            ),
            'thumb' => array(
                'name' => IS_ADMIN ? L('缩略图') : '',
                'ismain' => 1,
                'fieldname' => 'thumb',
                'fieldtype' => 'File',
                'setting' => array(
                    'option' => array(
                        'ext' => 'jpg,gif,png',
                        'size' => 10,
                    )
                )
            ),
            'keywords' => array(
                'name' => IS_ADMIN ? L('SEO关键字') : '',
                'ismain' => 1,
                'fieldname' => 'keywords',
                'fieldtype'	=> 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => '80%'
                    )
                )
            ),
            'title' => array(
                'name' => IS_ADMIN ? L('SEO标题') : '',
                'ismain' => 1,
                'fieldname' => 'title',
                'fieldtype'	=> 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => '80%'
                    )
                )
            ),
            'description' => array(
                'name' => IS_ADMIN ? L('SEO描述信息') : '',
                'ismain' => 1,
                'fieldname' => 'description',
                'fieldtype'	=> 'Textarea',
                'setting' => array(
                    'option' => array(
                        'width' => '80%',
                        'height' => 60
                    )
                )
            ),
            'template' => array(
                'name' => IS_ADMIN ? L('模板文件') : '',
                'ismain' => 1,
                'fieldname' => 'template',
                'fieldtype'	=> 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                        'value' => 'page.html'
                    )
                )
            ),
            'urllink' => array(
                'name' => IS_ADMIN ? L('转向链接') : '',
                'ismain' => 1,
                'fieldname' => 'urllink',
                'fieldtype'	=> 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 400,
                        'value' => ''
                    )
                )
            ),
            'urlrule' => array(
                'name' => IS_ADMIN ? L('URL规则') : '',
                'ismain' => 1,
                'fieldname' => 'urlrule',
                'fieldtype'	=> 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 300
                    )
                )
            ),
            'show' => array(
                'name' => IS_ADMIN ? L('是否显示') : '',
                'ismain' => 1,
                'fieldname' => 'show',
                'fieldtype'	=> 'Radio',
                'setting' => array(
                    'option' => array(
                        'value' => '1',
                        'options' => (IS_ADMIN ? L('是') : 'Yes').'|1'.PHP_EOL.(IS_ADMIN ? L('否') : 'No').'|0',
                    )
                )
            ),
            'getchild' => array(
                'name' => IS_ADMIN ? L('排序') : '',
                'ismain' => 1,
                'fieldtype'	=> 'Radio',
                'fieldname' => 'getchild',
                'setting' => array(
                    'option' => array(
                        'value' => '1',
                        'options' => (IS_ADMIN ? L('是') : 'Yes').'|1'.PHP_EOL.(IS_ADMIN ? L('否') : 'No').'|0',
                    )
                )
            ),
        );
    }
    
    /**
     * 首页
     */
    public function index() {
		if (IS_POST) {
			$ids = $this->input->post('ids');
            !$ids && $this->msg(0, L('您还没有选择呢'));
            if ($this->input->post('action') == 'del') {
                !$this->is_auth(APP_DIR.'/admin/page/index') && $this->msg(0, L('您无权限操作'));
				$this->del($ids);
				$this->msg(1, L('操作成功，更新缓存生效'));
            } elseif ($this->input->post('action') == 'order') {
				$data = $this->input->post('data');
				foreach ($ids as $id) {
					$this->db->where('id', $id)->update($this->models('site/page')->tablename, $data[$id]);
				}
				$this->models('site/page')->cache(SITE_ID);
                $this->system_log('排序页面【'.@implode(',', $ids).'】'); // 记录日志
				$this->msg(1, L('操作成功，更新缓存生效'));
			} else {
                !$this->is_auth(APP_DIR.'/admin/page/index') && $this->msg(0, L('您无权限操作'));
				$this->admin_delete($ids);
				$this->msg(1, L('操作成功，更新缓存生效'));
			}
		}
		
		$this->models('site/page')->repair();
		$this->load->library('dtree');
		$this->dtree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$this->dtree->nbsp = '&nbsp;&nbsp;&nbsp;';
		
		$tree = array();
		$data = $this->models('site/page')->get_data();
		
		if ($data) {
			foreach($data as $t) {
				$t['option'] = '<a class="ago" href="'.dr_url_prefix($t['url']).'" target="_blank"> <i class="fa fa-send"></i> '.L('访问').'</a>';
                // $this->is_auth(APP_DIR.'/admin/page/add') && $t['option'].= '<a class="aadd" href='.dr_url(APP_DIR.'/page/add', array('id' => $t['id'])).'> <i class="fa fa-plus"></i> '.L('添加子页').'</a>';
                $this->is_auth(APP_DIR.'/admin/page/edit') && $t['option'].= '<a class="aedit" href='.dr_url(APP_DIR.'/page/edit', array('id' => $t['id'])).'> <i class="fa fa-edit"></i> '.L('修改/内容').'</a>';
                $t['option'] .= '<a class="alist" href="javascript:;" onclick="dr_copy_link(\''.dr_url_prefix($t['url']).'\')"> <i class="fa fa-search"></i> '.L('复制链接').'</a>';
				$t['cache'] = $t['setting']['nocache'] ? '<img src="'.THEME_PATH.'admin/images/0.gif">' : '<img src="'.THEME_PATH.'admin/images/1.gif">';
                $t['show'] = $t['show'] ? '<img src="'.THEME_PATH.'admin/images/1.gif">' : '<img src="'.THEME_PATH.'admin/images/0.gif">';
				$t['cache'] = '<a href="'.dr_url(APP_DIR.'/page/option', array('op' => 'cache', 'id' => $t['id'])).'">'.$t['cache'].'</a>';
				$t['show'] = '<a href="'.dr_url(APP_DIR.'/page/option', array('op' => 'show', 'id' => $t['id'])).'">'.$t['show'].'</a>';
                $tree[$t['id']] = $t;
			}
		}
		
		$str = "<tr class='\$class'>";
		$str.= "<td><input name='ids[]' type='checkbox' class='toggle md-check dr_select' value='\$id' /></td>";
		$str.= "<td><input class='input-text displayorder' type='text' name='data[\$id][displayorder]' value='\$displayorder' /></td>";
		$str.= "<td>\$id</td>";
		$str.= $this->is_auth(APP_DIR.'/admin/page/edit') ? "<td>\$spacer<a href='".dr_url(APP_DIR.'/page/edit')."&id=\$id'>\$name</a>  \$parent</td>" : "<td>\$spacer\$name  \$parent</td>";
		$str.= "<td>\$dirname</td>";
        // $str.= "<td style='text-align: center'>\$cache</td>";
        // $str.= "<td style='text-align: center'>\$show</td>";
		$str.= "<td class='dr_option'>\$option</td>";
		$str.= "</tr>";
		$this->dtree->init($tree);
		
		$this->render(array(
			'list' => $this->dtree->get_tree(0, $str),
            'page' => (int)$this->input->get('page')
		), 'page_index.html');         
	}

    /**
     * 添加
     */
    public function add() {
		$pid = (int)$this->input->get('id');
		$data = array(
            'show' => 1,
            'getchild' => 1,
        );
        $error = $result = NULL;
        $field = $this->get_cache('page-field-'.SITE_ID);

		if (IS_POST) {
            $my = $field ? array_merge($this->field, $field) : $this->field;
			$data = $this->validate_filter($my);
            if (isset($data['error'])) {
                $error = $data;
                $data = $this->input->post('data');
            } else {
                $data[1]['pid'] = $this->input->post('pid');
                $data[1]['show'] = intval($data[1]['show']);
                $data[1]['urlrule'] = $this->input->post('urlrule');
                $data[1]['getchild'] = intval($data[1]['getchild']);
                $page = $this->models('site/page')->add($data[1]);
                if (is_numeric($page)) {
                    $this->models('site/page')->cache(SITE_ID);
                    $this->system_log('添加页面【#'.$page.'】'.$data[1]['name']); // 记录日志
                    $this->attachment_handle($this->uid, $this->models('site/page')->tablename.'-'.$page, $my);
                    if ($this->input->post('action') == 'back') {
                        $this->admin_msg(L('操作成功，更新缓存生效'), dr_url(APP_DIR.'/page/index'), 1);
                    } else {
                        $pid = $data[1]['pid'];
                        unset($data);
                        $result = L('操作成功，更新缓存生效');
                    }
                } else {
                    $data = $this->input->post('data');
                    $error = array('msg' => $page);
                }
            }
		} else {
            // 调用父属性
            if ($pid && ($row = $this->db->where('id', $pid)->get(SITE_ID.'_page')->row_array())) {
                $data['urlrule'] = $row['urlrule'];
                $data['setting'] = string2array($row['setting']);
                $data['template'] = $row['template'];
                // 过滤自定义字段
                if ($field && $data['setting']['nofield']) {
                    foreach ($field as $i => $t) {
                        if (@in_array($t['fieldname'], $data['setting']['nofield'])) {
                            unset($field[$i]);
                        }
                    }
                }
            }
        }
		
		$this->render(array(
			'page' => 0,
			'data' => $data,
			'error' => $error,
			'field' => $this->field,
			'result' => $result,
            'select' => $this->_select($this->models('site/page')->get_data(), $pid, 'name=\'pid\'', L('作为顶级')),
            'myfield' => $this->field_input($field, $data, FALSE),
            'myfield2' => $this->get_cache('page-field-'.SITE_ID),
		), 'page_add.html');
	}

    /**
     * 修改
     */
    public function edit() {
		$id = (int)$this->input->get('id');
		$data = $this->models('site/page')->get($id);
        !$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
        $error = $result = NULL;
        $field = $this->get_cache('page-field-'.SITE_ID);
        $data['setting'] = string2array($data['setting']);

        // 过滤自定义字段
        if ($field && $data['setting']['nofield']) {
            foreach ($field as $i => $t) {
                if (@in_array($t['fieldname'], $data['setting']['nofield'])) {
                    unset($field[$i]);
                }
            }
        }

		if (IS_POST) {
            $my = $field ? array_merge($this->field, $field) : $this->field;
            $post = $this->validate_filter($my);
            if (isset($post['error'])) {
                $error = $post;
            } else {
                $post[1]['pid'] = $this->input->post('pid');
                $post[1]['pid'] = $post[1]['pid'] == $id ? $data['pid'] : $post[1]['pid'];
                $post[1]['show'] = intval($post[1]['show']);
                $post[1]['urlrule'] = $this->input->post('urlrule');
                $post[1]['getchild'] = intval($post[1]['getchild']);
                $post[1]['displayorder'] = $data['displayorder'];
                $page = $this->models('site/page')->edit($id, $post[1]);
                if (is_numeric($page)) {
                    $this->models('site/page')->syn($this->input->post('synid'), $post[1]['urlrule']);
                    $this->attachment_handle($this->uid, $this->models('site/page')->tablename.'-'.$page, $my);
                    $this->models('site/page')->cache(SITE_ID);
                    $this->system_log('修改页面【#'.$page.'】'.$post[1]['name']); // 记录日志
                    $this->admin_msg(L('操作成功，更新缓存生效'), dr_url(APP_DIR.'/page/edit', array('id' => $id)), 1);
                } else {
                    $error = array('msg' => $page);
                }
            }
		}

		$page = $this->models('site/page')->get_data();

		$this->render(array(
			'id' => $id,
			'data' => $data,
			'page' => (int)$this->input->post('page'),
            'menu' => $this->get_menu_v3(array(
                L($data['name']) => array(dr_url('page/edit', array('id' => $id)), 'adn'),
			)),
            'error' => $error,
			'field' => $this->field,
			'result' => $result,
            'select' => $this->_select($page, $data['pid'], 'name=\'pid\'', L('作为顶级')),
            'myfile' => is_file(APPPATH.'views/admin/page_'.SITE_ID.'_'.$id.'.html') ? 'page_'.SITE_ID.'_'.$id.'.html' : '',
            'myfield' => $this->field_input($field, $data, FALSE),
            'myfield2' => $this->get_cache('page-field-'.SITE_ID),
			'select_syn' => $this->_select($page, 0, 'id="dr_synid" name=\'synid[]\' multiple style="height:200px;"', '')
		), 'page_add.html');
    }
    
    public function del($ids){
		if (!$ids) {
            return NULL;
        }
		
		// 筛选栏目id
		$catid = '';
		foreach ($ids as $id) {
			$data = $this->db->select('childids')->where('id', $id)->get($this->models('site/page')->tablename)->row_array();
			$catid.= ','.$data['childids'];
		}
		$catid = explode(',', $catid);
		$catid = array_flip(array_flip($catid));
		
		// 逐一删除
		foreach ($catid as $id) {
			// 删除主表
			$this->db->where('id', $id)->delete($this->models('site/page')->tablename);
			// 删除附件
			$this->models('system/attachment')->delete_for_table($this->models('site/page')->tablename.'-'.$id);
		}
		
		$this->models('site/page')->cache(SITE_ID);

        $this->system_log('删除页面【#'.@implode(',', $ids).'】'); // 记录日志
    }
    
    /**
     * 缓存
     */
    public function cache() {
        $this->models('site/page')->cache(isset($_GET['site']) ? (int)$_GET['site'] : SITE_ID);
        $this->load->helper('file');
        delete_files(WEBPATH.'cache/page/');
        (int)$_GET['admin'] or $this->admin_msg(L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 1);
    }

    /**
     * 操作
     */
    public function option() {
        if ($this->is_auth('admin/page/edit') && IS_ADMIN) {
            $id = (int)$this->input->get('id');
            $data = $this->models('site/page')->get($id);
            if ($this->input->get('op') == 'show') {
                $value = $data['show'] ? 0 : 1;
                $this->db->where('id', $id)->update(
                    $this->models('site/page')->tablename,
                    array('show' => $value)
                );
                $this->system_log('修改网站【#'.SITE_ID.'】页面【'.$data['name'].'#'.$id.'】显示状态为：'.($value ? '可见' : '隐藏')); // 记录日志
            } elseif ($this->input->get('op') == 'cache') {
                $data['setting'] = string2array($data['setting']);
                $data['setting']['nocache'] = $value = $data['setting']['nocache'] ? 0 : 1;
                $this->db->where('id', $id)->update(
                    $this->models('site/page')->tablename,
                    array('setting' => array2string($data['setting']))
                );
                $this->system_log('修改网站【#'.SITE_ID.'】页面【'.$data['name'].'#'.$id.'】状态为：'.($value ? '关闭静态缓存' : '开启静态缓存')); 
            }

            $this->models('site/page')->cache(SITE_ID);
            $this->load->helper('file');
            delete_files(WEBPATH.'cache/page/');
            $this->admin_msg(L('操作成功，正在刷新...'), dr_url(APP_DIR.'/page/index'), 1);
        } else {
            $this->admin_msg(L('您无权限操作'));
        }
    } 
    
	/**
	 * 上级选择
	 *
	 * @param array			$data		数据
	 * @param intval/array	$id			被选中的ID
	 * @param string		$str		属性
	 * @param string		$default	默认选项
	 * @return string
	 */
	private function _select($data, $id = 0, $str = '', $default = ' -- ') {
	
		$tree = array();
		$string = '<select class="form-control" '.$str.'>';

        $default && $string.= "<option value='0'>$default</option>";
		
		if (is_array($data)) {
			foreach($data as $t) {
				$t['selected'] = ''; // 选中操作
				if (is_array($id)) {
					$t['selected'] = in_array($t['id'], $id) ? 'selected' : '';
				} elseif(is_numeric($id)) {
					$t['selected'] = $id == $t['id'] ? 'selected' : '';
				}
				$tree[$t['id']] = $t;
			}
		}
		
		$str = "<option value='\$id' \$selected>\$spacer \$name</option>";
		$str2 = "<optgroup label='\$spacer \$name'></optgroup>";
		
		$this->load->library('dtree');
		$this->dtree->init($tree);
		
		$string.= $this->dtree->get_tree_category(0, $str, $str2);
		$string.= '</select>';
		
		return $string;
	}
}