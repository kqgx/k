<?php

class Cache extends M_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $action = $this->input->get('action');
        if ($action) {
            switch ($action) {
                case 'config':
                    
                    $cache = array(
                        0 => array(
                            'site::cache',
                            'application::cache',
                            'admin/auth::cache',
                            'system::email',
                            'system::sysvar',
                            'system::urlrule',
                            'system::attachment',
                            'system::downservers',
                            'module::cache',
                            'member::cache',
                            // 'menu::cache',
                        ),
                    );
                    
                    // 分模块缓存
                    $model = array(
                            0 => array(
                                    'menu' =>  ['admin']
                                )
                        );
                        
                    // 分站点缓存
                    foreach ($this->site_info as $sid => $t) {
                        $cache[$sid] = array(
                            'site/page::cache',
                            'site/form::cache',
                            'site/block::cache',
                            'site/navigator::cache',
                            'module/tag::cache'
                        );
                    }
                    foreach ($cache as $siteid => $c) {
                        foreach ($c as $t) {
                            list($n, $m) = explode('::', $t);
                            $this->models($n)->$m($siteid, $dir);
                        }
                    }
                    break;
                case 'linkage':
                    foreach ($this->site_info as $sid => $t) {
                        $this->models('site/linkage')->cache($sid);
                    }
                    break;
                case 'data':
                    $this->_clear_data();
                    break;
                case 'search':
                    $module = $this->db->where('disabled', 0)->order_by('displayorder ASC')->get('module')->result_array();
                    if ($module) {
                        foreach ($module as $t) {
                            $site = string2array($t['site']);
                            foreach ($site as $sid => $s) {
                                if ($s['use']) {
                                    $table = $this->db->dbprefix($sid . '_' . $t['dirname'] . '_search');
                                    if (!$this->db->query("SHOW TABLES LIKE '".$table."'")->row_array()) {
                                        continue;
                                    }
                                    $this->db->query('TRUNCATE `'.$table.'`');
                                    $table = $this->db->dbprefix($sid . '_' . $t['dirname'] . '_search_index');
                                    $this->db->query('TRUNCATE `'.$table.'`');
                                }
                            }
                        }
                    }
                    break;
                case 'category':
                    $mod = array(
                        'share',
                    );
                    $module = $this->db->where('disabled', 0)->order_by('displayorder ASC')->get('module')->result_array();
                    if ($module) {
                        foreach ($module as $t) {
                            $mod[] = $t['dirname'];
                        }
                    }
                    foreach ($this->site_info as $siteid => $t) {
                        foreach ($mod as $dirname) {
                            $cache = $this->dcache->get('module-'.$siteid.'-'.$dirname);
                            if (!$cache['category']) {
                                continue;
                            }
                            $table = $this->db->dbprefix($siteid . '_' . $dirname . '_category');
                            if (!$this->db->query("SHOW TABLES LIKE '".$table."'")->row_array()) {
                                continue;
                            }
                            foreach ($cache['category'] as $i => $c) {
                                if ($c['mid']) {
                                    $cache['category'][$i]['total'] = $this->db->where('status', 9)->where('catid', $c['id'])->count_all_results($siteid.'_'.$c['mid'].'_index');
                                } else {
                                    $cache['category'][$i]['total'] = 0;
                                }
                            }
                            foreach ($cache['category'] as $i => $c) {
                                if ($c['child']) {
                                    $arr = explode(',', $c['childids']);
                                    $cache['category'][$c['id']]['total'] = intval($cache['category'][$c['id']]['total']);
                                    foreach ($arr as $i) {
                                        $cache['category'][$c['id']]['total']+= $cache['category'][$i]['total'];
                                    }
                                }
                            }
                            $this->dcache->set('module-'.$siteid.'-'.$dirname, $cache);
                        }
                    }
                    break;
                case 'app':
                    break;
                case 'table':
                    $this->dcache->delete('table');
                    $this->models('system')->cache();
                    break;
            }
            $this->msg(1, L('更新成功'));
        }

        // 应用缓存
        $app = $this->db->select('disabled, dirname')->get('application')->result_array();
        $aurl = array();
        if ($app) {
            foreach ($app as $a) {
                if ($a['disabled'] == 0) {
                    $aurl[] = dr_url($a['dirname'].'/home/cache', array('admin' => 1));
                }
            }
        }

        $this->template->assign(array(
            'aurl' => $aurl,
            'list' => array(
                array('修改后台配置值、栏目、自定义内容等配置时，需要更新才会生效', 'config'),
                array('后台发布文章或修改文章后，前台进行实时显示时更新', 'data'),
                array('重建数据表字段结构，通常在自定义字段之后更新', 'table'),
                array('变更联动菜单数据后，更新联动菜单缓存数据', 'linkage'),
                array('更新模块搜索缓存，搜索不准时更新实时数据', 'search'),
                array('更新应用插件配置缓存', 'app'),
                array('重新统计栏目数据量', 'category'),
            )
        ));
        $this->template->display('cache.html');
    }

    // 更新表结构
    public function dbcache() {
        if (IS_AJAX || $this->input->get('todo')) {
            $this->dcache->delete('table');
            $this->models('system')->cache();
            if (!IS_AJAX) {
                $this->admin_msg(L('数据表结构缓存更新成功'), '', 1);
            }
        } else {
            $this->admin_msg('Clear ... ', dr_url('home/dbcache', array('todo' => 1)), 2);
        }
    }
    
    // 清除缓存数据
    private function _clear_data() {
        
        $this->load->helper('file');
        delete_files(DATAPATH.'sql/');
        delete_files(DATAPATH.'file/');
        delete_files(DATAPATH.'page/');
        delete_files(DATAPATH.'index/');
        delete_files(DATAPATH.'views/');
        
        function_exists('opcache_reset') && opcache_reset();

        // 模块缓存
        $module = $this->db->select('disabled,dirname')->get('module')->result_array();
        if ($module) {
            foreach ($module as $mod) {
                $site = string2array($mod['site']);
                if ($site[SITE_ID]) {
                    $this->db->where('inputtime<>', 0)->delete(SITE_ID.'_'.$mod['dirname'].'_search');
                }
            }
        }
    }
}