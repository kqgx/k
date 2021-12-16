<?php

class Comment extends M_Controller {

    public $cid;
    public $_uri; //
    public $back; // 返回uri
    public $_name; // 评论缓存名称
    public $cname; // 评论名称
    public $_config; // 评论配置
    public $mycfg_file; // 自定义扩展配置
    public $cache_file; // 分页数据缓存

    /**
     * 构造函数
     */

    public function __construct() {
        parent::__construct();
        $this->cache_file = md5($this->duri->uri(1).$this->uid.$this->sid.$this->input->ip_address().$this->input->user_agent()); // 缓存文件名称
        $this->module(APP_DIR);
    }

    // 设置模块操作评论
    public function module($dir) {
        $this->_uri = $dir.'/admin/comment/';
        $this->back = $this->_get_back_url($dir.'/home/index');
        $this->_name = 'comment-module-'.$dir;
        $this->cname = L('内容评论');
        $this->models('module/comment')->module($dir, $this->_name);
        $this->_config = $this->get_cache('comment', $this->_name);
        $this->mycfg_file = WEBPATH.'module/'.$dir.'/views/admin/my_comment.html';
    }

    // 评论配置
    public function config() {

        $page = intval($_GET['page']);

        if (IS_POST) {
            $data = $this->input->post('data');
            $this->db->where('name', $this->_name)->update('comment', array(
                'value' => array2string($data),
            ));
            $page = intval($_POST['page']);
            $this->models('system')->comment();
        } else {
            $data = $this->db->where('name', $this->_name)->get('comment')->row_array();
            if (!$data) {
                $this->db->insert('comment', array(
                    'name' => $this->_name,
                    'value' => '',
                    'field' => '',
                ));
            }
            $data = string2array($data['value']);
        }

        $this->template->assign(array(
            'data' => $data,
            'page' => $page,
            'menu' => $this->get_menu_v3(array(
                L('%s配置', $this->cname) => array($this->_uri.'config', 'cog'),
                L('%s字段', $this->cname) => array('admin/field/index/_name/'.$this->_name.'/rid/0', 'plus'),
            )),
            'mycfg' => is_file($this->mycfg_file) ? $this->mycfg_file : 0,
            'myfield2' => $this->db->where('disabled', 0)->where('relatedid', 0)->where('relatedname', $this->_name)->order_by('displayorder ASC, id ASC')->get('field')->result_array(),
        ));
        $this->template->display('comment_config.html');
    }

    // 评论管理
    public function index() {
        $tid = (int)$this->input->get('tid');
        $cid = $this->cid = (int)$this->input->get('cid');
        $index = array();

        if ($cid) {
            // 从内容处进来时
            list($table, $index) = $this->models('Module/comment')->get_table($cid, 1);
            $menu = array(
                L('返回') => array($this->back, 'mail-reply'),
                L('%s管理', $this->cname) => array($this->_uri.'index/cid/'.$cid, 'comments'),
            );
            $show_url = $this->duri->uri2url($this->_uri.'show/cid/'.$cid);
        } else {
            // 全部数据时
            $table = $this->models('Module/comment')->prefix.'_comment_data_';
            
            $menu = array(
                L('%s - 默认存储表', $this->cname) => array($this->_uri.'index/tid/0', 'database'),
            );
            for ($i = 1; $i < 100; $i ++) {
                if (!$this->models('Module/comment')->link->query("SHOW TABLES LIKE '".$table.$i."'")->row_array()) {
                    break;
                }
                $menu[L('归档【%s】表', $i)] = array($this->_uri.'index/tid/'.$i, 'database');
            }
            $table.= $tid;
            $show_url = $this->duri->uri2url($this->_uri.'show/tid/'.$tid);
        }

        if ($this->input->post('action') == 'verify') {
            // 审核操作
            $ids = $this->input->post('ids');
            !$ids && $this->$this->json(0, L('您还没有选择呢'));
            foreach ($ids as $i) {
                $this->models('Module/comment')->verify($table, intval($i));
            }
            $this->$this->json(1, L('操作成功，正在刷新...'));
        } elseif ($this->input->post('action') == 'del') {
            // 删除操作
            $ids = $this->input->post('ids');
            !$ids && $this->$this->json(0, L('您还没有选择呢'));
            foreach ($ids as $i) {
                $row = $this->models('Module/comment')->link->where('id', intval($i))->get($table)->row_array();
                $row && $this->models('Module/comment')->del($i, $row['cid']);
            }
            $this->$this->json(1, L('操作成功，正在刷新...'));
        }

        $field = array(
            'content' => array(
                'name' => L('关键字'),
                'fieldname' => 'content',
            ),
            'title' => array(
                'name' => L('主题名称'),
                'fieldname' => 'title',
            ),
            'cid' => array(
                'name' => L('内容Id'),
                'fieldname' => 'cid',
            )
        );
        $field = $this->_config['field'] ? array_merge($field, $this->_config['field']) : $field;

        // 数据库中分页查询
        list($data, $total, $param)	= $this->limit_page($table);
        $param['cid'] = $cid;
        $param['tid'] = $tid;
        $param['total'] = $total;

        $this->load->library('dip');
        $tpl = FCPATH.'views/admin/'.str_replace('-', '_', $this->_name).'.html';

        $this->template->assign(array(
            'uri' => $this->_uri,
            'tpl' => str_replace(FCPATH, '/', $tpl),
            'menu' => $this->get_menu_v3($menu),
            'list' => $data,
            'total' => $total,
            'pages'	=> $this->get_pagination(dr_url(str_replace('/admin', '', $this->_uri).'index', $param), $total),
            'param' => $param,
            'index' => $index,
            'field' => $field,
            'show_url' => $show_url,
        ));
        $this->template->display(is_file($tpl) ? basename($tpl) : 'comment_index.html');
    }

