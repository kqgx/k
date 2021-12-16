<?php

class Category extends M_Controller
{

    private $field;
    private $thumb;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->template->assign('menu', $this->get_menu_v3(array(
            L('栏目分类') => array(APP_DIR . '/admin/category/index', 'list-ul'),
            // L('自定义URL') => array(APP_DIR . '/admin/category/url', 'code-fork'),
            // L('自定义字段') => array('admin/field/index/rname/category-' . APP_DIR . '/rid/' . SITE_ID, 'plus-square'),
            L('添加') => array(APP_DIR . '/admin/category/add', 'plus'),
            L('更新缓存') => array('admin/module/cache/dir/' . APP_DIR, 'refresh'),
        )));
        $this->thumb = array(
            'thumb' => array(
                'name' => L('缩略图'),
                'ismain' => 1,
                'fieldtype' => 'File',
                'fieldname' => 'thumb',
                'setting' => array(
                    'option' => array(
                        'ext' => 'jpg,gif,png',
                        'size' => 10,
                        'resolution' => ''//分辨率
                    )
                )
            )
        );
        $this->field = array();
        $field = $this->db
            ->where('disabled', 0)
            ->where('relatedid', SITE_ID)
            ->where('relatedname', 'category-' . APP_DIR)
            ->order_by('displayorder ASC, id ASC')
            ->get('field')
            ->result_array();
        if ($field) {
            foreach ($field as $t) {
                $t['setting'] = string2array($t['setting']);
                $this->field[$t['fieldname']] = $t;
            }
            unset($field);
        }
    }

    /*
     * 列表统计显示
     */
    public function get_option($cat)
    {
        return '';
    }


    /*
     * 删除
     */
    public function delete($ids)
    {

        if (!$ids) {
            return null;
        }

        // 筛选栏目id
        $catid = '';
        $category = $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'category');
        foreach ($ids as $id) {
            $catid .= ',' . ($category[$id]['childids'] ? $category[$id]['childids'] : $id);
        }

        $catid = explode(',', trim($catid, ','));
        $catid = array_flip(array_flip($catid));
        $data = $this->db->select('tableid,id')->where_in('catid',
            $catid)->get($this->models('module/content')->prefix)->result_array();
        if ($data) {
            // 逐一删除内容
            foreach ($data as $t) {
                $this->models('module/content')->delete_for_id((int)$t['id'], (int)$t['tableid']);
            }
        }

        // 删除栏目
        $this->db->where_in('id', $catid)->delete($this->models('Module/category')->tablename);

        foreach ($catid as $id) {
            // 删除导航数据
            $this->db->where('mark', 'module-' . APP_DIR . '-' . $id)->delete(SITE_ID . '_navigator');
            // 删除栏目附件
            $this->models('system/attachment')->delete_for_table($this->models('Module/category')->tablename . '-' . $id);
        }
    }

    /**
     * 获取树结构
     */
    protected function _get_tree($data)
    {

        $tree = array();
        $domain = $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'domain');
        $category = $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'category');
        foreach ($data as $t) {
            $url = dr_url_prefix($category[$t['id']]['url'] ? $category[$t['id']]['url'] : '/index.php?s=' . APP_DIR . '&c=category&id=' . $t['id'],
                $domain);
            $t['child'] = $category[$t['id']]['pcatpost'] ? 0 : $t['child'];
            // $t['option'] = '<label><a class="ago" href="' . $url . '" target="_blank"> <i class="fa fa-send"></i> ' . L('访问') . '</a></label>';
            // $this->is_auth(APP_DIR . '/admin/cfield/index') && $t['option'] .= '<label><a class="alist onloading" href=' . $this->duri->uri2url('admin/field/index/rname/' . APP_DIR . '-' . SITE_ID . '/rid/' . $t['id']) . '> <i class="fa fa-plus-square"></i> ' . L('附加字段') . '(' . (int)count($category[$t['id']]['field'] ?? []) . ')</a></label>';
            $t['option'] .= $this->get_option($t);
            // $this->is_auth(APP_DIR . '/admin/category/add') && $t['option'] .= '<label><a class="aadd onloading" href=' . dr_url(APP_DIR . '/category/add',
            //         array('id' => $t['id'])) . '> <i class="fa fa-plus"></i> ' . L('子类') . '</a></label>';
            $this->is_auth(APP_DIR . '/admin/category/edit') && $t['option'] .= '<label><a class="aedit onloading" href=' . dr_url(APP_DIR . '/category/edit',
                    array('id' => $t['id'])) . '> <i class="fa fa-edit"></i> ' . L('修改') . '</a></label>';
            !$t['setting']['linkurl'] && !$t['child'] && $this->is_auth(APP_DIR . '/admin/content/add') && $t['option'] .= '<label><a class="aadd onloading" href=' . dr_url(APP_DIR . '/content/add',
                    array('catid' => $t['id'])) . '> <i class="fa fa-pencil"></i> ' . L('发布') . '</a></label>';
            !$t['setting']['linkurl'] && !$t['child'] && $t['option'] .= '<label><a class="ago onloading" href=' . dr_url(APP_DIR . '/content/index',
                    array('catid' => $t['id'])) . '> <i class="fa fa-navicon"></i> ' . L('管理') . '</a></label>';
            // 判断是否生成静态
            $t['html'] = $category[$t['id']]['setting']['html'] ? '<label><a class="badge badge-success" href=' . dr_url('module/html',
                    array(
                        'id' => APP_DIR,
                        'sid' => SITE_ID
                    )) . '> ' . L('是') . ' </a>' : '<a class="badge badge-warning" href=' . dr_url('module/html',
                    array('id' => APP_DIR, 'sid' => SITE_ID)) . '> ' . L('否') . ' </a></label>';
            // 外部地址不显示
            $t['setting']['linkurl'] && $t['html'] = '';
            $t['total'] = (int)$category[$t['id']]['total'];
            $t['dirname'] = dr_strcut($t['dirname'], 15);
            $tree[$t['id']] = $t;
        }

        return $tree;
    }

    /**
     * 批量自定义URL
     */
    public function url()
    {

        redirect(ADMIN_URL . dr_url('module/install3', array('id' => APP_DIR, 'sid' => SITE_ID)), 'refresh');
        exit;
        $category = $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'category');
        if (IS_POST) {
            $catid = $this->input->post('catid');
            if ($catid) {
                foreach ($catid as $id) {
                    $setting = $category[$id]['setting'];
                    if ($setting) {
                        $setting['urlrule'] = (int)$this->input->post('urlrule');
                        $this->db->where('id', $id)->update($this->models('Module/category')->tablename, array(
                            'setting' => array2string($setting)
                        ));
                    }
                }
                $this->clear_cache('module');
                $this->admin_msg(L('总共批量设置了%s个栏目<br>请更新缓存和更新地址', count($catid)), dr_url(APP_DIR . '/category/index'), 1,
                    5);
            } else {
                $error = L('请选择一个的栏目');
            }
        }
        $this->template->assign(array(
            'error' => $error,
            'select' => $this->select_category($category, 0,
                ' class=\'form-control\' id=\'dr_catid\' name=\'catid[]\' multiple style="min-width:200px;height:250px;"',
                ''),
        ));
        $this->template->display('category_url.html');
    }

    /**
     * 首页
     */
    public function index()
    {

        if (IS_POST) {
            $ids = $this->input->post('ids', true);
            !$ids && $this->msg(0, L('您还没有选择呢'));

            if ($this->input->post('action') == 'order') {
                $data = $this->input->post('data');
                foreach ($ids as $id) {
                    $this->db->where('id', $id)->update($this->models('Module/category')->tablename, $data[$id]);
                }
                $this->clear_cache('module');
                $this->system_log('排序站点【#' . SITE_ID . '】模块【' . APP_DIR . '】栏目【#' . @implode(',', $ids) . '】'); // 记录日志
                $this->models('module')->cache();
                $this->msg(1, L('操作成功，更新缓存生效'));
            } else {
                !$this->is_auth(APP_DIR . '/admin/category/index') && $this->msg(0, L('您无权限操作'));
                $this->delete($ids);
                $this->system_log('删除站点【#' . SITE_ID . '】模块【' . APP_DIR . '】栏目【#' . @implode(',', $ids) . '】'); // 记录日志

                $this->models('module')->cache();
                $this->msg(1, L('操作成功，更新缓存生效'));

            }
        }

        $this->load->library('dtree');
        $this->models('Module/category')->repair();
        $this->dtree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $this->dtree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree = array();
        $data = $this->models('Module/category')->get_data();
        $data && $tree = $this->_get_tree($data);
        $str = "<tr class='\$class'>";
        $str .= "<td align='right'><input name='ids[]' type='checkbox' class='dr_select toggle md-check' value='\$id' /></td>";
        $str .= "<td><input class='input-text displayorder' type='text' name='data[\$id][displayorder]' value='\$displayorder' /></td>";
        $str .= "<td>\$id</td>";
        $str .= $this->is_auth(APP_DIR . '/admin/category/edit') ? "<td>\$spacer<a class='onloading' href='" . dr_url(APP_DIR . '/category/edit') . "&id=\$id'>\$name</a>  \$parent</td>" : "<td>\$spacer\$name  \$parent</td>";
        $str .= "<td>\$dirname</td>";
        $str .= "<td>\$total</td>";
        // $str .= "<td>\$html</td>";
        $str .= "<td class='dr_option'>\$option</td>";
        $str .= "</tr>";
        $this->dtree->init($tree);

        $this->template->assign(array(
            'page' => (int)$this->input->get('page'),
            'list' => $this->dtree->get_tree(0, $str),
        ));
        $this->template->display('category_index.html');
    }

    /**
     * 添加
     */
    public function add()
    {

        $id = (int)$this->input->get('id');
        $data = array();
        $result = '';

        // 初始化配置信息
        if ($id) {
            $parent = $this->models('Module/category')->get($id);
            $data['setting'] = $parent['setting'];
            unset($parent);
        } else {
            $data['setting']['template']['list'] = 'list.html';
            $data['setting']['template']['show'] = 'show.html';
            $data['setting']['template']['extend'] = 'extend.html';
            $data['setting']['template']['category'] = 'category.html';
            $data['setting']['template']['search'] = 'search.html';
            $data['setting']['template']['pagesize'] = 20;
            $data['setting']['template']['mpagesize'] = 20;
            $data['setting']['seo']['list_title'] = '[第{page}页{join}]{catpname}{join}{modname}{join}{SITE_NAME}';
            $data['setting']['seo']['show_title'] = '[第{page}页{join}]{title}{join}{catpname}{join}{modname}{join}{SITE_NAME}';
            $data['setting']['seo']['extend_title'] = '{extend}{join}{title}{join}{catpname}{join}{modname}{join}{SITE_NAME}';
        }

        if (IS_POST) {
            $field = $this->field ? array_merge($this->field, $this->thumb) : $this->thumb;
            $data = $this->input->post('data', true);
            $backurl = $this->input->post('backurl');
            $tmp = $this->validate_filter($field);
            if ($tmp) {
                if (isset($tmp['error'])) {
                    $this->admin_msg($tmp['msg']);
                } else {
                    // 删除老数据
                    foreach ($field as $i => $t) {
                        unset($data[$i]);
                    }
                    // 归类新数据
                    foreach ($tmp[1] as $i => $t) {
                        if (isset($field[$i])
                            || strpos($i, '_lng')
                            || strpos($i, '_lat')) {
                            $data[$i] = $t;
                        }
                    }
                }
            }
            if ($this->input->post('_all') == 1) {
                $names = $this->input->post('names', true);
                $number = $this->models('Module/category')->add_all($names, $data, $field);
                $this->system_log('批量添加站点【#' . SITE_ID . '】模块【' . APP_DIR . '】栏目【' . $number . '个】'); // 记录日志
                //$this->clear_cache('module');
                $this->models('module')->cache();
                $this->admin_msg(L('批量添加%s个，更新缓存生效', $number), dr_url(APP_DIR . '/category/index'), 1);
            } else {
                $result = $this->models('Module/category')->add($data, $field);
                if (is_numeric($result)) {
                    $this->clear_cache('module');
                    $this->system_log('添加站点【#' . SITE_ID . '】模块【' . APP_DIR . '】栏目【#' . $result . '】'); // 记录日志
                    $this->attachment_handle($this->uid, $this->models('Module/category')->tablename . '-' . $result,
                        $field);
                    $this->admin_msg(L('操作成功，更新缓存生效'), $backurl, 1, 1);
                }
            }
        }

        $this->template->assign(array(
            'id' => $id,
            'page' => 0,
            'data' => $data,
            'role' => $this->dcache->get('role'),
            'thumb' => $this->field_input($this->thumb, $data, true),
            'field' => $this->field_input($this->field, $data, true),
            'result' => $result,
            'extend' => $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'extend'),
            'select' => $this->select_category($this->models('Module/category')->get_data(), $id,
                ' class=\'form-control\' name=\'data[pid]\'', L('顶级栏目')),
            'backurl' => $backurl ? $backurl : $_SERVER['HTTP_REFERER'],
        ));
        $this->template->display('category_add.html');
    }

    /**
     * 修改
     */
    public function edit()
    {

        $id = (int)$this->input->get('id');
        $data = $this->models('Module/category')->get($id);
        $page = (int)$this->input->get('page');
        $result = '';
        !$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));

        if (IS_POST) {
            $field = $this->field ? array_merge($this->field, $this->thumb) : $this->thumb;
            $_data = $data;
            $page = (int)$this->input->post('page');
            $data = $this->input->post('data', true);
            $tmp = $this->validate_filter($field);
            if ($tmp) {
                if (isset($tmp['error'])) {
                    $this->admin_msg($tmp['msg']);
                } else {
                    // 删除老数据
                    foreach ($field as $i => $t) {
                        unset($data[$i]);
                    }
                    // 归类新数据
                    foreach ($tmp[1] as $i => $t) {
                        if (isset($field[$i])
                            || strpos($i, '_lng')
                            || strpos($i, '_lat')) {
                            $data[$i] = $t;
                        }
                    }
                }
            }
            $backurl = $this->input->post('backurl');
            $data['pid'] = $data['pid'] == $id ? $_data['pid'] : $data['pid'];
            $data['rule'] = $this->input->post('rule');
            $result = $this->models('Module/category')->edit($id, $data, $_data, $field);
            $this->models('Module/category')->syn($data, $_data);
            $data['id'] = $id;
            $data['permission'] = $data['rule'];
            $this->attachment_handle($this->uid, $this->models('Module/category')->tablename . '-' . $id, $this->thumb,
                $_data);
            //$this->clear_cache('module');
            $this->models('module')->cache();
            $this->system_log('修改站点【#' . SITE_ID . '】模块【' . APP_DIR . '】栏目【#' . $id . '】'); // 记录日志
            $this->admin_msg(L('操作成功，更新缓存生效'), $backurl, 1, 2);
        }

        $category = $this->models('Module/category')->get_data();
        $this->template->assign(array(
            'id' => $id,
            'page' => $page,
            'data' => $data,
            'role' => $this->get_cache('role'),
            'thumb' => $this->new_field_input($this->thumb, $data, true),
            'field' => $this->new_field_input($this->field, $data, true),
            'extend' => $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'extend'),
            'result' => $result,
            'select' => $this->select_category($category, $data['pid'], ' class=\'form-control\' name=\'data[pid]\'',
                L('顶级栏目')),
            'backurl' => $backurl ? $backurl : $_SERVER['HTTP_REFERER'],
            'select_syn' => $this->select_category($category, 0,
                ' class=\'form-control\' id="dr_synid" name=\'synid[]\' multiple style="min-width:150px;height:200px;"',
                '')
        ));
        $this->template->display('category_add.html');
    }

    /**
     * Ajax调用栏目附加字段
     *
     * @return void
     */
    public function field()
    {
        $data = string2array($this->input->post('data'));
        $field = $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'category', (int)$this->input->post('catid'),
            'field');
        !$field && exit('');
        exit($this->field_input($field, $data));
    }

    /**
     * 设置规则
     */
    public function rule()
    {

        $id = $this->input->get('id');
        $catid = $this->input->get('catid');
        $data = $this->models('Module/category')->get_permission($catid);

        if (IS_POST) {
            $temp = $data[$id];
            $value = $this->input->post('data');
            $data[$id] = $value;
            $data[$id]['add'] = $temp['add'];
            $data[$id]['del'] = $temp['del'];
            $data[$id]['show'] = $temp['show'];
            $data[$id]['edit'] = $temp['edit'];
            $data[$id]['forbidden'] = $temp['forbidden'];
            $this->db->where('id', $catid)->update($this->models('Module/category')->tablename, array(
                'permission' => array2string($data)
            ));
            //$this->clear_cache('module');
            $this->models('module')->cache();
            $this->system_log('站点【#' . SITE_ID . '】模块【' . APP_DIR . '】栏目【#' . $catid . '】权限设置'); // 记录日志
            exit;
        }

        $html = '<select name="data[verify]"><option value="0"> -- </option>';
        $verify = $this->get_cache('verify');
        if ($verify) {
            foreach ($verify as $t) {
                $html .= '<option value="' . $t['id'] . '" ' . ($data[$id]['verify'] == $t['id'] ? 'selected' : '') . '> ' . $t['name'] . '(' . $t['num'] . ') </option>';
            }
        }
        $html .= '</select>';

        $this->template->assign(array(
            'data' => $data[$id],
            'verify' => $html,
            'extend' => $this->get_cache('module-' . SITE_ID . '-' . APP_DIR, 'extend')
        ));
        $this->template->display('category_rule.html');
    }

    public function select()
    {
        $id = (int)$this->input->get('id');
        echo $this->select_category($this->models('Module/category')->get_data(), $id,
            ($id ? 'disabled ' : '') . ' class=\'form-control\' name=\'module[catid]\' onChange=\'dr_select_category(this.value)\'',
            L('全部栏目'));
    }
}