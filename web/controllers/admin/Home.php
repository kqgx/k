<?php

class Home extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->output->enable_profiler(FALSE);
    }

    /**
     * 重置
     */
    public function home() {
        $this->index();
    }
    
    public function index(){
        $sites = array();
        foreach ($this->site_info as $sid => $t) {
            if ($this->admin['adminid'] == 1
                || ($this->admin['role']['site'] && in_array($sid, $this->admin['role']['site']))) {
                $sites[$sid] = $t['SITE_NAME'];
            }
        }
        $menus = $this->_get_menu();
        foreach ($menus as $j=>$a) {
            foreach($a['data'] as $k=>$b){
                foreach ($b['left']['link'] as $l=>$c) {
                    if(!$this->is_auth($c['uri'])){
                        unset($menus[$j]['data'][$k]['left']['link'][$l]);
                    }
                }
            }
        }

        $this->template->assign(array(
            'menus' => $menus,
            'sites' => $sites,
        ));
        $this->template->display('index.html');
    }

    /**
     * 菜单缓存格式化
     */
    private function _get_menu() {

        $menu = $this->dcache->get('menu');
        
        $smenu = array();
        if (!$menu) {
            $menu = $this->models('site/menu')->set('admin')->cache();
        }
        
        $mymenu = array();
        foreach ($menu as $t) {
            
            if ($t['mark'] == 'share' && !SYS_SHARE) {
                continue; // 存在共享模块时再显示内容菜单
            } elseif (is_array($t['left'])) {
                $left = array();
                if ($t['mark'] && strpos($t['mark'], 'module-') === 0) {
                    list($a, $dir) = explode('-', $t['mark']);
                    $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
                    if (!$mod) {
                        continue; // 当前站点模块不存在时不显示
                    }
                }
                foreach ($t['left'] as $m) {
                    if (strpos($m['mark'], 'module-') === 0) {
                        // 表示模块
                        list($a, $dir) = explode('-', $m['mark']);
                        $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
                        if (!$mod) {
                            continue; // 当前站点模块不存在时不显示
                        }
                    }
                    $link = array();
                    if (is_array($m['link'])) {
                        foreach ($m['link'] as $n) {
                            if ($n['uri'] == 'admin/odb/index'
                                && !is_file(FCPATH.'controllers/admin/Odb.php')) {
                                continue;
                            }
                            $n['tid'] = $t['id'];
                            if (!$n['uri'] && $n['url']) {
                                $link[] = $n;
                            } elseif ($this->is_auth($n['uri'])) {
                                // 判断模块表单权限
                                if ($n['mark']
                                    && strpos($n['mark'], 'module-') === 0
                                    && strpos($n['uri'], 'admin/form_')
                                    && substr_count($n['mark'], '-') == 3) {
                                    list($a, $mod, $sid, $mid) = explode('-', $n['mark']);
                                    // 判断是否是当前站点
                                    if ($sid != SITE_ID) {
                                        continue;
                                    } elseif (!$this->is_auth($mod.'/admin/home/index')) {
                                        continue; // 判断是否具有内容管理权限
                                    }
                                } elseif (strpos($m['mark'], 'content-form') === 0) {
                                    // 判断网站表单权限
                                    list($a, $b, $table) = explode('-', $n['mark']);
                                    if (!$this->get_cache('form-name-'.SITE_ID, $table)) {
                                        continue;
                                    }
                                }
                                $n['url'] = $this->duri->uri2url($n['uri']);
                                $mymenu[$n['uri']] = $link[] = $n;
                            }
                        }
                    }
                    if ($link || $m['mark'] == 'content-content') {
                        $left[] = array('left' => $m, 'data' => $link);
                    }
                }
                if ($left) {
                    $smenu[$t['id']] = array('top' => $t, 'data' => $left);
                }
            }
        }

        $this->dcache->set('mymenu', $mymenu);

        return $smenu;
    }

    // 初始化系统
    public function init() {

    }

    /**
     * 后台首页
     */
    public function main() {
        // 判断管理员ip状况
        $ip = '';
        $login = $this->db->where('uid', $this->uid)->order_by('logintime desc') ->limit(2)->get('admin_login')->result_array();
        if ($login
            && count($login) == 2
            && $login[0]['loginip'] != $login[1]['loginip']) {
            $this->load->library('dip');
            $now = $this->dip->address($login[0]['loginip']);
            $last = $this->dip->address($login[1]['loginip']);
            if (@strpos($now, $last) === FALSE
                && @strpos($last, $now) === FALSE) {
                // Ip异常判断
                $ip = L('登录IP出现异常，您上次登录IP是%s【%s】，请确认是本人登录，<a href="%s" style="color:blue">账号登录查询</a>', $login[1]['loginip'], $last, dr_url('root/log', array('uid' => $this->uid)));
            }
        }

        // 统计模块数据
        $total = array();
        $module = $this->get_module(SITE_ID);
        if ($module) {
            // 查询模块的菜单
            $mymenu = $this->_get_mymenu();
            foreach ($module as $dir => $mod) {
                // 判断模块表是否存在
                if (!$this->db->query("SHOW TABLES LIKE '%".$this->db->dbprefix(SITE_ID.'_'.$dir.'_verify')."%'")->row_array()) {
                    continue;
                }
                $total[$dir] = array(
                    'name' => L($mod['name']),
                    'today' => $this->_set_k_url($mymenu, $dir.'/admin/home/index', $dir.'/admin/home/index'),
                    'content' => $this->_set_k_url($mymenu, $dir.'/admin/home/index', $dir.'/admin/home/index'),
                    'recycle' => $this->_set_k_url($mymenu, $dir.'/admin/home/recycle', $dir.'/admin/home/recycle'),
                    'add' => $this->_set_k_url($mymenu, $dir.'/admin/home/index', $dir.'/admin/home/add'),
                    'url' => $mod['url'],
                );

            }
            $total['member'] = array(
                'name' => L('会员'),
                'today' => $this->_set_k_url($mymenu, 'admin/member/index', 'admin/member/index'),
                'content' => $this->_set_k_url($mymenu, 'admin/member/index', 'admin/member/index'),
                'recycle' => 'javascript:;',
                'add' => $this->_set_k_url($mymenu, 'admin/member/index', 'admin/member/index'),
                'url' => $this->_set_k_url($mymenu, 'admin/member/index', 'admin/member/index'),
            );
        }

        $server = @explode(' ', strtolower($_SERVER['SERVER_SOFTWARE']));
        if (isset($server[0]) && $server[0]) {
            $server = dr_strcut($server[0], 15);
        } else {
            $server = 'web';
        }

        $notice = $this->db->query('select * from `'.$this->db->dbprefix('admin_notice').'` where ((`to_uid`='.$this->uid.') or (`to_rid`='.$this->member['adminid'].') or (`to_uid`=0 and `to_rid`=0)) and `status`<>3 order by `status` asc, `inputtime` desc limit 10')->result_array();

        $this->template->assign(array(
            'ip' => $ip,
            'sip' => server_ip(),
            'mymain' => 1,
            'mtotal' => $total,
            'server' => ucfirst($server),
            'notice' => $notice,
            'notice_url' => $this->_set_k_url($mymenu, 'admin/notice/index', 'admin/notice/my'),
            'sqlversion' => $this->db->version(),
            'sqdomain' => dr_cms_domain_name($this->site_info[1]['SITE_URL'])
        ));
        $this->template->display('main.html');
    }

    // 域名检查
    public function domain() {
        $ip = server_ip();
        $domain = $this->input->get('domain');
        if (gethostbyname($domain) != $ip) {
            exit(L('请将域名【%s】解析到【%s】', $domain, $ip));
        }
        exit('');
    }

    // 页面统计是的url
    private function _set_k_url($menu, $nuri, $uri) {
        return 'javascript:parent._MAP(\''.intval($menu[$nuri]['id']).'\', \''.intval($menu[$nuri]['tid']).'\', \''.$this->duri->uri2url($uri).'\');';
    }

    // 格式菜单
    private function _get_mymenu() {
        return $this->dcache->get('mymenu');
    }

    // 统计数据
    public function mtotal() {

        // 统计模块数据
        $total = $this->get_cache_data('admin_mtotal_'.SITE_ID);
        $module = $this->get_module(SITE_ID);
        if (!$module) {
            exit;
        }

        if (!$total) {
            // 查询模块的菜单
            $total = $top = array();
            $menu = $this->db
                ->where('pid=0')
                ->where('hidden', 0)
                ->order_by('displayorder ASC,id ASC')
                ->get('admin_menu')
                ->result_array();
            if ($menu) {
                $i = 0;
                foreach ($menu as $t) {
                    list($a, $dir) = @explode('-', $t['mark']);
                    if ($dir && !$module[$dir]) {
                        continue;
                    }
                    $top[$dir] = $i;
                    $i++;
                }
            }
            foreach ($module as $dir => $mod) {
                // 判断模块表是否存在
                if (!$this->db->query("SHOW TABLES LIKE '%".$this->db->dbprefix(SITE_ID.'_'.$dir.'_verify')."%'")->row_array()) {
                    continue;
                }
                //
                $total[$dir] = array(
                    'today' => $this->db->where('status=9')->where('DATEDIFF(from_unixtime(inputtime),now())=0')->count_all_results(SITE_ID.'_'.$dir.'_index'),
                    'content' => $this->db->where('status=9')->count_all_results(SITE_ID.'_'.$dir.'_index'),
                    'content_verify' => 0,
                    'recycle' => $this->db->count_all_results(SITE_ID.'_'.$dir.'_recycle'),
                );
            }
            $total['member'] = array(
                'today' => $this->db->where('DATEDIFF(from_unixtime(regtime),now())=0')->count_all_results('member'),
                'content' => $this->db->count_all_results('member'),
                'recycle' => 0,
            );
            $this->set_cache_data('admin_mtotal_'.SITE_ID, $total, 60);
        }

        if (!$total) {
            exit;
        }

        // AJAX输出
        foreach ($total as $dir => $t) {
            echo '$("#'.$dir.'_today").html('.$t['today'].');';
            echo '$("#'.$dir.'_content").html('.$t['content'].');';
            echo '$("#'.$dir.'_recycle").html('.$t['recycle'].');';
        }

    }
}