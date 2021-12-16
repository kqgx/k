<?php

require_once FCPATH.'core/D_Common.php';

class D_Module extends D_Common {

    public $dir; // 模块目录
    public $flag; // 可用推荐位
    public $catid; // 当前会员可管理的栏目（id数组）
    public $search_model; // 搜索模型类
    public $is_category; // 是否开启栏目功能
    public $syn_content; // 同步内容到其他站点

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        !defined('DR_IS_SO') && $this->_module_init();
    }
    
    /**
     * 栏目权限验证
     *
     * @param	intval	$catid	栏目id
     * @param	string	$option	权限选项
     * @return	bool
     */
    public function is_category_auth($catid, $option) {

        if ($this->admin['adminid'] == 1 || !$catid || !$option) {
            return TRUE;
        }

        return $this->get_cache('module-'.SITE_ID.'-'.$this->dir, 'category', $catid, 'setting', 'admin', $this->admin['adminid'], $option);
    }

    /**
     * 栏目选择
     *
     * @param array			$data		栏目数据
     * @param intval/array	$id			被选中的ID，多选是可以是数组
     * @param string		$str		属性
     * @param string		$default	默认选项
     * @param intval		$onlysub	只可选择子栏目
     * @param intval		$is_push	是否验证权限
     * @param intval		$is_first	是否返回第一个可用栏目id
     * @return string
     */
    public function select_category($data, $id = 0, $str = '', $default = ' -- ', $onlysub = 0, $is_push = 0, $is_first = 0) {

        $cache = md5(array2string($data).array2string($id).$str.$default.$onlysub.$is_push.$is_first.$this->member['uid']);
        if ($cache_data = $this->get_cache_data($cache)) {
            return $cache_data;
        }

        $tree = array();
        $first = 0; // 第一个可用栏目
        $string = '<select class=\'form-control\' '.$str.'>';

        $default && $string.= "<option value='0'>$default</option>";

        if (is_array($data)) {
            foreach($data as $t) {
                // 外部链接不显示
                $is_link = isset($t['setting']['linkurl']) && $t['setting']['linkurl'] ? 1 : (isset($t['tid']) && $t['tid'] == 2 ? 1 : 0);
                if ($is_link) {
                    continue;
                }
                // 单页且为最终单页不显示
                if (isset($t['tid']) && $t['tid'] == 0 && !$t['child']) {
                    continue;
                }
                $t['html_disabled'] = 0;
                // 验证权限
                if ($t['pcatpost']) {
                    // 父栏目可发布时的权限
                    if ($is_push) {
                        if (IS_MEMBER && isset($this->module_rule[$t['id']]) && !$this->module_rule[$t['id']]['add']) {
                            // 会员中心用户发布权限
                            continue;
                        } elseif (IS_ADMIN && !$this->is_category_auth($t['id'], 'add') && !$this->is_category_auth($t['id'], 'edit')) {
                            // 后台角色发布和修改权限
                            continue;
                        } elseif ($t['mid'] != $this->dir) {
                            continue;
                        }
                    } else {
                        // 是否可选子栏目
                        $t['html_disabled'] = $onlysub ? 1 : 0;
                    }
                    // 选中操作
                    $t['selected'] = '';
                    if (is_array($id)) {
                        $t['selected'] = in_array($t['id'], $id) ? 'selected' : '';
                    } elseif(is_numeric($id)) {
                        $t['selected'] = $id == $t['id'] ? 'selected' : '';
                    }
                } else {
                    // 正常栏目权限
                    if ($is_push && $t['child'] == 0) {
                        if (IS_MEMBER && !$this->module_rule[$t['id']]['add']) {
                            continue;
                        } elseif (IS_ADMIN && !$this->is_category_auth($t['id'], 'add') && !$this->is_category_auth($t['id'], 'edit')) {
                            continue;
                        } elseif ($t['mid'] && $t['mid'] != $this->dir) {
                            continue;
                        }
                    }
                    // 选中操作
                    $t['selected'] = '';
                    if (is_array($id)) {
                        $t['selected'] = in_array($t['id'], $id) ? 'selected' : '';
                    } elseif(is_numeric($id)) {
                        $t['selected'] = $id == $t['id'] ? 'selected' : '';
                    }
                    // 是否可选子栏目
                    $t['html_disabled'] = $onlysub && $t['child'] != 0 ? 1 : 0;
                }
                // 第一个可用子栏目
                $first == 0 && $t['child'] == 0 && $first = $t['id'];
                if (isset($t['permission'])) {
                    unset($t['permission']);
                }
                if (isset($t['setting'])) {
                    unset($t['setting']);
                }
                $tree[$t['id']] = $t;
            }
        }

        if (IS_ADMIN && $this->admin['adminid'] > 1 && !$tree && $data) {
            if ($this->router->method == 'add') {
                $string = '<label style="padding-top:8px"><font color="red">你没有对此角色设置可用的管理栏目，在栏目管理中对具体的栏目进行设置管理权限</font></label>';
            } else {
                $string = '<label><font color="red">无栏目内容管理权限</font></label>';
            }

        } else {
            $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $str2 = "<optgroup label='\$spacer \$name'></optgroup>";

            $this->load->library('dtree');
            $this->dtree->init($tree);

            $string.= $this->dtree->get_tree_category(0, $str, $str2);
            $string.= '</select>';

            if ($is_first) {
                $mark = "value='";
                $first2 = (int)substr($string, strpos($string, $mark) + strlen($mark));
                $first = $first2 ? $first2 : $first;
            }
        }


        $data = $is_first ? array($string, $first) : $string;
        $tree && $this->set_cache_data($cache, $data, 7200);

        return $data;
    }

    /**
     * 模块内容/扩展购买页
     */
    protected function _show_buy() {

        $id = (int)$this->input->get('id');
        $eid = (int)$this->input->get('eid');

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);
        !$mod && exit(safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '模块【'.$this->dir.'】不存在')).')');

        $name = $id ? 'show'.$this->dir.SITE_ID.$id : 'extend'.$this->dir.SITE_ID.$id;
        $data = $this->get_cache_data($name);

        if ($id) {
            // 模块内容
            if (!$data) {
                $data = $this->models('module/content')->get($id);
                !$data && exit(safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '内容不存在')).')');
            }
            // 字段
            $cat = $mod['category'][$data['catid']];
            // 格式化输出自定义字段
            $fields = $mod['field'];
            $fields = $cat['field'] ? array_merge($fields, $cat['field']) : $fields;
            $table = SITE_ID.'_'.$this->dir.'_buy';
            $where = 'cid='.$id.' and uid='.$this->uid;
            //
            $tpl = 'show_buy.html';
        } elseif ($eid) {
            // 模块内容扩展
            if (!$data) {
                $data = $this->models('module/content')->get_extend($eid);
                !$data && exit(safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '扩展内容不存在')).')');
            }
            // 格式化输出自定义字段
            $fields = $mod['extend'];
            $table = SITE_ID.'_'.$this->dir.'_extend_buy';
            $where = 'eid='.$eid.' and uid='.$this->uid;
            //
            $tpl = 'extend_buy.html';
        } else {
            echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '无参数')).')';exit;
        }

        $fields['inputtime'] = array('fieldtype' => 'Date');
        $fields['updatetime'] = array('fieldtype' => 'Date');

        $data = $this->field_format_value($fields, $data, 0);

        // 查找收费有收费字段
        $fees = '';
        foreach ($fields as $t) {
            if ($t['fieldtype'] == 'Fees') {
                $fees = $t['fieldname'];
                if ($t['setting']['option']['mode']) {
                    // 按会员组模式
                    $this->markrule = $this->member['groupid'];
                }
                break;
            }
        }

        // 无收费字段
        if (!$fees) {
            echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '此模块内容没有收费字段')).')';exit;
        } elseif (!isset($data[$fees])) {
            echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '此模块内容收费字段内容没有填写')).')';exit;
        }
        // 判断是否开启阅读收费
        if ($data[$fees]) {
            // 判断用户权限
            if ($this->uid) {
                $is_buy = $this->db->where($where)->count_all_results($table);
                $data['score'] = abs((int)$data[$fees][$this->markrule]);
                $data['is_buy'] = $data['score'] ? $is_buy : 1;
                // 当前类型是扩展时判定一下主内容是否被购买
                $eid && $data['is_buy'] == 0 && $data['is_buy'] = $this->db->where('cid='.(int)$data['cid'].' and eid=0 and uid='.$this->uid)->count_all_results($table);
            } else {
                exit(safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => '收费内容请登录之后再查看')).')');
            }
        } else {
            // 未开启时默认为显示
            $data['is_buy'] = 1;
        }


        if (!$data['is_buy']
            && $this->input->get('action') == 'confirm') {
            // 会员未登录
            !$this->member && $this->msg(L('会话超时，请重新登录'));
            // 虚拟币检查
            -$data['score'] + $this->member['score'] < 0 && $this->msg(L(SITE_SCORE.'不足！本次需要%s'.SITE_SCORE.'，当前余额%s'.SITE_SCORE, $data['score'], $this->member['score']));
            // 扣减虚拟币
            $this->models('member')->update_score(1, $this->uid, -$data['score'], '', '购买《'.($data['name'] ? $data['name'] : $data['title']).'》');
            // 记录购买历史
            $insert = array(
                'uid' => $this->uid,
                'url' => $data['url'],
                'score' => $data['score'],
                'thumb' => $data['preview'] ? $data['preview'] : ($data['thumb'] ? $data['thumb'] : ''),
                'inputtime' => SYS_TIME
            );
            if ($id) {
                $insert['cid'] = $id;
                $insert['title'] = $data['title'];
            } else {
                $insert['eid'] = $eid;
                $insert['cid'] = $data['cid'];
                $insert['title'] = ($data['ctitle'] ? $data['ctitle'].' - ' : '').$data['name'];
            }
            $this->db->insert($table, $insert);
            $this->msg(L('购买成功'), $data['url'], 1);
        } else {
            $this->template->assign($data);
            ob_start();
            $this->template->display($tpl);
            $html = ob_get_contents();
            ob_clean();
            echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('html' => $html)).')';exit;
        }
    }

    /**
     * 模块首页
     */
    protected function _index() {

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);

        // 判断满足定向跳转的条件
        $mod['url'] && dr_is_redirect(2, dr_url_prefix($mod['url'], $this->dir));

        $file = DATAPATH.'index/'.(IS_MOBILE ? 'mobile-' : '').DOMAIN_NAME.'-'.$this->dir.'-'.max(intval($_GET['page']), 1).'.html';

        if (is_file($file) && filemtime($file) < SYS_TIME + SYS_CACHE_MINDEX) {
            exit(file_get_contents($file));
        }

        // 系统开启静态首页、非手机端访问、静态文件不存在时，才生成文件
        if (defined('SYS_AUTO_CACHE') && SYS_AUTO_CACHE && SYS_CACHE_MINDEX && !is_file($file) && !SITE_CLOSE) {
            ob_start();
            $this->template->assign(dr_module_seo($mod));
            $this->template->assign('indexm', 1);
            $this->template->display('index.html');
            $html = ob_get_clean();
            file_put_contents($file, $html, LOCK_EX);
            echo $html;exit;
        } else {
            $this->template->assign(dr_module_seo($mod));
            $this->template->assign('indexm', 1);
            $this->template->display('index.html');
        }
    }

    /**
     * 模块栏目列表
     */
    protected function _category($id = 0, $dir = NULL, $page = 1) {

        $id = $catid = intval($id);
        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);
        
        if ($id) {
            $cat = $mod['category'][$id];
            !$cat && $this->goto_404_page(L('栏目(%s)不存在', $id));
        } elseif ($dir) {
            $id = $mod['category_dir'][$dir];
            $cat = $mod['category'][$id];
            if (!$cat) {
                // 无法通过目录找到栏目时，尝试多及目录
                foreach ($mod['category'] as $t) {
                    if ($t['setting']['urlrule']) {
                        $rule = $this->get_cache('urlrule', $t['setting']['urlrule']);
                        if ($rule['value']['catjoin'] && strpos($dir, $rule['value']['catjoin'])) {
                            $dir = trim(strchr($dir, $rule['value']['catjoin']), $rule['value']['catjoin']);
                            if (isset($mod['category_dir'][$dir])) {
                                $id = $mod['category_dir'][$dir];
                                $cat = $mod['category'][$id];
                                break;
                            }
                        }
                    }
                }
                // 返回无法找到栏目
                !$cat && $this->goto_404_page(L('栏目(%s)不存在', $dir));
            }
        } else {
            $this->goto_404_page(L('栏目不存在'));
        }
        $tpl = $cat['child'] ? $cat['setting']['template']['category'] : $cat['setting']['template']['list'];

        // 定向URL
        $cat['url'] && dr_is_redirect(3, dr_url_prefix($cat['url'], $this->dir));

        // 拒绝访问判断
        isset($cat['permission'][$this->markrule]['show']) && $cat['permission'][$this->markrule]['show'] && $this->goto_404_page(L('当前会员组无权限访问'));

        // 是否定向到搜索页面
        if (!$mod['setting']['search']['close'] && $mod['setting']['search']['catsync']) {
            $_GET = array(
                'catid' => $cat['id']
            );
            return $this->_search();
        }

        list($parent, $related) = $this->models('Module/category')->related($mod, $id);

        // 静态时的
        if ($cat['setting']['html']) {
            $this->template->assign('my_web_url', dr_url_prefix($cat['url'], $this->dir));
        }
        
        $this->render(array_merge(dr_category_seo($mod, $cat, max(1, (int)$this->input->get('page'))), array(
            'cat' => $cat,
            'top' => $mod['category'][$id]['topid'] && $mod['category'][$mod['category'][$id]['topid']] ? $mod['category'][$mod['category'][$id]['topid']] : $cat,
            'page' => $page,
            'catid' => $id,
            'params' => array('catid' => $id),
            'parent' => $parent,
            'related' => $related,
            'urlrule' => $this->mobile && $cat['setting']['html'] ? dr_mobile_category_url($this->dir, $id, '{page}') : dr_category_url($catid, '{page}'),
        )), $tpl);
    }

    /**
     * 模块内容页
     */
    protected function _show($id = NULL, $page = 1, $return = FALSE) {

        $id = intval($id);

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);

        if ($this->input->get('type') == 'draft') {
            // 草稿数据
            $data = $this->models('module/content')->get_draft($id);
            (!$data || !($data['uid'] == $this->uid || $this->member['adminid'])) && $this->msg(L('数据不存在'));
        } else {
            if (!$id && $this->dir != 'share'
                && isset($_GET['field'])
                && $mod['field'][$_GET['field']]['ismain']
            ) {
                $row = $this->db
                    ->select('id')
                    ->where(safe_replace($_GET['field']), safe_replace($_GET['value']))
                    ->get(SITE_ID.'_'.$this->dir)
                    ->row_array();
                if ($row) {
                    $id = intval($row['id']);
                    define('CT_HTML_FILE', 1);
                }
            }
            // 正式内容缓存查询结果
            $name = 'show'.$this->dir.SITE_ID.$id;
            $data = $this->get_cache_data($name);
            // 定向URL
            $data['url'] && dr_is_redirect(4, dr_url_prefix($data['url'], $this->dir));
        }

        if (!$data) {
            $data = $this->models('module/content')->get($id);
            if (!$data) {
                if ($return) {
                    return NULL;
                }
                $this->goto_404_page(L('内容(id#%s)不存在', $id));
            }
            // 定向URL
            $data['url'] && dr_is_redirect(4, dr_url_prefix($data['url'], $this->dir));
            if (!$mod) {
                if ($return) {
                    return NULL;
                }
                $this->msg(L('模块不存在，请尝试更新缓存'));
            }
            // 检测转向字段
            $redirect = 0;
            foreach ($mod['field'] as $t) {
                if ($t['fieldtype'] == 'Redirect'
                    && $data[$t['fieldname']]) {
                    $this->db->where('id', $id)->set('hits', 'hits+1', FALSE)->update(SITE_ID.'_'.$this->dir);
                    if ($mod['category'][$data['catid']]['setting']['html']) {
                        $redirect = 1;
                        $data['goto_url'] = $data[$t['fieldname']];
                        break;
                    } else {
                        redirect($data[$t['fieldname']], 'location', 301);
                        exit;
                    }
                }
            }

            $data['catid'] = intval($data['catid']);
            $cat = $mod['category'][$data['catid']];

            // 处理关键字标签
            $data['tag'] = $data['keywords'];
            $data['keyword_list'] = dr_tag_list(MOD_DIR, $data['keywords']);

            // 上一篇文章
            $this->db->where('catid', $data['catid'])->where('status', 9);
            $this->db->where('id<', $data['id']);
            $this->db->order_by('id desc');
            $data['prev_page'] = $this->db->limit(1)->get($this->models('module/content')->prefix)->row_array();

            // 下一篇文章
            $this->db->where('catid', $data['catid'])->where('status', 9);
            $this->db->where('id>', $data['id']);
            $this->db->order_by('id asc');
            $data['next_page'] = $this->db->limit(1)->get($this->models('module/content')->prefix)->row_array();

            // 缓存数据
            $data['uid'] != $this->uid && $data = $this->set_cache_data($name, $data, SYS_CACHE_MSHOW);
        } else {
            $cat = $mod['category'][$data['catid']];
        }

        // 状态判断
        if ($data['status'] == 10 && !($this->uid == $data['uid'] || $this->member['adminid'])) {
            if ($return) {
                return NULL;
            }
            $this->goto_404_page(L('您暂时无法访问'));
        }

        // 判断是否同步栏目
        if ($data['link_id'] && $data['link_id'] > 0) {
            $data = $this->models('module/content')->get($data['link_id']);
            redirect(dr_url_prefix($data['url'], $this->dir), 301);exit;
        }

        // 拒绝访问判断
        if (isset($cat['permission'][$this->markrule]['show'])
            && $cat['permission'][$this->markrule]['show'] && !$this->member['adminid']) {
            if ($return) {
                return NULL;
            }
            $this->goto_404_page(L('当前会员组无权限访问'));
        }

        // 格式化输出自定义字段
        $fields = $mod['field'];
        $fields = $cat['field'] ? array_merge($fields, $cat['field']) : $fields;
        $fields['inputtime'] = array('fieldtype' => 'Date');
        $fields['updatetime'] = array('fieldtype' => 'Date');
        $data = $this->field_format_value($fields, $data, $page);

        // 判断分页
        if ($page && isset($data['content_page'])
            && $data['content_page'] && !$data['content_page'][$page]) {
            if ($return) {
                return NULL;
            }
            $this->goto_404_page(L('该分页不存在'));
        }

        // 静态时的
        if ($cat['setting']['html']) {
            $this->template->assign('my_web_url', dr_url_prefix($data['url'], $this->dir));
        }

        // 栏目下级或者同级栏目
        list($parent, $related) = $this->models('Module/category')->related($mod, $data['catid']);
          
        !$return && $this->render(array_merge($data, dr_show_seo($mod, $data, $page), array(
            'cat' => $cat,
            'page' => $page,
            'top' => $mod['category'][$data['catid']]['topid'] && $mod['category'][$mod['category'][$data['catid']]['topid']] ? $mod['category'][$mod['category'][$data['catid']]['topid']] : $cat,
            'parent' => $parent,
            'params' => array('catid' => $data['catid']),
            'related' => $related,
            'urlrule' => $this->mobile ? dr_mobile_show_url($this->dir, $id, '{page}') : dr_show_url($mod, $data, '{page}'),
        )), isset($data['template']) && strpos($data['template'], '.html') !== FALSE ? $data['template'] : ($cat['setting']['template']['show'] ? $cat['setting']['template']['show'] : 'show.html'));

        // 存在转向字段时处理方式
        return array($data, $redirect ? 'go' : $tpl);
    }

    /**
     * 模块扩展内容页
     */
    protected function _extend($id = NULL, $return = FALSE) {

        $id = intval($id);

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);

        if ($this->input->get('type') == 'draft') {
            // 草稿数据
            $data = $this->models('module/content')->get_draft($id);
            (!$data || !($data['uid'] == $this->uid || $this->member['adminid'])) && $this->msg(L('数据不存在'));
        } else {
            if (!$id && isset($_GET['field'])
                && $mod['extend'][$_GET['field']]['ismain']
            ) {
                $row = $this->db
                    ->select('id')
                    ->where(safe_replace($_GET['field']), safe_replace($_GET['value']))
                    ->get(SITE_ID.'_'.$this->dir.'_extend')
                    ->row_array();
                if ($row) {
                    $id = intval($row['id']);
                    define('CT_HTML_FILE', 1);
                }
            }
            // 正式内容缓存查询结果
            $name = 'extend'.$this->dir.SITE_ID.$id;
            $data = $this->get_cache_data($name);
            // 定向URL
            $data['url'] && dr_is_redirect(5, dr_url_prefix($data['url'], $this->dir));
        }

        if (!$data) {

            if (!$mod) {
                if ($return) {
                    return NULL;
                }
                $this->msg(L('模块不存在，请尝试更新缓存'));
            }

            $data = $this->models('module/content')->get_extend($id);
            if (!$data) {
                if ($return) {
                    return NULL;
                }
                $this->goto_404_page(L('章节(id#%s)不存在', $id));
            }

            // 定向URL
            $data['url'] && dr_is_redirect(5, dr_url_prefix($data['url'], $this->dir));

            $content = $this->get_cache_data('show'.$this->dir.SITE_ID.$data['cid']);
            !$content && $content = $this->get_cache_data('extend-show'.$this->dir.SITE_ID.$data['cid']);

            if (!$content) {
                $content = $this->models('module/content')->get($data['cid']);
                $this->set_cache_data('extend-show'.$this->dir.SITE_ID.$data['cid'], $content, SYS_CACHE_MSHOW);
            }
            if (!$content) {
                if ($return) {
                    return NULL;
                }
                $this->goto_404_page(L('内容(id#%s)不存在', $data['cid']));
            }

            foreach ($content as $k => $v) {
                !isset($data['c'.$k]) && $data['c'.$k] = $v;
            }

            $data['fid'] = 0;

            // 检测转向字段
            $redirect = 0;
            foreach ($mod['extend'] as $t) {
                if ($t['fieldtype'] == 'Redirect'
                    && $data[$t['fieldname']]) {
                    if ($mod['category'][$data['catid']]['setting']['html']) {
                        $redirect = 1;
                        $data['goto_url'] = $data[$t['fieldname']];
                        break;
                    } else {
                        redirect($data[$t['fieldname']], 'location', 301);
                        exit;
                    }
                }
            }

            $cat = $mod['category'][$data['catid']];

            // 上一篇文章
            $this->db->where('cid', (int)$data['cid'])->where('status', 9);
            $this->db->where('id<', $data['id']);
            $this->db->order_by('id desc');
            $data['prev_page'] = $this->db->limit(1)->get($this->models('module/content')->prefix.'_extend')->row_array();

            // 下一篇文章
            $this->db->where('cid', (int)$data['cid'])->where('status', 9);
            $this->db->where('id>', $data['id']);
            $this->db->order_by('id asc');
            $data['next_page'] = $this->db->limit(1)->get($this->models('module/content')->prefix.'_extend')->row_array();

            // 缓存数据
            $data['uid'] != $this->uid && $data = $this->set_cache_data($name, $data, SYS_CACHE_MSHOW);

        } else {
            $cat = $mod['category'][$data['catid']];
        }

        // 状态判断
        if ( ($data['status'] == 10 || $data['cstatus'] == 10)
            && !($this->uid == $data['uid'] || $this->member['adminid'])) {
            if ($return) {
                return NULL;
            }
            $this->goto_404_page(L('您暂时无法访问'));
        }

        // 拒绝访问判断
        if (isset($cat['permission'][$this->markrule]['show'])
            && $cat['permission'][$this->markrule]['show'] && !$this->member['adminid']) {
            if ($return) {
                return NULL;
            }
            $this->goto_404_page(L('当前会员组无权限访问'));
        }

        // 格式化输出自定义字段
        $fields = $mod['field'];
        $fields = $cat['field'] ? array_merge($fields, $cat['field']) : $fields;
        $fields = $fields + $mod['extend'];
        $fields['inputtime'] = array('fieldtype' => 'Date');
        $fields['updatetime'] = array('fieldtype' => 'Date');
        $data = $this->field_format_value($fields, $data, 1);

        // 栏目下级或者同级栏目
        list($parent, $related) = $this->models('Module/category')->related($mod, $data['catid']);

        // 静态时的
        if ($cat['setting']['html']) {
            $this->template->assign('my_web_url', dr_url_prefix($data['url'], $this->dir));
        }
        
        !$return && $this->render(array_merge($data, dr_extend_seo($mod, $data), array(
            'cat' => $cat,
            'params' => array('catid' => $data['catid']),
            'parent' => $parent,
            'related' => $related,
            'urlrule' => $this->mobile ? dr_mobile_extend_url($this->dir, $id, '{page}') : dr_extend_url($mod, $data, '{page}'),
        )), $cat['setting']['template']['extend'] ? $cat['setting']['template']['extend'] : 'extend.html');

        // 存在转向字段时处理方式
        return array($data, $redirect ? 'go' : $tpl);
    }

    /**
     * 模块内容搜索页
     */
    protected function _search($call = '') {

        // 对指定模块搜索
        $call && $this->dir = $call;

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);
        if (isset($mod['setting']['search']['close'])
            && $mod['setting']['search']['close']) {
            if ($call) {
                return NULL;
            } else {
                $this->msg(L('此模块已经关闭了搜索功能'));
            }
        }

        // 清除过期缓存
        $this->models('Module/search')->clear((int)SYS_CACHE_MSEARCH);

        // 搜索参数
        $get = $this->input->get(NULL, TRUE);
        $get = isset($get['rewrite']) ? dr_rewrite_decode($get['rewrite'], $mod['setting']['search']['param_join'], $mod['setting']['search']['param_field']) : $get;

        $id = $get['id'];
        $catid = (int)$get['catid'];
        $_GET['page'] = $get['page'];
        $get['keyword'] = safe_replace(str_replace(array('+', ' '), '%', urldecode($get['keyword'])));
        unset($get['s'], $get['c'], $get['m'], $get['id'], $get['page']);

        // 关键字个数判断
        if ($get['keyword']
            && strlen($get['keyword']) < (int)$mod['setting']['search']['length']) {
            if ($call) {
                return NULL;
            } else {
                $this->msg(L('关键字不得少于系统规定的长度'));
            }
        }

        if ($id) {
            // 读缓存数据
            $data = $this->models('Module/search')->get($id);
            $catid = $data['catid'];
            $data['get'] = $data['params'];
            if (!$data) {
                if ($call) {
                    return NULL;
                } else {
                    $this->msg(L('搜索缓存已过期，请重新搜索'));
                }
            }
        } else {
            // 实时组合搜索条件
            $data = $this->models('Module/search')->set($get);
        }

        list($parent, $related) = $this->models('Module/category')->related($mod, $catid);

        $seoinfo = dr_search_seo($mod, $catid, $data['params'], max(1, (int)$this->input->get('page')));
        
        if(IS_API){
            $pagesize = max(10, (int)$this->input->get('pagesize'));
            $result = $this->template->list_tag('list action=search module='.$mod['dirname'].' id='.$data['id'].' total='.($data['contentid'] ? substr_count($data['contentid'], ',') + 1 : 0).' catid='.$data['catid'].' page=1 pagesize='.$pagesize);
            $data['list'] = $result['return']?$result['return']:array();
        }
        
        if ($call) {
            return array(
                'cat' => $mod['category'][$catid],
                'get' => @array_merge($get, $data['params']),
                'data' => $data,
                'caitd' => $catid,
                'parent' => $parent,
                'seoinfo' => $seoinfo,
                'keyword' => $get['keyword'],
                'urlrule' => dr_so_url($data['params'], 'page', '{page}'),
                'sototal' => $data['contentid'] ? substr_count($data['contentid'], ',') + 1 : 0,
                'searchid' => $data['id'],
            );
        } else {
            $urlrule = dr_search_url($get, 'page', '{page}', NULL, $this->dir);
            $this->render(array_merge($data, $seoinfo, array(
                'cat' => $mod['category'][$catid],
                'get' => @array_merge($get, $data['params']),
                'caitd' => $catid,
                'parent' => $parent,
                'related' => $related,
                'keyword' => $get['keyword'],
                'urlrule' => str_replace('{id}', $data['id'], $urlrule),
                'sototal' => $data['contentid'] ? substr_count($data['contentid'], ',') + 1 : 0,
                'searchid' => $data['id'],
            )), $catid && $mod['category'][$catid]['setting']['template']['search'] ? $mod['category'][$catid]['setting']['template']['search'] : 'search.html');
        }
    }

    /**
     * 创建内容html文件
     */
    protected function _create_show_file($id, $is_mobile = 0) {

        if (!$id) {
            log_message('error', '生成失败: id is null');
            return;
        }

        // 判断权限
        !dr_html_auth() && exit('权限验证超时，请重新执行生成');

        define('CT_HTML_FILE', 1);
        $this->clear_cache('show'.$this->dir.SITE_ID.$id);
        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);

        $this->template->set_mobile_file($is_mobile);
        list($data, $tpl) = $this->index($id, 1, TRUE);
        if (!$data) {
            log_message('error', '生成失败: 【'.$this->dir.'】内容'.$id.'不存在');
            return;
        } elseif (!$mod['category'][$data['catid']]['setting']['html']) {
            //exit('未开启');
            log_message('error', '生成失败: 未开启静态生成功能');
            return;// 未开启
        }

        // 同步数据不执行生成
        if ($data['link_id'] > 0) {
            log_message('error', '生成失败: 同步数据不能生成');
            return;
        }

        // 模块内容
        $file = $this->_remove_domain($data['url']);
        if (strpos($file, 'index.php') === FALSE) {

            ob_start();
            $this->template->display($tpl);
            $html = ob_get_clean();

            $filepath = array();
            // 格式化生成文件
            $hfile = dr_format_html_file($file, $is_mobile);
            // 判断是否生成成功
            if (@file_put_contents($hfile, $html, LOCK_EX)) {
                $filepath[] = $hfile;
                // 表示存在内容分页
                if (isset($data['content_page'])
                    && $data['content_page']) {
                    foreach ($data['content_page'] as $i => $p) {
                        $url = dr_show_url($mod, $data, $i);
                        $file = $this->_remove_domain($url);
                        // 格式化生成文件
                        $hfile = dr_format_html_file($file, $is_mobile);
                        ob_start();
                        $this->template->set_mobile_file($is_mobile);
                        list($cdata, $tpl) = $this->index($id, $i, TRUE);
                        if ($cdata) {
                            $this->template->display($tpl);
                            $html = ob_get_clean();
                            if (!@file_put_contents($hfile, $html, LOCK_EX)) {
                                log_message('error', '生成失败: '.$file.'文件写入失败'.$hfile);
                            } else {
                                $filepath[] = $hfile;
                            }
                        }
                    }
                }
            } else {
                log_message('error', '生成失败: '.$file.'文件写入失败'.$hfile);
            }
            // 保存文件记录
            $this->models('module/content')->set_html(1, $data['uid'], 0, $id, $data['catid'], $filepath);
        }
        ob_clean();

        // 扩展内容
        if ($mod['extend']) {
            $list = $this->db->select('id')->where('cid', (int)$id)->get(SITE_ID.'_'.$this->dir.'_extend')->result_array();
            if ($list) {
                $this->clear_cache('show-extend'.$this->dir.SITE_ID.$id);
                foreach ($list as $t) {

                    list($edata, $tpl) = $this->_extend($t['id'], TRUE);
                    if (!$edata) {
                        continue;
                    }
                    $file = $this->_remove_domain($edata['url']);
                    if (strpos($file, 'index.php') !== FALSE) {
                        continue;
                    }
                    ob_start();
                    $this->template->display($tpl);
                    $html = ob_get_clean();

                    // 格式化生成文件
                    $hfile = dr_format_html_file($file, $is_mobile);
                    if (!file_put_contents($hfile, $html, LOCK_EX)) {
                        log_message('error', '生成失败: '.$file.'文件写入失败'.$hfile);
                    } else {
                        $filepath = array($hfile);
                        // 保存文件记录
                        $this->models('module/content')->set_html(2, $data['uid'], $data['id'], $t['id'], $data['catid'], $filepath);
                    }
                }
            }
        }

        // 移动端生成
        SITE_MOBILE_HTML && !$is_mobile && $this->_create_show_file($id, 1);

        return TRUE;
    }

    /**
     * 内容页生成静态
     */
    protected function _show_html() {

        // 判断权限
        !dr_html_auth() && exit('权限验证超时，请重新执行生成');

        $end = (int)$this->input->get('end');
        $page = (int)$this->input->get('p');
        $type = $this->input->get('type');
        $type = $type ? $type : 'html';
        $value = $this->input->get('value');
        $catid = $this->input->get('catid');
        $start = (int)$this->input->get('start');
        $total = (int)$this->input->get('total');

        $url = (IS_ADMIN ? ADMIN_URL : '').'index.php?s='.$this->dir.'&c=show&m=html';
        $category = $this->get_cache('module-'.SITE_ID.'-'.$this->dir, 'category');

        if (IS_POST) {
            $data = $this->input->post('data');
            $end = $data['end'];
            $start = $data['start'];

            $all = $cat = array();
            $type = $this->input->post('type');
            foreach ($category as $t) {
                if ($cat['setting']['linkurl']) {
                    continue; // 外链
                }
                if (@in_array($t['id'], $data['catid'])) {
                    $tmp = @explode(',', $t['childids']);
                    $cat = array_merge($cat, $tmp);
                }
                $all[] = $t['id'];
            }
            // 排除不生成的栏目
            $cat = $cat ? $cat : $all;
            $catid = array();
            foreach ($cat as $id) {
                if ($category[$id]['setting']['linkurl']) {
                    continue; // 外链
                }
                if ($type == 'html') {
                    $category[$id]['setting']['html'] && $catid[] = $id;
                } else {
                    $catid[] = $id;
                }
            }
            !$catid && $this->admin_msg('所选栏目没有配置生成功能');
            $catid = @implode(',', $catid);
        }

        if (!$page) {
            $url.= '&p=1&catid='.$catid.'&start='.$start.'&end='.$end.'&type='.$type.'&value='.$value;
            $url.= '&type='.$type;
            $this->admin_msg('正在统计数据...', $url, 2, 0);
        } else {
            $url.= '&type='.$type;
        }

        if ($page == 1 && !$total) {
            $catid && $this->db->where_in('catid', explode(',', $catid));
            $type == 'html' && $this->db->where('status', 9);
            if ($start) {
                $end = $end ? $end : SYS_TIME;
                $this->db->where('`inputtime` between '.$start.' and '.$end);
            }
            $value && $this->db->where('`id` IN ('.$value.')');
            $total = $this->db->count_all_results(SITE_ID.'_'.$this->dir.'_index');
            !$total && $this->admin_msg("无可用数据");
            $msg = '共 '.$total.' 条数据...';
            $url = $url.'&p=1&total='.$total.'&catid='.$catid.'&start='.$start.'&end='.$end.'&type='.$type.'&value='.$value;
            $this->admin_msg($msg, $url, 2, 0);
        }

        if ($type == 'html') {
            $pagesize = 100;// 每次生成数量
            $count = ceil($total/$pagesize); // 计算总页数
            if ($page > $count) {
                $msg = '执行完成';
                $this->admin_msg($msg, '', 1);
            }

            $this->db->where('status', 9);
            $catid && $this->db->where_in('catid', explode(',', $catid));
            if ($start) {
                $end = $end ? $end : SYS_TIME;
                $this->db->where('`inputtime` between '.$start.' and '.$end);
            }
            $value && $this->db->where('`id` IN ('.$value.')');
            $list = $this->db->select('id')->limit($pagesize, $pagesize * ($page - 1))->get(SITE_ID.'_'.$this->dir)->result_array();

            if ($list) {
                foreach ($list as $t) {
                    $this->_create_show_file($t['id']);
                }
            }

            $next = $page + 1;
            $msg = "共{$total}条数据，每页生成{$pagesize}条，正在生成{$count}/{$next}...";
            $url = $url.'&p='.$next.'&total='.$total.'&catid='.$catid.'&start='.$start.'&end='.$end.'&type='.$type.'&value='.$value;
            $this->admin_msg($msg, $url, 2, 0);
        } else {
            $pagesize = 500;// 每次生成数量
            $count = ceil($total/$pagesize); // 计算总页数
            if ($page > $count) {
                $msg = '执行完成';
                $this->admin_msg($msg, '', 1);
            }

            $catid && $this->db->where_in('catid', explode(',', $catid));
            if ($start) {
                $end = $end ? $end : SYS_TIME;
                $this->db->where('`inputtime` between '.$start.' and '.$end);
            }
            $value && $this->db->where('`id` IN ('.$value.')');

            $list = $this->db->limit($pagesize, $pagesize * ($page - 1))->get(SITE_ID.'_'.$this->dir)->result_array();
            if ($list) {
                foreach ($list as $t) {
                    dr_delete_html_file($this->_remove_domain($t['url']));
                    if ($this->dir == 'share' && !$t['mid']) {
                        continue;// 排除异常
                    }
                    $html_table = $this->dir == 'share' ? SITE_ID.'_'.$t['mid'].'_html' : SITE_ID.'_'.$this->dir.'_html';
                    $this->db->where('rid', $t['id'])->where('type', 1)->delete($html_table);
                    if ($this->get_cache('module-'.SITE_ID.'-'.$this->dir, 'extend')) {
                        // 删除扩展内容文件
                        $extend = $this->db
                            ->select('filepath,id')
                            ->where('rid', $t['id'])
                            ->where('type', 2)
                            ->get($html_table)
                            ->result_array();
                        $this->models('module/content')->delete_html_file($extend);
                        $this->db->where('rid', $t['id'])->where('type', 2)->delete($html_table);
                    }
                }
            }

            $next = $page + 1;
            $msg = "共{$total}条数据，正在删除{$count}/{$next}...";
            $url = $url.'&p='.$next.'&total='.$total.'&catid='.$catid.'&start='.$start.'&end='.$end.'&type='.$type.'&value='.$value;
            $this->admin_msg($msg, $url, 2, 0);
        }


    }

    /**
     * 栏目页生成静态
     */
    protected function _category_html() {

        // 判断权限
        !dr_html_auth() && exit('权限验证超时，请重新执行生成');

        $url = (IS_ADMIN ? ADMIN_URL : '').'index.php?'.($this->dir == 'share' ? '' : 's='.$this->dir.'&').'c=category&m=html';
        $key = (int)$this->input->get('key');
        $page = (int)$this->input->get('p');
        $type = $this->input->get('type');
        $type = $type ? $type : 'html';
        $name = 'category_html_'.$this->uid.md5($this->input->ip_address());
        $total = (int)$this->input->get('total');

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);
        $category = $mod['category'];

        if (IS_POST) {
            $all = $cat = array();
            $data = $this->input->post('data');
            $type = $this->input->post('type');
            foreach ($category as $t) {
                if ($cat['setting']['linkurl']) {
                    continue; // 外链
                }
                if ($data['catid'] && in_array($t['id'], $data['catid'])) {
                    $tmp = explode(',', $t['childids']);
                    $cat = array_merge($cat, $tmp);
                }
                $all[] = $t['id'];
            }
            // 排除不生成的栏目
            $cat = $cat ? $cat : $all;
            $catid = array();
            foreach ($cat as $id) {
                if ($category[$id]['setting']['linkurl']) {
                    continue; // 外链
                }
                if ($type == 'html') {
                    $category[$id]['setting']['html'] && $catid[] = $id;
                } else {
                    $catid[] = $id;
                }
            }
            !$catid && $this->admin_msg('所选栏目没有配置生成功能');
            // 生成栏目缓存
            $this->cache->file->save($name, $catid, 99999);
            $url.= '&type='.$type;
            $this->admin_msg('正在统计数量...', $url, 2, 0);
        } else {
            $url.= '&type='.$type;
        }

        $cat = $this->cache->file->get($name);
        !$cat && $this->admin_msg('临时缓存数据不存在，请重新生成栏目');

        $catid = (int)$cat[$key];
        !$catid && $this->admin_msg('执行完毕', '', 1);

        if (!$total) {
            if (!$category[$catid]['child'] ||
                ($category[$catid]['child'] && $category[$catid]['setting']['template']['list'] == $category[$catid]['setting']['template']['category'])) {
                // 生成栏目的列表分页
                $mid = $this->dir == 'share' ? $category[$catid]['mid'] : $this->dir;
                if ($mid) {
                    $total = $category[$catid]['child'] ? $this->db->where_in('catid', @implode(',', $category[$catid]['childids']))->count_all_results(SITE_ID.'_'.$mid) : $this->db->where('catid', $catid)->count_all_results(SITE_ID.'_'.$mid);
                } else {
                    $total = 0;
                }
                if (!$total) {
                    if ($type == 'html') {
                        $this->_create_category_file($catid);
                        $this->admin_msg('栏目【'.$category[$catid]['name'].'】列表无数据，正在生成下一栏目...', $url.'&p=1&total=0&key='.($key+1), 2, 0);
                    } else {
                        dr_delete_html_file($this->_remove_domain($category[$catid]['url']));
                        $this->admin_msg('正在删除栏目【'.$category[$catid]['name'].'】...', $url.'&p=1&total=0&key='.($key+1), 2, 0);
                    }
                }
            } else {
                if ($type == 'html') {
                    // 生成一个栏目的首页
                    $this->_create_category_file($catid);
                    $this->admin_msg('栏目【'.$category[$catid]['name'].'】首页生成成功，正在生成下一栏目...', $url.'&p=1&total=0&key='.($key+1), 2, 0);
                } else {
                    dr_delete_html_file($this->_remove_domain($category[$catid]['url']));
                    $this->admin_msg('正在删除栏目【'.$category[$catid]['name'].'】...', $url.'&p=1&total=0&key='.($key+1), 2, 0);
                }
            }
        }

        if ($this->template->_tname == 'mobile') {
            $pagesize = (int)$category[$catid]['setting']['template']['mpagesize'];// 每页数量
        } else {
            $pagesize = (int)$category[$catid]['setting']['template']['pagesize'];// 每页数量
        }

        $count = ceil($total/$pagesize); // 计算总页数

        if ($type == 'html') {
            for ($i = 0; $i <= 20; $i++) {
                $this->_create_category_file($catid, $page);
                $page > $count && $this->admin_msg('栏目【' . $category[$catid]['name'] . '】列表生成完毕，正在生成下一栏目...', $url . '&p=1&total=0&key=' . ($key + 1), 2, 0);
                $page++;
            }

            $next = $page + 1;

            $this->admin_msg("栏目【{$category[$catid]['name']}】共{$total}条数据，正在生成【{$count}/{$next}】...", $url . '&p=' . $next . '&total=' . $total . '&key=' . $key, 2, 0);
        } else {
            // 多删除2页试试
            for ($i = 0;$i<$count+2; $i++) {
                dr_delete_html_file($this->_remove_domain(dr_category_url($mod, $category[$catid], $i)));
            }
            $this->admin_msg('正在删除栏目【' . $category[$catid]['name'] . '】列表...', $url . '&p=1&total=0&key=' . ($key + 1), 2, 0);
        }

    }

    /**
     * 创建栏目的html文件
     */
    protected function _create_category_file($catid, $page = 0, $is_mobile = 0) {

        // 判断权限
        !dr_html_auth() && exit('权限验证超时，请重新执行生成');

        if (!$catid) {
            log_message('error', '生成失败: catid is null');
            return;
        }

        $mod = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);
        $cat = $mod['category'][$catid];

        // 当此栏目是外链时，不生成！
        if ($cat['setting']['linkurl']) {
            #log_message('error', '生成失败: 当此栏目'.$catid.'是外链');
            return;
        } elseif ($this->dir == 'share' && $cat['tid'] == 2) {
            #log_message('error', '生成失败: 当此栏目'.$catid.'是外链');
            return;
        } elseif (!$cat['setting']['html']) {
            return;// 未开启
        }

        $url = $page > 1 ? dr_category_url($mod, $cat, $page) : $cat['url'];
        if (!$url) {
            log_message('error', '生成失败: 当此栏目'.$catid.'URL不存在');
            return;
        }

        $file = $this->_remove_domain($url);

        if (strpos($file, 'index.php') !== FALSE) {
            log_message('error', '生成失败: 当此栏目'.$catid.'是动态URL【'.$url.'】');
            return;
        }

        ob_start();
        $_GET['page'] = $page;
        define('CT_HTML_FILE', 1);
        $this->template->set_mobile_file($is_mobile);
        $this->_category($catid, NULL, $page);
        $html = ob_get_clean();

        // 格式化生成文件
        $hfile = dr_format_html_file($file, $is_mobile);
        if (!@file_put_contents($hfile, $html, LOCK_EX)) {
            log_message('error', '生成失败: 当此栏目'.$file.'文件写入失败'.$hfile);
            return;
        }

        // 生成栏目的第一页
        if ($page <= 1) {
            $purl = dr_category_url($mod, $cat, '{page}'); // 分页地址
            $hfile = dr_format_html_file(str_replace('{page}', 1, $this->_remove_domain($purl)), $is_mobile);
            !@file_put_contents($hfile, $html, LOCK_EX) && $this->admin_msg('文件写入失败：'.$hfile);
        }

        // 移动端生成
        SITE_MOBILE_HTML && !$is_mobile && $this->_create_category_file($catid, $page, 1);

        return TRUE;
    }


    /**
     * 创建栏目html方法
     */
    public function create_list_html() {
        $this->_create_category_file((int)$this->input->get('id'), 1);
    }

    // 会员中心获取可用字段
    protected function _get_member_field($catid) {

        // 主字段
        $field = $this->get_cache('module-'.SITE_ID.'-'.MOD_DIR, 'field');
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

        // 判断是否审核，若审核的话就不需要显示状态字段
        if (!$this->uid) {
            return $field;
        }

        $field['status'] = array(
            'name' => L('状态'),
            'ismain' => 1,
            'ismember' => 1,
            'fieldname' => 'status',
            'fieldtype' => 'Radio',
            'setting' => array(
                'option' => array(
                    'value' => 9,
                    'options' => L('正常').'|9'.chr(13).L('关闭').'|10'
                ),
                'validate' => array(
                    'tips' => L('关闭状态起内容暂存作用，除自己和管理员以外的人均无法访问'),
                )
            )
        );

        return $field;
    }
}