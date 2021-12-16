<?php

class Content extends M_Controller {

    public $field; // 自定义字段+含系统字段
    protected $sysfield; // 系统字段

    /**
     * 构造函数
     */

    public function __construct() {
        parent::__construct();
        $this->load->library('Dfield', array(APP_DIR));
        $this->sysfield = array(
            'author' => array(
                'name' => L('录入作者'),
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'author',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                        'value' => $this->admin['username']
                    ),
                    'validate' => array(
                        'tips' => L('填写录入者的会员名称'),
                        'check' => '_check_member',
                        'required' => 1,
                        'formattr' => ' ondblclick="dr_dialog_member(\'author\')" ',
                    )
                )
            ),
            'inputtime' => array(
                'name' => L('录入时间'),
                'ismain' => 1,
                'fieldtype' => 'Date',
                'fieldname' => 'inputtime',
                'setting' => array(
                    'option' => array(
                        'width' => 200
                    ),
                    'validate' => array(
                        'required' => 1,
                        'formattr' => '',
                    )
                )
            ),
            'updatetime' => array(
                'name' => L('更新时间'),
                'ismain' => 1,
                'fieldtype' => 'Date',
                'fieldname' => 'updatetime',
                'setting' => array(
                    'option' => array(
                        'width' => 200
                    ),
                    'validate' => array(
                        'required' => 1,
                        'formattr' => '',
                    )
                )
            ),
            'inputip' => array(
                'name' => L('客户端IP'),
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'inputip',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                        'value' => $this->input->ip_address()
                    ),
                    'validate' => array(
                        'formattr' => ' ondblclick="dr_dialog_ip(\'inputip\')" ',
                    )
                )
            ),
            'hits' => array(
                'name' => L('阅读量'),
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'hits',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                        'value' => 0,
                    )
                )
            ),
            'status' => array(
                'name' => L('状态'),
                'ismain' => 1,
                'fieldname' => 'status',
                'fieldtype' => 'Radio',
                'setting' => array(
                    'option' => array(
                        'value' => 9,
                        'options' => L('正常').'|9'.chr(13).L('关闭').'|10',
                    ),
                    'validate' => array(
                        'tips' => L('关闭状态起内容暂存作用，除自己和管理员以外的人均无法访问'),
                    )
                )
            ),
        );
    }

    // 获取可用字段
    public function _get_field($catid = 0) {

        // 主字段
        $field = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'field');

        // 指定栏目字段
        $category = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category', $catid, 'field');
        if ($category) {
            $tmp = $field;
            $field = array();
            if (isset($tmp['title'])) {
                $field['title'] = $tmp['title'];
                unset($tmp['title']);
                $field = array_merge($field, $category, $tmp);
            } else {
                $field = array_merge($category, $tmp);
            }
        }
        // 筛选出右边显示的字段
        if (!$field) {
            return array();
        }
        foreach ($field as $i => $t) {
            if ($t['setting']['is_right']) {
                $next[$i] = $field[$i];
                $this->sysfield = array_merge($next, $this->sysfield);
                unset($field[$i]);
            }
        }

        return $field;
    }

    /**
     * 管理
     */
    public function index() {

        if (IS_POST && !$this->input->post('search')) {
            $ids = $this->input->post('ids');
            $action = $this->input->post('action');
            !$ids && ($action == 'html' ? $this->admin_msg(L('您还没有选择呢')) : $this->msg(0, L('您还没有选择呢')));
            switch ($action) {
                case 'del':
                    $ok = $no = 0;
                    foreach ($ids as $id) {
                        $data = $this->db->where('id', (int)$id)->select('id,catid,tableid')->get($this->models('module/content')->prefix)->row_array();
                        if ($data) {
                            if (!$this->is_category_auth($data['catid'], 'del')) {
                                $no++;
                            } else {
                                $ok++;
                                $this->models('module/content')->delete_for_id((int)$data['id'], (int)$data['tableid']);
                            }
                        }
                    }
                    $this->system_log('删除站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】'); // 记录日志
                    $this->msg($no ? 0 : 1, $no ? L('删除成功：%s，失败：%s', $ok, $no) : L('操作成功，正在刷新...'));
                    break;
                case 'recycle':
                    $ok = $no = 0;
                    foreach ($ids as $id) {
                        $data = $this->db->where('id', (int)$id)->select('id,catid,tableid')->get($this->models('module/content')->prefix)->row_array();
                        if ($data) {
                            if (!$this->is_category_auth($data['catid'], 'del')) {
                                $no++;
                            } else {
                                $ok++;
                                $this->models('module/content')->recycle_for_id((int)$data['id'], (int)$data['tableid']);
                            }
                        }
                    }
                    $this->system_log('回收站 站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】'); // 记录日志
                    $this->msg($no ? 0 : 1, $no ? L('回收成功：%s，失败：%s', $ok, $no) : L('操作成功，正在刷新...'));
                    break;
                case 'order':
                    $_data = $this->input->post('data');
                    foreach ($ids as $id) {
                        $this->db->where('id', $id)->update($this->models('module/content')->prefix, $_data[$id]);
                    }
                    $this->system_log('排序站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】'); // 记录日志
                    $this->msg(1, L('操作成功，正在刷新...'));
                    break;
                case 'move':
                    $catid = $this->input->post('catid');
                    if (!$catid) {
                        $this->msg(0, L('目标栏目id不存在'));
                    } elseif (!$this->is_auth(APP_DIR.'/admin/content/edit')
                        || !$this->is_category_auth($catid, 'edit')) {
                        $this->msg(0, L('您无权限操作'));
                    }
                    $this->models('module/content')->move($ids, $catid);
                    $this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】更改栏目#'.$catid); // 记录日志
                    $this->msg(1, L('操作成功，正在刷新...'));
                    break;
                case 'flag':
                    !$this->is_auth(APP_DIR.'/admin/content/edit') &&  $this->msg(0, L('您无权限操作'));
                    $flag = $this->input->get('flag');
                    $this->models('module/content')->flag($ids, -$flag);
                    $this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】移出推荐位#'.$flag); // 记录日志
                    $this->msg(1, L('操作成功，正在刷新...'));
                    break;
                case 'html':
                    // 生成权限文件
                    !dr_html_auth(1) && $this->admin_msg(L('/data/views/ 无法写入文件'));
                    $url = ADMIN_URL.'index.php?s='.APP_DIR.'&c=show&m=html&page=1&type=html&value='.implode(',', $ids).'&total='.count($ids);
                    redirect($url, 'refresh');
                    break;
                default :
                    $this->msg(0, L('操作成功，正在刷新...'));
                    break;
            }
        }

        // 重置页数和统计
        IS_POST && $_GET['page'] = $_GET['total'] = 0;

        // 筛选结果
        $param = $this->input->get(NULL, TRUE);
        $catid = isset($param['catid']) ? (int)$param['catid'] : 0;
        unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);

        // 按字段的搜索
        $this->field = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'field');
        $this->field['author'] = array('name' => L('录入作者'), 'ismain' => 1, 'fieldname' => 'author');

        // 数据库中分页查询
        list($list, $param) = $this->models('module/content')->limit_page($param, max((int)$_GET['page'], 1), (int)$_GET['total']);

        $meta_name = L('已通过的内容');

        // 统计推荐位
        $flag = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'setting', 'flag');
        if ($flag) {
            foreach ($flag as $id => $t) {
                if ($t['name'] && $id) {
                    $flag[$id]['url'] =  $this->duri->uri2url($catid ?
                        APP_DIR.'/admin/content/index/flag/'.$id.'/catid/'.$catid :
                        APP_DIR.'/admin/content/index/flag/'.$id);
                    isset($param['flag']) && $param['flag'] && $param['flag'] == $id && $meta_name = L($t['name']);
                } else {
					unset($flag[$id]);
				}
            }
        }

        // 模块应用嵌入
        $app = array();
        $data = $this->get_cache('app');
        if ($data) {
            foreach ($data as $dir) {
                $a = $this->get_cache('app-'.$dir);
                if (isset($a['module'][APP_DIR]) && isset($a['related']) && $a['related']) {
                    $app[] = array(
                        'url' => dr_url($dir.'/content/index'),
                        'name' => $a['name'],
                        'field' => $a['related'],
                    );
                }
            }
        }

        // 模块表单嵌入
        $form = array();
        $data = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'form');
        if ($data) {
            foreach ($data as $t) {
                $form[] = array(
                    'url' => dr_url(APP_DIR.'/form_'.$t['table'].'/index'),
                    'name' => $t['name'],
                    'field' => $t['table'].'_total',
                );
            }
        }

        // 存储当前页URL
        $this->_set_back_url(APP_DIR.'/content/index', $param);

        $this->template->assign(array(
            'app' => $app,
            'form' => $form,
            'list' => $list,
            'flag' => isset($param['flag']) ? $param['flag'] : '',
            'flags' => $flag,
            'param' => $param,
            'meta_name' => $meta_name,
            'field' => $this->field,
            'pages' => $this->get_pagination(dr_url(APP_DIR.'/content/index', $param), $param['total']),
            'extend' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'extend'),
            'select' => $this->select_category($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category'), 0, 'id=\'move_id\' name=\'catid\'', ' --- ', 1, 1),
            'select2' => $this->select_category($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category'), $catid, ' name=\'data[catid]\'', ' --- ', 0, 1),
            'html_url' => 'index.php?s='.APP_DIR.'&',
            'post_url' => $this->duri->uri2url($catid ? APP_DIR.'/admin/content/add/catid/'.$catid : APP_DIR.'/admin/content/add'),
            'list_url' =>  $this->duri->uri2url($catid ? APP_DIR.'/admin/content/index/catid/'.$catid : APP_DIR.'/admin/content/index'),
        ));
        $this->template->display('content_index.html');
    }

    /**
     * 添加
     */
    public function add() {

        $did = (int)$this->input->get('did');
        $catid = (int)$this->input->get('catid');

        $error = $data = array();
        $select = '';
        $category = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category');

        // 提交保存操作------
        if (IS_POST) {
            $cid = (int)$this->input->post('catid');
            $syncatid = $this->input->post('syncatid');
            // 判断栏目权限
            $cid != $catid && !$this->is_category_auth($catid, 'add') && $this->admin_msg(L('您无权限操作'));
            $catid = $cid;
            $cat = $cid != $catid ? $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category', $catid) : $category[$catid];
            unset($cid);
            // 设置uid便于校验处理
            $uid = $this->input->post('data[author]') ? get_member_id($this->input->post('data[author]')) : 0;
            $_POST['data']['id'] = 0;
            $_POST['data']['uid'] = $uid;
            // 获取字段
            $myfield = array_merge($this->_get_field($catid), $this->sysfield);
            $data = $this->validate_filter($myfield);
            // 返回错误
            if (isset($data['error'])) {
                $error = $data;
                $data = $this->input->post('data', TRUE);
            } elseif (!$catid) {
                $data = $this->input->post('data', TRUE);
                $error = array('error' => 'catid', 'msg' => L('还没有选择栏目'));
            } else {
                $data[1]['uid'] = $uid;
                $data[1]['catid'] = $catid;
                // 保存为草稿
                if ($this->input->post('action') == 'draft') {
                    $this->clear_cache('save_'.APP_DIR.'_'.$this->uid);
                    $id = $this->models('module/content')->save_draft($did, $data, 0);
                    $this->attachment_handle($this->uid, $this->models('module/content')->prefix.'_draft-'.$id, $myfield);
                    $this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.$id.'】保存草稿'); // 记录日志
                    $this->admin_msg(L('已保存到我的草稿箱中'), dr_url(APP_DIR.'/content/draft/'), 1);
                    exit;
                }
                // 数据来至草稿时更新时间
                $did && $data[1]['updatetime'] = $data[1]['inputtime'] = SYS_TIME;
                // 正常发布
                if (($id = $this->models('module/content')->add($data, $syncatid)) != FALSE) {
					// 执行提交后的脚本
					$this->validate_table($id, $myfield, $data);
                    // 发布草稿时删除草稿数据
                    $did && $this->models('module/content')->delete_draft($did, 'cid=0 and eid=0')
                        ? $this->attachment_replace_draft($did, $id, 0, $this->models('module/content')->prefix)
                        : $this->clear_cache('save_'.APP_DIR.'_'.$this->uid);
                    $mark = $this->models('module/content')->prefix.'-'.$id;
                    $member = $this->models('member')->get_base_member($uid);
                    // 操作成功处理附件

                    $this->attachment_handle($data[1]['uid'], $mark, $myfield);
                    // 处理推荐位
                    $update = $this->input->post('flag');
                    if ($update) {
                        foreach ($update as $i) {
                            $this->db->insert(SITE_ID.'_'.APP_DIR.'_flag', array(
                                'id' => $id,
                                'uid' => $uid,
                                'flag' => $i,
                                'catid' => $catid
                            ));
                        }
                    }
                    $this->system_log('添加 站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.$id.'】'); // 记录日志
                    // 是否创建静态页面链接
                    $create = $cat['setting']['html'] && $data[1]['status'] == 9 ? dr_module_create_show_file($id, 1) : '';
                    if ($this->input->post('action') == 'back') {
                        $this->admin_msg(
                            L('操作成功，正在刷新...').
                            ($create ? "<script src='".$create."'></script>".dr_module_create_list_file($catid) : ''),
                            $this->_get_back_url(APP_DIR.'/content/index'),
                            1,
                            1
                        );
                    } else {
                        unset($data);
                        $error = array('msg' => dr_lang('发布成功'), 'status'=>1);
                    }
                }
            }
            $data['syncatid'] = $syncatid;
            $select = $this->select_category($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category'), $catid, 'id=\'dr_catid\' name=\'catid\' onChange="show_category_field(this.value)"', '', 1, 1);
        } else {
            if ($did) {
                $temp = $this->models('module/content')->get_draft($did);
                $temp['draft']['cid'] == 0 && $temp['draft']['eid'] == 0 && $data = $temp;
            } else {
                $data = $this->get_cache_data('save_'.APP_DIR.'_'.$this->uid);
            }
            $catid = $data['catid'] ? $data['catid'] : $catid;
            // 栏目id不存在时就去第一个可用栏目为catid
            if (!$catid) {
                list($select, $catid) = $this->select_category($category, 0, 'id=\'dr_catid\' name=\'catid\' onChange="show_category_field(this.value)"', '', 1, 1, 1);
            } else {
                $select = $this->select_category($category, $catid, 'id=\'dr_catid\' name=\'catid\' onChange="show_category_field(this.value)"', '', 1, 1);
            }


        }

        // 判断栏目权限
        !$this->is_category_auth($catid, 'add') && $this->admin_msg(L('您无权限操作'));

        $myfield = $this->_get_field($catid);
        define('MODULE_CATID', $catid);

        $this->template->assign(array(
            'did' => $did,
            'data' => $data,
            'flag' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'setting', 'flag'),
            'menu' => $this->get_menu_v3(array(
                L('返回') => array($this->_get_back_url(APP_DIR.'/content/index'), 'reply'),
                L('发布') => array(APP_DIR.'/admin/content/add', 'plus')
            )),
            'catid' => $catid,
            'error' => $error,
            'create' => $create,
            'myflag' => $this->input->post('flag'),
            'select' => $select,
            'myfield' => $this->new_field_input($myfield, $data, TRUE),
            'sysfield' => $this->new_field_input($this->sysfield, $data, TRUE, '', '<div class="form-group" id="dr_row_{name}"><label class="col-sm-12">{text}</label><div class="col-sm-12">{value}</div></div>'),
            'draft_url' => dr_url(APP_DIR.'/content/add'),
            'draft_list' => $this->models('module/content')->get_draft_list('cid=0 and eid=0'),
            'is_category_field' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category_field'),
        ));
        $this->template->display('content_add.html');
    }

    /**
     * 修改
     */
    public function edit() {
        $id = (int)$this->input->get('id');
        $did = (int)$this->input->get('did');
        $cid = (int)$this->input->get('catid');
        $data = $this->models('module/content')->get($id);
        $catid = $cid ? $cid : $data['catid'];
        $error = $myflag = array();
        unset($cid);

        // 数据判断
        !$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));

        if ($data['link_id'] > 0) {
            $data = $this->models('module/content')->get($data['link_id']);
            !$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
        }

        $lid = $data['link_id'] > 0 ? $data['id'] : $id;

        // 栏目缓存
        $category = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category');

        if ($flag = $this->db->where('id', $id)->get(SITE_ID.'_'.APP_DIR.'_flag')->result_array()) {
            foreach ($flag as $t) {
                $myflag[] = $t['flag'];
            }
        }
        unset($flag);

        if (IS_POST) {
            $cid = (int)$this->input->post('catid');
            // 判断栏目权限
            $cid != $catid && !$this->is_category_auth($catid, 'add') && $this->admin_msg(L('您无权限操作'));
            $catid = $cid;
            $cat = $cid != $catid ? $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category', $catid) : $category[$catid];
            unset($cid);
            // 设置uid便于校验处理
            $uid = $this->input->post('data[author]') ? get_member_id($this->input->post('data[author]')) : 0;
            $_POST['data']['id'] = $id;
            $_POST['data']['uid'] = $uid;
            // 获取字段
            $myfield = array_merge($this->_get_field($catid), $this->sysfield);
            $post = $this->validate_filter($myfield, $data);
            if (isset($post['error'])) {
                $error = $post;
            } elseif (!$catid) {
                $error = array('error' => 'catid', 'msg' => L('还没有选择栏目'));
            } else {
                $post[1]['uid'] = $uid;
                $post[1]['catid'] = $catid;
                $post[1]['updatetime'] = $this->input->post('no_time') ? $data['updatetime'] : $post[1]['updatetime'];
                // 保存为草稿
                if ($this->input->post('action') == 'draft') {
                    $post[1]['id'] = $post[0]['id'] = $lid;
                    $id = $this->models('module/content')->save_draft($did, $post, 0);
                    $this->attachment_handle($this->uid, $this->models('module/content')->prefix.'_draft-'.$id, $myfield);
                    $this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.$lid.'】保存草稿'); // 记录日志
                    $this->admin_msg(L('已保存到我的草稿箱中'), dr_url(APP_DIR.'/content/draft/'), 1);
                    exit;
                }
                // 正常保存
                $this->models('module/content')->edit($data, $post, $lid);
				// 执行提交后的脚本
				$this->validate_table($id, $myfield, $post);
                // 发布草稿时删除草稿数据
                $did && $this->models('module/content')->delete_draft($did, 'cid='.$lid.' and eid=0') && $this->attachment_replace_draft($did, $lid, 0, $this->models('module/content')->prefix);
                // 操作成功处理附件
                $this->attachment_handle($post[1]['uid'], $this->models('module/content')->prefix.'-'.$lid, $myfield, $data);
                // 处理推荐位
                $update = $this->input->post('flag');
                if ($update !== $myflag) {
                    // 删除旧的
                    $myflag && $this->db->where('id', $id)->where_in('flag', $myflag)->delete(SITE_ID.'_'.APP_DIR.'_flag');
                    // 增加新的
                    if ($update) {
                        foreach ($update as $i) {
                            $this->db->insert(SITE_ID.'_'.APP_DIR.'_flag', array(
                                'id' => $id,
                                'uid' => $uid,
                                'flag' => $i,
                                'catid' => $catid
                            ));
                        }
                    }
                }
                $this->system_log('修改 站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.$lid.'】'); // 记录日志
				if ($cat['setting']['html']
                    && $data['link_id'] != 0 && $post[1]['status'] == 10) {
                    // 删除生成的静态文件
					$html = $this->db->where('rid', $id)->where('type', 1)->get($this->models('module/content')->prefix.'_html')->row_array();
					if ($html) {
						$files = string2array($html['filepath']);
						if ($files) {
							foreach ($files as $file) {
								@unlink($file);
							}
						}
					}
				}
                //exit;
                $this->admin_msg(
                    L('操作成功，正在刷新...') .
                    ($cat['setting']['html'] && $post[1]['status'] == 9 ? dr_module_create_show_file($lid).dr_module_create_list_file($catid) : ''),
                    $this->_get_back_url(APP_DIR.'/content/index'),
                    1,
                    1
                );
            }
			$data = $this->input->post('data', TRUE);
            $myflag = $this->input->post('flag');
        } else {
            if ($did) {
                $temp = $this->models('module/content')->get_draft($did);
                if ($temp['draft']['cid'] == $data['id'] && $temp['draft']['eid'] == 0) {
                    $temp['id'] = $id;
                    $data = $temp;
                    $catid = $temp['catid'] ? $temp['catid'] : $catid;
                }
            }
        }

        // 判断栏目权限
        !$this->is_category_auth($catid, 'edit') && $this->admin_msg(L('您无权限操作'));

        // 可用字段
        $myfield = $this->_get_field($catid);
        define('MODULE_CATID', $catid);

        $data['updatetime'] = SYS_TIME;
        $this->template->assign(array(
            'did' => $did,
            'data' => $data,
            'flag' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'setting', 'flag'),
            'menu' => $this->get_menu_v3(array(
                L('返回') => array($this->_get_back_url(APP_DIR.'/content/index'), 'reply'),
                L('发布') => array(APP_DIR.'/admin/content/add/catid/'.$catid, 'plus')
            )),
            'catid' => $catid,
            'error' => $error,
            'myflag' => $myflag,
            'select' => $this->select_category($category, $catid, 'id=\'dr_catid\' name=\'catid\' onChange="show_category_field(this.value)"', '', 1, 1),
            'myfield' => $this->new_field_input($myfield, $data, TRUE),
            'sysfield' => $this->new_field_input($this->sysfield, $data, TRUE, '', '<div class="form-group" id="dr_row_{name}"><label class="col-sm-12">{text}</label><div class="col-sm-12">{value}</div></div>'),
            'draft_url' => dr_url(APP_DIR.'/content/edit', array('id' => $id)),
            'draft_list' => $this->models('module/content')->get_draft_list('cid='.$id.' and eid=0'),
            'is_category_field' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category_field'),
        ));
        $this->template->display('content_add.html');
    }
	
	/*===========草稿部分===========*/

	/**
     * 草稿箱管理
     */
    public function draft() {

        $table = $this->models('module/content')->prefix.'_draft';

        if (IS_POST) {
            $ids = $this->input->post('ids');
            !$ids && $this->msg(0, L('您还没有选择呢'));
            foreach ($ids as $id) {
                // 删除草稿记录
                if ($this->db->where('id', $id)->where('uid', $this->uid)->get($table)->row_array()) {
                    $this->db->where('id', $id)->delete($table);
                    // 删除表对应的附件
                    $this->models('system/attachment')->delete_for_table($table.'-'.$id);
                }
            }
            $this->system_log('删除站点【#'.SITE_ID.'】模块【'.APP_DIR.'】草稿内容【#'.@implode(',', $ids).'】'); // 记录日志
            $this->msg(1, L('操作成功，正在刷新...'));
        }

        $page = max(1, (int) $this->input->get('page'));
        $total = $_GET['total'] ? intval($_GET['total']) : $this->db->where('uid', $this->uid)->count_all_results($table);
        $result = $total ? $this->db
                                ->where('uid', $this->uid)
                                ->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1))
                                ->order_by('inputtime DESC, id DESC')
                                ->get($table)
                                ->result_array() : array();

        // 存储当前页URL
        $this->_set_back_url(APP_DIR.'/content/index', '', APP_DIR.'/content/draft');
        
        $this->template->assign(array(
            'menu' => $this->get_menu_v3(array(
                L('草稿箱') =>  array(APP_DIR.'/admin/content/draft', 'edit'),
                L('发布') => array(APP_DIR.'/admin/content/add', 'plus')
            )),
            'list' => $result,
            'total' => $total,
            'pages' => $this->get_pagination(dr_url(APP_DIR.'/content/draft'), $total)
        ));
        $this->template->display('content_draft.html');
    }


	/*===========回收站部分===========*/

	/**
     * 回收站管理
     */
    public function recycle() {


        $table = $this->models('module/content')->prefix.'_recycle';

        if (IS_POST) {
            $ids = $this->input->post('ids');
            !$ids && $this->msg(0, L('您还没有选择呢'));
            if ($this->input->post('action') == 'del') {
                foreach ($ids as $id) {
                    $data = $this->db->where('id', (int)$id)->get($this->models('module/content')->prefix.'_recycle')->row_array();
                    if ($data) {
                        $c = string2array($data['content']);
                        $this->models('module/content')->delete_for_id((int)$c['id'], (int)$c['tableid']);
                    }
                }
                $this->system_log('删除站点【#'.SITE_ID.'】模块【'.APP_DIR.'】回收站内容【#'.@implode(',', $ids).'】'); // 记录日志
                $this->msg(1, L('操作成功，正在刷新...'));
            } else {
                foreach ($ids as $id) {
                    $data = $this->db->where('id', (int)$id)->get($this->models('module/content')->prefix.'_recycle')->row_array();
                    if ($data) {
                        $c = string2array($data['content']);
                        $this->models('module/content')->recycle_add((int)$c['id'], $c);
                    }
                }
                $this->system_log('恢复站点【#'.SITE_ID.'】模块【'.APP_DIR.'】回收站内容【#'.@implode(',', $ids).'】'); // 记录日志
                $this->msg(1, L('操作成功，正在刷新...'));
            }
        }

        $page = max(1, (int) $this->input->get('page'));
        $total = $_GET['total'] ? intval($_GET['total']) : $this->db->count_all_results($table);
        $result = $total ? $this->db
            ->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1))
            ->order_by('inputtime DESC, id DESC')
            ->get($table)
            ->result_array() : array();

        // 存储当前页URL
        $this->_set_back_url(APP_DIR.'/content/index', '', APP_DIR.'/content/recycle');

        $this->template->assign(array(
            'menu' => $this->get_menu_v3(array(
                L('回收站') =>  array(APP_DIR.'/admin/content/recycle', 'trash'),
                L('发布') => array(APP_DIR.'/admin/content/add', 'plus')
            )),
            'list' => $result,
            'total' => $total,
            'pages' => $this->get_pagination(dr_url(APP_DIR.'/content/recycle'), $total)
        ));
        $this->template->display('content_recycle.html');
    }


    /**
     * 修改回收站文档
     */
    public function recycleedit() {

        $id = (int)$this->input->get('id');
        $data = $this->models('module/content')->get_recycle($id);
        $error = array();

        // 数据验证
        !$data && $this->admin_msg(L('数据被删除或者查询不存在'));

        $catid = $data['catid'];
        $category = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category');

        define('MODULE_RECYCLE', 1);

        // 可用字段
        $myfield = $this->_get_field($catid);
        unset($myfield['status']);

        $backuri = APP_DIR.'/admin/content/recycle/';

        $data['status'] = 10;
        $this->template->assign(array(
            'data' => $data,
            'menu' => $this->get_menu_v3(array(
                L('返回') => array($backuri, 'reply'),
                L('查看') => array(APP_DIR.'/admin/content/recycleedit/id/'.$data['id'], 'edit')
            )),
            'catid' => $catid,
            'error' => $error,
            'select' => $this->select_category($category, $catid, 'id=\'dr_catid\' name=\'catid\' onChange="show_category_field(this.value)"', '', 1, 1),
            'backurl' => $backuri,
            'myfield' => $this->new_field_input($myfield, $data, TRUE),
            'sysfield' => $this->new_field_input($this->sysfield, $data, TRUE, '', '<div class="form-group" id="dr_row_{name}"><label class="col-sm-12">{text}</label><div class="col-sm-12">{value}</div></div>'),
            'is_category_field' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category_field'),
        ));
        $this->template->display('content_edit.html');
    }
	
	/*===========相关功能===========*/

    /**
     * 生成静态
     */
    public function html() {
        redirect(ADMIN_URL.dr_url('html/index', array('dir' => APP_DIR)), 'refresh');
        exit;

    }

    /**
     * 清除静态文件
     */
    public function clear() {
        redirect(ADMIN_URL.dr_url('html/index', array('dir' => APP_DIR)), 'refresh');
        exit;

    }

    // 推送执行界面
    public function ts_ajax() {

        if ($this->input->get('ispost')) {
            $ids = $this->input->post('ids');
            !$ids && $this->msg(0, L('您还没有选择呢'));
            !$this->is_auth(APP_DIR.'/admin/content/edit') && $this->msg(0, L('您无权限操作'));
            if ($this->input->get('tab') == 1) {
                // 推荐位推送
                $flag = array();
                $value = @explode(',', $this->input->get('value'));
                !$value && $this->msg(0, L('您还没有选择呢'));
                // 执行推荐位
                foreach ($value as $t) {
                    if ($t) {
                        $flag[] = $t;
                        $this->models('module/content')->flag($ids, $t);
                        $this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】设置推荐位#'.$t); // 记录日志
                    }
                }
                // 再次验证
                !$flag && $this->msg(0, L('您还没有选择呢'));
                $this->msg(1, L('操作成功，正在刷新...'));
            } elseif ($this->input->get('tab') == 0) {
				// 推送栏目
				$value = @explode(',', $this->input->get('value'));
                !$value && $this->msg(0, L('您还没有选择呢'));
				 // 执行同步指定栏目
				foreach ($ids as $id) {
					$this->db->where('id', (int)$id)->update($this->models('module/content')->prefix, array('link_id' => -1)); // 更改状态
					$data = $this->db->where('id', (int)$id)->get($this->models('module/content')->prefix)->row_array(); // 获取数据
					if (!$data) {
						continue;
					}
					foreach ($value as $catid) {
						if ($catid && $catid != $data['catid']) {
							// 插入到同步栏目中
							$new[1] = $data;
							$new[1]['catid'] = $catid;
							$new[1]['link_id'] = $id;
							$new[1]['tableid'] = 0;
							$new[1]['id'] = $this->models('module/content')->index($new);
							$this->db->replace($this->models('module/content')->prefix, $new[1]); // 创建主表
							$this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.@implode(',', $ids).'】同步到栏目#'.$catid); // 记录日志
						}
					}
				}
                $this->msg(1, L('操作成功，正在刷新...'));
			}	
        } else {
            $this->template->assign(array(
                'flag' => $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'setting', 'flag'),
                'select' => $this->select_category($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category'), 0, 'id="dr_synid" name=\'catid[]\' multiple style="width:200px;height:250px;"', '', 1, 1),
            ));
            $this->template->display('content_ts.html');exit;
        }
    }
	
	// 文档状态设定
	public function status() {
		
		$id = (int)$this->input->get('id');
        $data = $this->models('module/content')->get($id);
        !$data && $this->msg(0, L('对不起，数据被删除或者查询不存在'));
		
		// 删除缓存
        $this->clear_cache('show'.APP_DIR.SITE_ID.$id);
        $this->clear_cache('mshow'.APP_DIR.SITE_ID.$id);
		
		if ($data['status'] == 10) {
			$this->db->where('id', $id)->update($this->models('module/content')->prefix, array('status' => 9));
			$this->db->where('id', $id)->update($this->models('module/content')->prefix.'_index', array('status' => 9));
            // 调用方法状态更改方法
            $data['status'] = 9;
            // $this->models('module/content')->_update_status($data);
            $this->system_log('修改 站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.$id.'】状态为【正常】'); // 记录日志
			$this->msg(1, L('操作成功，正在刷新...'), $data['catid']);
		} else {
			// 删除生成的文件
			if ($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category', $data['catid'], 'setting', 'html')
                && strpos($data['url'], 'index.php') === FALSE) {
				$html = $this->db->where('rid', $id)->where('type', 1)->get($this->models('module/content')->prefix.'_html')->row_array();
				if ($html) {
					$files = string2array($html['filepath']);
					if ($files) {
						foreach ($files as $file) {
							@unlink($file);
						}
					}
				}
			}
			$this->db->where('id', $id)->update($this->models('module/content')->prefix, array('status' => 10));
			$this->db->where('id', $id)->update($this->models('module/content')->prefix.'_index', array('status' => 10));
            // 调用方法状态更改方法
            $data['status'] = 10;
            // $this->models('module/content')->_update_status($data);
            $this->system_log('修改 站点【#'.SITE_ID.'】模块【'.APP_DIR.'】内容【#'.$id.'】状态为【关闭】'); // 记录日志
			$this->msg(1, L('操作成功，正在刷新...'), 0);
		}
		
	}

    // 跳转
    public function content() {
        redirect(ADMIN_URL.dr_url(APP_DIR.'/content/index'), 'refresh');
        exit;
    }

    // 同步栏目
    public function syncat_ajax() {

        $cat = array();
        $ids = @explode('|', $this->input->get('ids'));
        $cache = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category');
        foreach ($cache as $t) {
            if (!$t['child']) {
                $cat[$t['id']] = array(
                    'id' => $t['id'],
                    'name' => $t['name'],
                    'cname' => dr_catpos($t['id'], ' > ', FALSE),
                );
            }
        }

        $this->template->assign(array(
            'cat' => $cat,
            'ids' => $ids,
        ));
        $this->template->display('content_syncat.html');exit;
    }

    /**
     * 更新URL 兼容处理
     */
    public function url() {

        $cfile = SITE_ID.APP_DIR.$this->uid.$this->input->ip_address().'_content_url';

        if (IS_POST) {
            $catid = $this->input->post('catid');
            $query = $this->db;
            if (count($catid) > 1 || $catid[0]) {
                $query->where_in('catid', $catid);
                count($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category')) == count($catid) && $catid = 0;
            } else {
                $catid = 0;
            }
            // 统计数量
            $total = $query->count_all_results($this->models('module/content')->prefix.'_index');
            $this->cache->file->save($cfile, array('catid' => $catid, 'total' => $total), 10000);
            if ($total) {
                $this->system_log('站点【#'.SITE_ID.'】模块【'.APP_DIR.'】更新URL地址#'.$total); // 记录日志
                $this->msg(L('可更新内容%s条，正在准备执行...', $total), dr_url(APP_DIR.'/content/url', array('todo' => 1)), 2);
            } else {
                $this->msg(L('抱歉，没有找到可更新的内容'));
            }
        }

        // 处理url
        if ($this->input->get('todo')) {
            $page = max(1, (int)$this->input->get('page'));
            $psize = 100; // 每页处理的数量
            $cache = $this->cache->file->get($cfile);
            if ($cache) {
                $total = $cache['total'];
                $catid = $cache['catid'];
            } else {
                $catid = 0;
                $total = $this->db->count_all_results($this->models('module/content')->prefix);
            }
            $tpage = ceil($total / $psize); // 总页数
            if ($page > $tpage) {
                // 更新完成删除缓存
                $this->cache->file->delete($cfile);
                $this->msg(L('更新成功'), NULL, 1);
            }
            $module = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR);
            $table = $this->models('module/content')->prefix;
            $catid && $this->db->where_in('catid', $catid);
            $data = $this->db->limit($psize, $psize * ($page - 1))->order_by('id DESC')->get($table)->result_array();
            foreach ($data as $t) {
                if ($t['link_id'] && $t['link_id'] >= 0) {
                    // 同步栏目的数据
                    $i = $t['id'];
                    $t = $this->db->where('id', (int)$t['link_id'])->get($table)->row_array();
                    if (!$t) {
                        continue;
                    }
                    $url = dr_show_url($module, $t);
                    $t['id'] = $i; // 替换成当前id
                } else {
                    $url = dr_show_url($module, $t);
                }
                $this->db->update($table, array('url' => $url), 'id='.$t['id']);
                if ($module['extend']) {
                    $extend = $this->db->where('cid', (int)$t['id'])->order_by('id DESC')->get($table.'_extend')->result_array();
                    if ($extend) {
                        foreach ($extend as $e) {
                            $this->db->where('id=',(int)$e['id'])->update($table.'_extend', array(
                                'url' => dr_extend_url($module, $e)
                            ));
                        }
                    }
                }
            }
            $this->msg(L('正在执行中(%s) ... ', "$tpage/$page"), dr_url(APP_DIR.'/content/url', array('todo' => 1, 'page' => $page + 1)), 2, 0);
        } else {
            $this->template->assign(array(
                'select' => $this->select_category($this->get_cache('module-'.SITE_ID.'-'.APP_DIR, 'category'), 0, 'id="dr_synid" name=\'catid[]\' multiple style="width:200px;height:250px;"', ''),
            ));
            $this->template->display('content_url.html');
        }
    }

}