    // 查看
    public function show() {

        $id = (int)$this->input->get('id');
        $tid = (int)$this->input->get('tid');
        $cid = $this->cid = (int)$this->input->get('cid');

        if ($cid) {
            // 从内容处进来时
            $table = $this->models('module/comment')->get_table($cid);
            $menu = array(
                L('返回') => array($this->back, 'reply'),
                L('评论管理') => array($this->_uri.'index/cid/'.$cid, 'comments'),
            );
            $show_url = $this->_uri.'show/cid/'.$cid;
        } else {
            // 全部数据时
            $table = $this->models('Module/comment')->prefix.'_comment_data_';
            $menu = array(
                L('默认存储表') => array($this->_uri.'index/tid/0', 'database'),
            );
            for ($i = 1; $i < 100; $i ++) {
                if (!$this->models('Module/comment')->link->query("SHOW TABLES LIKE '".$table.$i."'")->row_array()) {
                    break;
                }
                $menu[L('归档【%s】表', $i)] = array($this->_uri.'index/tid/'.$i, 'database');
            }
            $table.= $tid;
            $show_url = $this->_uri.'show/tid/'.$tid;
        }

        $data = $this->models('Module/comment')->link->where('id', $id)->get($table)->row_array();
        // 数据验证
        !$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));

        if (IS_POST) {
            $update = $this->input->post('post');
            if ($this->_config['field']) {
                $my = $this->validate_filter($this->_config['field']);
                isset($my['error']) && $this->admin_msg($my['msg']);
                $update = array_merge($update, $my[1]);
            }
            $status = isset($update['status']) && $update['status'] ? 1 : 0;
            unset($update['status']);
            $this->models('Module/comment')->link->where('id', $id)->update($table, $update);
            // 操作成功处理附件
            if ($data['uid'] && $my) {
                $this->attachment_handle(
                    $data['uid'],
                    $table.'-'.$id,
                    $this->_config['field'],
                    $my
                );
            }
            // 审核操作
            if ($status) {
                $this->models('Module/comment')->verify($table, $id);
                // 任务状态
                $this->models('member')->update_admin_notice($this->_uri.'show/tid/'.$tid.'/id/'.$id, 3);
            }

            $this->admin_msg(
                L('操作成功，正在刷新...'),
                $this->duri->uri2url($show_url.'/id/'.$id),
                1,
                1
            );
        }

        $menu[L('查看/修改')] = array($show_url.'/id/'.$id, 'edit');
        $this->load->library('dip');
        $tpl = APPPATH.'views/admin/'.str_replace('-', '_', $this->_name).'_show.html';

        $this->template->assign(array(
            'tpl' => str_replace(FCPATH, '/', $tpl),
            'data' => $data,
            'menu' => $this->get_menu_v3($menu),
            'myfield' => $this->new_field_input($this->_config['field'], $data),
        ));
        $this->template->display(is_file($tpl) ? basename($tpl) : 'comment_show.html');
    }

    /**
     * 条件查询
     *
     * @param	object	$select	查询对象
     * @param	intval	$where	是否搜索
     * @return	intval
     */
    protected function _where(&$select, $param) {

        // 存在POST提交时
        if (IS_POST) {
            $search = $this->input->post('data');
            $param['keyword'] = $search['keyword'];
            $param['start'] = $search['start'];
            $param['end'] = $search['end'];
            $param['field'] = $search['field'];
        }

        // 相对于内容
        $this->cid && $select->where('cid', $this->cid);

        // 存在search参数时，读取缓存文件
        if ($param) {
            if (isset($param['keyword']) && $param['keyword'] != '') {
                $field = $this->field;
                $param['field'] = $param['field'] ? $param['field'] : 'content';
                if ($param['field'] == 'id' || $param['field'] == 'cid') {
                    // 按id查询
                    $id = array();
                    $ids = explode(',', $param['keyword']);
                    foreach ($ids as $i) {
                        $id[] = (int)$i;
                    }
                    $select->where_in($param['field'], $id);
                } elseif ($field[$param['field']]['fieldtype'] == 'Linkage'
                    && $field[$param['field']]['setting']['option']['linkage']) {
                    // 联动菜单搜索
                    if (is_numeric($param['keyword'])) {
                        // 联动菜单id查询
                        $link = dr_linkage($field[$param['field']]['setting']['option']['linkage'], (int)$param['keyword'], 0, 'childids');
                        $link && $select->where($param['field'].' IN ('.$link.')');
                    } else {
                        // 联动菜单名称查询
                        $id = (int)$this->ci->get_cache('linkid-'.SITE_ID, $field[$param['field']]['setting']['option']['linkage']);
                        $id && $select->where($param['field'].' IN (select id from `'.$select->dbprefix('linkage_data_'.$id).'` where `name` like "%'.$param['keyword'].'%")');
                    }
                } else {
                    $select->like($param['field'], urldecode($param['keyword']));
                }
            }
            // 时间搜索
            if (isset($param['start']) && $param['start']) {
                $param['end'] = strtotime(date('Y-m-d 23:59:59', $param['end'] ? $param['end'] : SYS_TIME));
                $param['start'] = strtotime(date('Y-m-d 00:00:00', $param['start']));
                $select->where('inputtime BETWEEN ' . $param['start'] . ' AND ' . $param['end']);
            } elseif (isset($param['end']) && $param['end']) {
                $param['end'] = strtotime(date('Y-m-d 23:59:59', $param['end']));
                $param['start'] = 0;
                $select->where('inputtime BETWEEN ' . $param['start'] . ' AND ' . $param['end']);
            }
        }

        return $param;
    }

    /**
     * 数据分页显示
     *
     * @return	array
     */
    protected function limit_page($table) {

        if (IS_POST) {
            $page = $_GET['page'] = 1;
            $total = 0;
        } else {
            $page = max(1, (int)$this->input->get('page'));
            $total = (int)$this->input->get('total');
        }

        $param = $this->input->get(NULL);
        unset($param['s'],$param['c'],$param['m'],$param['d'],$param['page']);

        if (!$total) {
            $select	= $this->db->select('count(*) as total');
            $param = $this->_where($select, $param);
            $data = $select->get($table)->row_array();
            unset($select);
            $total = (int)$data['total'];
            if (!$total) {
                return array(array(), $total, $param);
            }
        }

        $select	= $this->db->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1));
        $param = $this->_where($select, $param);
        $_order = isset($_GET['order']) && strpos($_GET['order'], "undefined") !== 0 ? $this->input->get('order') : 'inputtime DESC';
        $data = $select->order_by('status asc,'.$_order)->get($table)->result_array();

        return array($data, $total, $param);
    }
}
