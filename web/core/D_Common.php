<?php

class D_Common extends CI_Controller {

    public $site; // 站点数据库对象
    public $site_info; // 站点信息数组
    public $branch; // 分支系统信息
    public $admin; // 管理员信息
    public $member; // 当前登录会员信息
    public $pagesize; // 会员中心分页数量
    public $markrule; // 会员权限规则标识
    public $member_rule; // 会员权限规则
    public $module_rule; // 会员模块权限规则
    public $mobile; // 是否手机端
    public $uid; // 当前登录的uid
    public $my_city; // 当前的定位城市
    public $my_position; // 当前的定位坐标
    public $module;

    // 临时变量
    public $post;
    public $data;
    public $dir;
    public $mark;
    public $replace_lang = 1;
    private $_auth;
    private $_link;
    private $_temp;

    /**
     * 构造函数
     */

    public function __construct() {
        parent::__construct();

        // 初始化环境和数据库
        $this->replace_lang = 1;
        $this->load->database();
        $this->load->library('user_agent');
        $this->_init_variable();
        $this->lang->load('my');
        $this->template->ci = $this;
        if ($r = get_cookie('my_position')) {
            list($this->my_position['lng'], $this->my_position['lat']) = explode(',', $r);
        }
        $this->my_city = get_cookie('my_city');
        $this->template->assign(array(
            'get' => $this->input->get(NULL, TRUE),
            'member' => $this->member,
            'dirname' => APP_DIR,
            'my_city' => $this->my_city,
            'markrule' => $this->markrule,
            'site_info' => $this->site_info,
            'is_mobile' => $this->template->mobile,
            'my_position' => $this->my_position,
            'member_rule' => $this->member_rule,
            'module_rule' => $this->module_rule,
        ));
    }

    /**
     * 清除系统缓存
     *
     * @param	string	$name	缓存名称
     * @return  void
     */
    public function clear_cache($name) {
        if (!$name) {
            return;
        }
        $name = strtolower($name);
        // 模块缓存时，清除所有相关文件
        if ($name == 'module' && is_memcache() && $this->cache->memcached->is_supported()) {
            $data = $this->get_cache('module');
            if ($data) {
                foreach ($data as $site => $t) {
                    if ($t) {
                        foreach ($t as $m) {
                            $this->cache->memcached->delete(SYS_KEY.'module-'.$site.'-'.$m);
                        }
                    }
                }
            }
        }
        // 删除文件缓存
        $this->cache->file->delete($name);
        return;
    }

    /**
     * 系统缓存读取
     *
     * @param	string	$name	缓存名称
     * @param	string	$var1	缓存数组的参数变量1
     * @param	string	$var2	缓存数组的参数变量2
     * @param	string	$varN	缓存数组的参数变量N
     * @return  array
     */
    public function get_cache() {
        $param = func_get_args();
        if (!$param) {
            return NULL;
        }
        // 取第一个参数作为缓存变量名称
        $data = $model = NULL;
        $name = strtolower(array_shift($param));
        if (!$data) {
            $var = 'cache-'.$name;
            if (isset($this->$var) && $this->$var) {
                // 读取全局变量
                $data = $this->$var;
            } else {
                // 读取本地文件缓存数据
                $data = $this->$var = $this->dcache->get($name);
            }
        }
        if (!$param) {
            return $data;
        }
        $var = '';
        foreach ($param as $v) {
            $var.= '[\''.safe_replace($v).'\']';
        }
        $return = NULL;
        @eval('$return = $data'.$var.';');
        return $return;
    }

    /**
     * 临时数据缓存读取
     *
     * @param	string	$name	缓存名称
     * @return  array
     */
    public function get_cache_data($name) {
        if (!$name) {
            return NULL;
        } elseif (defined('SYS_AUTO_CACHE') && !SYS_AUTO_CACHE) {
            // 禁用缓存
            return NULL;
        }
        return is_memcache() && $this->cache->memcached->is_supported() ? $this->cache->memcached->get(SYS_KEY.$name) : @$this->cache->file->get($name);
    }

    /**
     * 临时数据缓存
     *
     * @param	string	$name	缓存名称
     * @param	array	$data	缓存数据
     * @param	intval	$ttl	时间（秒）
     * @return  array
     */
    public function set_cache_data($name, $data, $ttl = 10) {

        if (defined('SYS_AUTO_CACHE') && !SYS_AUTO_CACHE) {
            // 禁用缓存
            return $data;
        }

        $ttl = (int)$ttl;

        if (!$name || !$ttl) {
            return $data;
        }

        is_memcache() && $this->cache->memcached->is_supported() ? $this->cache->memcached->save(SYS_KEY.$name, $data, $ttl) : $this->cache->file->save($name, $data, $ttl);

        return $data;
    }

    // 模块缓存互数据
    public function get_module($siteid = 1) {

        $mod = array();
        $MOD = $this->get_cache('module', $siteid);
        if ($MOD) {
            foreach ($MOD as $dir) {
                $mod[$dir] = $this->get_cache('module-'.$siteid.'-'.$dir);
            }
        }

        return $mod;
    }

    /**
     * 初始化网站全局变量
     */
    private function _init_variable() {

        define('IS_AJAX', $this->input->is_ajax_request());
        define('IS_POST', $_SERVER['REQUEST_METHOD'] == 'POST' && count($_POST) ? TRUE : FALSE);
        define('SYS_TIME', $_SERVER['REQUEST_TIME'] ? $_SERVER['REQUEST_TIME'] : time());
        define('SITE_PATH', '/');

        // 全局化站点变量
        $config1 = require CONFPATH.'system.php'; // 加载网站系统配置文件
        $config2 = array(); // 加载系统版本更新文件
        $config1['SYS_CMS'] = $config1['SYS_CMS'] ? $config1['SYS_CMS'] : $config2['DR_NAME'];
        $config1['SYS_NAME'] = $config1['SYS_NAME'] ? $config1['SYS_NAME'] : 'IMTCMS';
        $config2['DR_NAME'] = $config1['SYS_CMS'];
        $config3 = array_merge($config1, $config2); // 合并配置数组

        define('SYS_HTTPS', $config3['SYS_HTTPS']);

        $this->load->library('session');

        $domain = require CONFPATH.'domain.php'; // 加载站点域名配置文件
        $sitecfg = directory_map(CONFPATH.'site/'); // 加载全部站点的配置文件
        $this->client_domain = array();
        if (is_file(CONFPATH.'client_domain.php')) {
            $this->client_domain = require CONFPATH.'client_domain.php';
        }
        foreach ($sitecfg as $file) {
            $id = (int)basename($file);
            if (is_file(CONFPATH.'site/'.$file) && $id > 0) {
                $this->site[$id] = & $this->db;
                $this->site_info[$id] = require CONFPATH.'site/'.$file;
                $this->site_info[$id]['SITE_ID'] = (int) $id;
                $this->site_info[$id]['SITE_PC'] = $this->site_info[$id]['SITE_URL'] = dr_http_prefix(($this->site_info[$id]['SITE_DOMAIN'] ? $this->site_info[$id]['SITE_DOMAIN'] : DOMAIN_NAME).SITE_PATH);
                $this->site_info[$id]['SITE_MURL'] = dr_http_prefix(($this->site_info[$id]['SITE_MOBILE'] ? $this->site_info[$id]['SITE_MOBILE'] : DOMAIN_NAME).SITE_PATH);
            }
        }
        unset($sitecfg);

        // 判断手机端与PC端模板
        $this->mobile = 0;

        // 分析站点信息
        if (isset($domain[DOMAIN_NAME]) && isset($this->site_info[$domain[DOMAIN_NAME]])) {
            // 通过域名来获取siteid
            $siteid = (int)$domain[DOMAIN_NAME];
            $orthers = @explode(',', $this->site_info[$siteid]['SITE_DOMAINS']);
            $this->site_info[$siteid]['SITE_M_URL'] = $this->site_info[$siteid]['SITE_PC'] = $this->site_info[$siteid]['SITE_URL']; // PC端为主域名
            $uri = isset($_SERVER['HTTP_X_REWRITE_URL']) && trim($_SERVER['REQUEST_URI'], '/') == SELF ? trim($_SERVER['HTTP_X_REWRITE_URL'], '/') : ($_SERVER['REQUEST_URI'] ? trim($_SERVER['REQUEST_URI'], '/') : '');
            if ($orthers && DOMAIN_NAME != $this->site_info[$siteid]['SITE_DOMAIN'] && in_array(DOMAIN_NAME, $orthers)) {
                // 判断当前域名为“其他域名”
                // 301 转向开启
                defined('SITE_URL_301') && SITE_URL_301 && redirect(dr_http_prefix($this->site_info[$siteid]['SITE_DOMAIN'].'/'.$uri), '', '301');
                $this->site_info[$siteid]['SITE_PC'] = $this->site_info[$siteid]['SITE_URL'] = dr_http_prefix(DOMAIN_NAME.'/');
            } elseif (isset($this->site_info[$siteid]['SITE_MOBILE']) && $this->site_info[$siteid]['SITE_MOBILE']) {
                // 当前网站存在移动端域名时
                if (DOMAIN_NAME == $this->site_info[$siteid]['SITE_MOBILE']) {
                    // 当此域名是移动端域名时重新赋值给主站URL
                    $this->site_info[$siteid]['SITE_URL'] = dr_http_prefix(DOMAIN_NAME.'/');
                    $this->site_info[$siteid]['SITE_MOBILE'] = TRUE;
                    $this->mobile = 1;
                } elseif (is_mobile()
                    && $this->site_info[$siteid]['SITE_MOBILE_OPEN']
                    && $this->site_info[$siteid]['SITE_MOBILE']
                    && DOMAIN_NAME == $this->site_info[$siteid]['SITE_DOMAIN']
                    && !IS_ADMIN) {
                    // 当网站开启强制定向时，并且存在移动端域名、URL是主站的域名、非后台
                    redirect(dr_http_prefix($this->site_info[$siteid]['SITE_MOBILE'].'/'.$uri), '', '301');
                    exit;
                }
            } elseif ($this->agent->is_mobile() && $this->site_info[$siteid]['SITE_MOBILE_OPEN']) {
                // 识别移动端
                $this->site_info[$siteid]['SITE_MOBILE'] = TRUE;
                $this->mobile = 1;
            }
            define('SITE_ID', $siteid);
        } else {
            // 识别移动端
            if ($this->agent->is_mobile() && $this->site_info[1]['SITE_MOBILE_OPEN']) {
                $this->site_info[1]['SITE_PC'] = $this->site_info[1]['SITE_URL'];
                $this->site_info[1]['SITE_MOBILE'] = TRUE;
                $this->mobile = 1;
            }
            // 默认站点id
            define('SITE_ID', 1);
        }

        // 移动端入口识别
        defined('IS_MOBILE_SELF') && !$this->mobile &&  $this->mobile = 1;

        $this->site_info[SITE_ID] && $config3 = array_merge($config3, $this->site_info[SITE_ID]);

        // 后台域名
        if (IS_ADMIN && is_file(CONFPATH.'admin_domain.php')) {
            $admin_domain = require CONFPATH.'admin_domain.php';
            if (isset($admin_domain[SITE_ID]) && $admin_domain[SITE_ID]) {
                if (dr_cms_domain_name($admin_domain[SITE_ID]) != dr_cms_domain_name(DOMAIN_NAME)) {
                    // 非后台域名时,禁止访问
                    exit('系统禁止访问');
                }
                define('ADMIN_URL', dr_http_prefix(DOMAIN_NAME.'/'));
            } else {
                define('ADMIN_URL', dr_http_prefix(DOMAIN_NAME.'/'));
            }
        } else {
            define('ADMIN_URL', dr_http_prefix(DOMAIN_NAME.'/'));
        }

        // 附件域名
        $config3['SITE_FID'] = isset($config3['SITE_FID']) ? $config3['SITE_FID'] : 0;
        $config3['SITE_LID'] = isset($config3['SITE_LID']) ? $config3['SITE_LID'] : 0;
        // 将配置文件数组转换为常量
        foreach ($config3 as $var => $value) {
            if ($var == 'SITE_MOBILE' && $value === TRUE && IS_ADMIN) {
                $value = '';
            } elseif (($var == 'SITE_THEME' && $value =='SITE_THEME')
                || ($var == 'SITE_TEMPLATE' && $value =='SITE_TEMPLATE')) {
                $value = 'default';
            } elseif (strpos($value, 'SYS_CACHE') === 0) {
                $value = (int)$value;
            }
            !defined($var) && define($var, $value); // 定义站点常量
        }
        unset($config3, $config2, $config1);

        define('UEDITOR_IMG_ID', md5(SYS_ATTACHMENT_URL));
        define('THEME_PATH', '/statics/');

        // 定义pc端域名
        !defined('SITE_PC') && define('SITE_PC', SITE_URL);
        !defined('SITE_M_URL') && define('SITE_M_URL', $this->site_info[SITE_ID]['SITE_MURL']);


        // 是否移动端
        define('IS_MOBILE', $this->mobile);
        // 是否pc端
        define('IS_PC', !$this->mobile);
        // 终端ID
        !defined('SITE_CLIENT_ID') && define('SITE_CLIENT_ID', 0);

        // 显示错误提示
        SYS_DEBUG && error_reporting(E_ALL ^ E_NOTICE);

        $this->config->set_item('language', SITE_LANGUAGE); // 网站语言
        date_default_timezone_set('Etc/GMT'.(SITE_TIMEZONE > 0 ? '-' : '+').abs(SITE_TIMEZONE)); // 设置时区

        // 网站风格地址
        define('HOME_THEME_PATH', strpos(SITE_THEME, 'http') === 0 ? trim(SITE_THEME, '/').'/' : THEME_PATH.SITE_THEME.'/');
        define('HOME_MOBILE_PATH', HOME_THEME_PATH);

        define('MEMBER_URL', $url);
        define('MEMBER_PATH', '');
        define('MEMBER_THEME', SITE_THEME);
        define('MEMBER_TEMPLATE', SITE_TEMPLATE);
        define('MEMBER_THEME_PATH', strpos(MEMBER_THEME, 'http') === 0 ? trim(MEMBER_THEME, '/').'/' : THEME_PATH.MEMBER_THEME.'/');
        define('MEMBER_MOBILE_PATH', MEMBER_THEME_PATH);

        define('SYS_UPLOAD_PATH', MTBASE . SYS_UPLOAD_DIR);

        $this->load->library('template');
        $this->template->mobile = $this->mobile;

        $this->_init_member();

        // 当网站处于关闭状态时，非管理员无法访问
        defined('SITE_CLOSE') && SITE_CLOSE && !IS_ADMIN
        && !$this->member['adminid']
        && $this->router->class != 'api'
        && !($this->router->class == 'home' && $this->router->method == 'captcha')
        && $this->msg(0, SITE_CLOSE_MSG);
    }

    // 判断是否会员登录
    private function _is_login_member() {
        if ($this->router->class.'-'.$this->router->method == 'pay-call') {
            return 0;
        }
        if (!defined('DR_UEDITOR')
            && !defined('DR_PAY_ID')
            && !in_array($this->router->class, array('register', 'login', 'api', 'oauth'))) {
            return 1;
        }
        return 0;
    }

    /**
     * 判断是否具有操作权限
     *
     * @param	string	$uri
     * @return	bool	有权限返回TRUE，否则返回FALSE
     */
    public function is_auth($uri) {

        // 管理员组不验证,表示通过
        if (!$this->admin || $this->admin['adminid'] == 1) {
            return TRUE;
        }

        $uri = trim(substr_count($uri, '/') == 1 ? 'admin/'.$uri : $uri, '/');
        // 后台首页不验证
        if (in_array($uri, array(
            'admin/home/main',
            'admin/home/index',
            'admin/home/clear',
            'admin/home/cache',
            'admin/root/my',
        ))) {
            return TRUE;
        }

        $role = is_array($this->admin['color']) && $this->admin['color'] ? $this->admin['color'] : array_merge(
            $this->admin['role']['system'],
            $this->admin['role']['module'],
            $this->admin['role']['application']
        );

        if (!$role) {
            return FALSE;
        }

        // uri在当前角色的权限列表中就验证通过
        if (in_array($uri, $role)) {
            return TRUE;
        }

        $route = '/'; // 把uri转为标准路由
        $data = $this->duri->uri2ci($uri);
        $data['dir'] = $data['app'] ? $data['app'] : ($data['path'] ? $data['path'] : '');
        $data['dir'] && $route .= $data['dir'].'/';
        $data['directory'] && $route .= $data['directory'].'/';
        APP_DIR && strpos($route, 'admin') === false && $route.='admin/';
        $data['class'] && $route .= $data['class'].'/';
        $data['method'] && $route .= $data['method'].'/';
        $route = trim($route, '/');

        // 新的uri在当前角色的权限列表中就验证通过
        if (in_array($route, $role)) {
            return TRUE;
        }

        // 跳过自定义字段权限配置，把它放到各个模块单独划分权限
        if (in_array($route, array(
            'admin/field/add',
            'admin/field/del',
            'admin/field/edit',
        ))) {
            return TRUE;
        }

        $this->_link = $this->_link ? $this->_link : $this->dcache->get('link');
        $this->_auth = $this->_auth ? $this->_auth : $this->models('admin/auth')->get_auth_all();

        // uri不在权限所有列表中就不验证,表示通过
        if (!isset($this->_auth[$uri]) && !isset($this->_auth[$route]) && !isset($this->_link[$uri])) {
            return TRUE;
        }
        return FALSE;
    }

    // 获取表字段结构
    public function get_table_field($table) {
        $field = $this->get_cache_data('field-'.$table);
        if (!$field) {
            $tableinfo = $this->models('system')->cache(); // 表结构缓存
            $field = $tableinfo[$table]['field'];
            $this->set_cache_data('field-'.$table, $field, 3600);
        }
        return $field;
    }

    // 获取自定义控制器
    public function get_sysc($id) {
        return $this->db->where('id', $id)->limit(1)->get('controller')->row_array();
    }

    /**
     * 后台操作界面中的顶部导航菜单
     *
     * @param	array	$menu
     * @return	string
     */
    protected function get_menu_v3($menu) {

        if (!$menu) {
            return NULL;
        }
        $_i = 1;
        $_cur = ''; // 表示当前菜单
        $_uri = $this->duri->uri(1); // 当前uri
        $_name = ''; // 表示当前菜单名称
        $_link = '';
        $_first = ''; // 第一个菜单的名称
        $_quick = array(); // 作为快捷菜单

        foreach ($menu as $name => $t) {
            $uri = $t[0];
            $uri = trim($uri, '/'); // 格式菜单uri
            if (!$name || !$uri) {
                continue;
            }
            // 获取URL
            if (substr($uri, (int)strpos($uri, '_js')) == '_js') {
                $uri = substr($uri, 0, -3);
                $url = dr_dialog_url($this->duri->uri2url($uri), 'add');
            } elseif (strpos($uri, 'javascript') === 0) {
                $url = $uri;
            } elseif (strpos($uri, SELF) !== FALSE) {
                $url = $uri;
            } else {
                $url = $this->duri->uri2url($uri);
            }
            // 验证URI权限
            if (!$this->is_auth($uri)) {
                continue;
            }
            // 判断选中的当前菜单
            if ($_i > 1 && $this->get_menu_calss($menu, $uri, $_uri)) {
                $_cur = $name;
                $class = ' class="blue"';
            } elseif ($_i == 1) {
                $class = ' class="{MARK}"';
            } else {
                $class = '';
            }
            $_first = $_i == 1 ? $name : $_first;
            // 快捷菜单
            $_quick[] = array(
                'url' => $url,
                'name' => $name,
                'icon' => '<i class="fa fa-'.$t[1].'"></i>',
            );
            // 生成链接
            $_link.= '<li> <a href="'.$url.'" '.$class.'>'.($t[1] ? '<i class="fa fa-'.$t[1].'"></i> ' : '').$name.'</a> <i class="fa fa-circle"></i> </li>';
            $_i ++;
        }

        // 修改菜单时
        // if (!$_cur && $this->router->method == 'edit') {
        //     $_link.= '<li><a href="javascript:;" class="blue"><i class="fa fa-edit"></i> '.L('修改').'</a> <i class="fa fa-circle"></i> ';
        //     $_cur = L('修改');
        // }

        if (!$_cur) {
            $_link = str_replace('{MARK}', 'blue', $_link);
            !$_name && $_name = $_first;
        } else {
            $_name = $_cur;
        }

        return array(
            'name' => $_name,
            'link' => $_link,
            'quick' => $_quick,
        );
    }

    /**
     * 后台操作界面中的顶部导航菜单
     *
     * @param	array	$menu
     * @return	string
     */
    protected function get_menu($menu) {

        if (!$menu) {
            return NULL;
        }

        $_i = 0;
        $_str = '';
        $_uri = $this->duri->uri(1); // 当前uri
        $_mark = TRUE;

        foreach ($menu as $name => $t) {
            if (is_array($t)) {
                $uri = $t[0];
                $name = '<i class="fa fa-'.$t[1].'"></i> '.$name;
            } else {
                $uri = $t;
            }
            $uri = trim($uri, '/');
            if (!$name || !$uri) {
                continue;
            }
            $class = '';
            if (strpos($uri, '_js') !== FALSE) {
                $uri = substr($uri, 0, -3);
                $url = dr_dialog_url($this->duri->uri2url($uri), 'add');
            } elseif (strpos($uri, 'javascript') === 0) {
                $url = $uri;
            } else {
                $url = $this->duri->uri2url($uri);
                $class = ' class="onloading '.(!$_i ? '{FIR}' : '').'"';
            }
            if (!$this->is_auth($uri)) {
                continue;
            }
            $mark = $_i == 0 ? '{MARK}' : '';
            // 判断选中
            if ($this->get_menu_calss($menu, $uri, $_uri)) {
                $_mark = FALSE;
                $class = ' class="onloading on"';
            }
            $class = strpos($url, 'target') ? '' : $class;
            $_str.= '<a href="'.$url.'" '.$class.$mark.'><em>'.$name.'</em></a><span>|</span>';
            $_i ++;
        }

        if ($_mark && $this->router->method == 'edit') {
            $_str.= '<a href="javascript:;" class="on"><em>'.L('修改').'</em></a><span>|</span>';
            $_mark = FALSE;
        }

        $_str = $_mark ? str_replace('{FIR}', 'on', $_str) : $_str;

        return $_mark ? str_replace('{MARK}', ' class="on"', $_str) : str_replace('{MARK}', '', $_str);
    }

    private function get_menu_calss($menu, $uri, $_uri) {

        if ($uri == $_uri) {
            return TRUE;
        }

        if (!in_array($_uri, $menu)) {
            if (@strpos($_uri, $uri) === FALSE) {
                return FALSE;
            }
            $uri_arr = explode('/', $_uri);
            $uri_arr = array_slice($uri_arr, 0, -2);
            $__uri = implode('/', $uri_arr);
            if (in_array($__uri, $menu) && $__uri == $uri) {
                return TRUE;
            }
            if ($this->get_menu_calss($menu, $uri, $__uri)) {
                return TRUE;
            }
            if (strpos($__uri, $uri) === 0) {
                return TRUE;
            }
        }
    }

    /**
     * 用于后台的分页
     */
    protected function get_pagination($url, $total) {

        $this->load->library('pagination');

        $config['base_url'] = $url;
        $config['per_page'] = SITE_ADMIN_PAGESIZE;
        $config['next_link'] = L('下一页');
        $config['prev_link'] = L('上一页');
        $config['last_link'] = L('最后一页');
        $config['first_link'] = L('第一页');
        $config['total_rows'] = $total;
        $config['cur_tag_open'] = '<span>';
        $config['cur_tag_close'] = '</span>';
        $config['use_page_numbers'] = TRUE;
        $config['query_string_segment'] = 'page';

        $this->pagination->initialize($config);

        return $this->pagination->create_links();
    }

    /**
     * 会员中心分页
     */
    public function get_member_pagination($url, $total) {

        $this->load->library('pagination');

        $config = array();
        is_file(CONFPATH.'pagination_member.php') ? include CONFPATH.'pagination_member.php' : include CONFPATH.'pagination.php';

        $page = $config['pagination'];
        $page['base_url'] = $url;
        $page['per_page'] = $this->pagesize;
        $page['total_rows'] = $total;
        $page['use_page_numbers'] = TRUE;
        $page['query_string_segment'] = 'page';

        $this->pagination->initialize($page);

        return $this->pagination->create_links();
    }

    /**
     * 后台登录判断，返回当前登录用户信息
     *
     * @return void
     */
    protected function is_admin_login() {

        if (IS_ADMIN && ($this->router->class == 'login' || $this->router->method == 'login')) {
            return FALSE;
        }

        $uid = (int) $this->session->userdata('uid');
        $admin = (int) $this->session->userdata('admin');
        if ($this->uid == FALSE
            || $uid != $this->uid
            || $admin != $uid) {
            if (IS_AJAX) {
                exit(L('登录超时'));
            } elseif (IS_ADMIN) {
                redirect(ADMIN_URL.dr_url('login/index', array('backurl' => urlencode(dr_now_url()))), 'refresh');
            }
            return FALSE;
        }

        $data = $this->models('member')->get_admin_member($this->uid, 1);
        if (!is_array($data)) {
            if (IS_ADMIN) {
                if ($data == 0) {
                    $this->msg(0, L('会员不存在'));
                } elseif ($data == -3) {
                    $this->msg(0, L('您无权限登录管理平台'));
                } elseif ($data == -4) {
                    $this->msg(0, L('您无权限登录该站点'));
                }
            } else {
                return $data;
            }
        }

        return $data;
    }

    // 返回json格式
    public function callback_json($data) {

        // 自定义返回名称
        $return = safe_replace($this->input->get('return', true));
        if (isset($data['error']) && !isset($data['msg'])) {
            $data['msg'] = $data['error'];
            unset($data['error']);
        }

        if ($return) {
            $temp = $data;
            $data = array();
            foreach ($temp as $i => $t) {
                $data[$i.'_'.$return] = $t;
            }
        }

        return json_encode($data);
    }

    /**
     * 验证码验证
     *
     * @param	string	$id	验证码表单的name
     * @return  bool
     */
    protected function check_captcha($id) {

        if ((defined('IS_API_AUTH') && IS_API_AUTH) || defined('SELECT_API_AUTH')) {
            return TRUE; // 移动端不判断验证码
        }

        if (isset($_POST['geetest_challenge'])
            && isset($_POST['geetest_validate'])) {
            require FCPATH.'libraries/Geetestlib.php';
            $GtSdk = new GeetestLib();
            if ($this->session->userdata('gtserver') == 1) {
                $result = $GtSdk->validate(
                    $_POST['geetest_challenge'],
                    $_POST['geetest_validate'],
                    $_POST['geetest_seccode']
                );
                if ($result == TRUE) {
                    return TURE;
                } else if ($result == FALSE) {
                    return FALSE;
                } else {
                    return FALSE;
                }
            }else{
                if ($GtSdk->get_answer($_POST['geetest_validate'])) {
                    return TURE;
                }else{
                    return FALSE;
                }
            }
        } else {
            $data = $this->input->post($id);
            $data = $data ? $data : $this->input->get($id);
            if (!$data) {
                return FALSE;
            }
            $code = $this->session->userdata('captcha');
            if (strtolower($data) == $code) {
                $this->session->unset_userdata('captcha');
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * 表单提交数据验证和过滤
     *
     * @param	array	$_field	字段
     * @param	array	$_data	修改前的数据
     * @return  array
     */
    protected function validate_filter($_field, $_data = array()) {

        if (!$_field) {
            return;
        }

        $this->_data = $_data;
        $this->syn_content = $this->post = $this->data = array();
        $this->load->library('Dfield', array(APP_DIR));
        $this->load->library('Dfilter');
        $this->load->library('Dvalidate');

        foreach ($_field as $field) {
            // 前端字段筛选
            if (!IS_ADMIN && !$field['ismember']) {
                continue;
            }
            // 验证字段对象的有效性
            $obj = $this->dfield->get($field['fieldtype']);
            if (!$obj) {
                continue;
            }
            $name = $field['fieldname'];
            $validate = $field['setting']['validate'];
            // 禁止修改字段筛选
            if (IS_MEMBER && $validate['isedit'] && isset($_data[$name]) && $_data[$name]) {
                $this->post[$name] = $_data[$name];
                $obj->insert_value($field); // 获取入库值
                continue;
            }
            //
            if ($field['fieldtype'] == 'Ueditor' && strlen($this->post[$name]) > 1000000) {
                return array('error' => $name, 'msg' => L('%s内容太多', $field['name']));
            }
            // 编辑器默认关闭xss
            $validate['xss'] = !isset($validate['xss']) && $field['fieldtype'] == 'Ueditor' ? 1 : $validate['xss'];
            // 从表单获取值
            $this->post[$name] = $value = $this->input->post("data[$name]", $validate['xss'] ? FALSE : TRUE);
            // 验证必填字段
            if ($field['fieldtype'] != 'Group'
                && isset($validate['required'])
                && $validate['required']) {
                // 验证值为空
                if ($value == '') {
                    return array('error' => $name, 'msg' => L('%s不能为空！', $field['name']));
                }
                // 当类别为联动时判定0值
                if ($field['fieldtype'] == 'Linkage' && !$value) {
                    return array('error' => $name, 'msg' => L('%s不能为空！', $field['name']));
                }
            }
            // 正则验证
            if (!is_array($value) && strlen($value) && isset($validate['pattern'])
                && $validate['pattern']
                && !preg_match($validate['pattern'], $value)) {
                return array('error' => $name, 'msg' => $field['name'].'：'.($validate['errortips'] ? $validate['errortips'] : L('格式不正确')));
            }
            // 函数/方法校验
            if (isset($validate['check']) && $validate['check']) {
                if (strpos($validate['check'], '_') === 0) {
                    // 方法格式：_方法名称[:现存数据字段,参数2...]
                    list($method, $_param) = explode(':', str_replace('::', ':', $validate['check']));
                    $method = substr($method, 1);
                    if (method_exists($this->dvalidate, $method)) {
                        $param['value'] = $value;
                        if ('check_member' == $method && $value == 'guest') {
                            // 游客不验证
                        } else {
                            if ($_param && $_value = explode(',', $_param)) {
                                foreach ($_value as $t) {
                                    $param[$t] = isset($_POST['data'][$t]) ? $this->input->post("data[$t]") : $t;
                                }
                            }
                            if (call_user_func_array(array($this->dvalidate, $method), $param)) {
                                return array('error' => $name, 'msg' => $field['name'].'：'.L('格式校验不正确'));
                            }
                        }
                    } else {
                        log_message('error', "校验方法 $method 不存在！");
                    }
                } else {
                    // 函数格式：函数名称[:现存数据字段,参数2...]
                    list($func, $_param) = explode(':', str_replace('::', ':', $validate['check']));
                    if (function_exists($func)) {
                        $param['value'] = $value;
                        if ($_param && $_value = explode(',', $_param)) {
                            foreach ($_value as $t) {
                                $param[$t] = isset($_POST['data'][$t]) ? $this->input->post("data[$t]") : $t;
                            }
                        }
                        if (call_user_func_array($func, $param)) {
                            return array('error' => $name, 'msg' => $field['name'].'：'.L('格式校验不正确'));
                        }
                    } else {
                        log_message('error', "校验函数 $func 不存在！");
                    }
                }
            }
            // 过滤函数
            if (isset($validate['filter']) && $validate['filter']) {
                if (strpos($validate['filter'], '_') === 0) {
                    // 方法格式：_方法名称[:现存数据字段,参数2...]
                    list($method, $_param) = explode(':', str_replace('::', ':', $validate['filter']));
                    $method = substr($method, 1);
                    if (method_exists($this->dfilter, $method)) {
                        $param['value'] = $value;
                        if ($_param && $_value = explode(',', $_param)) {
                            foreach ($_value as $t) {
                                $param[$t] = isset($_POST['data'][$t]) ? $this->input->post("data[$t]") : $t;
                            }
                        }
                        // 开始过滤
                        $this->post[$name] = call_user_func_array(array($this->dfilter, $method), $param);
                    } else {
                        log_message('error', "过滤方法 $method 不存在！");
                    }
                } else {
                    // 函数格式：函数名称[:现存数据字段,参数2...]
                    list($func, $_param) = explode(':', str_replace('::', ':', $validate['filter']));
                    if (function_exists($func)) {
                        $param['value'] = $value;
                        if ($_param && $_value = explode(',', $_param)) {
                            foreach ($_value as $t) {
                                $param[$t] = isset($_POST['data'][$t]) ? $this->input->post("data[$t]") : $t;
                            }
                        }
                        // 开始过滤
                        $this->post[$name] = call_user_func_array($func, $param);
                    } else {
                        log_message('error', "过滤函数 $func 不存在！");
                    }
                }
            }
            // 判断表字段值的唯一性
            if ($field['ismain']
                && isset($field['setting']['option']['unique'])
                && $field['setting']['option']['unique']) {
                if ($this->db->where('id<>', (int)$_data['id'])->where($name, $this->post[$name])->count_all_results(SITE_ID.'_'.APP_DIR)) {
                    return array('error' => $name, 'msg' => L('%s已经存在', $field['name']));
                }
            }
            $obj->insert_value($field); // 获取入库值
            if ($field['fieldtype'] == 'Baidumap') {
                $this->data[$field['ismain']][$name.'_lng'] = (double)$this->data[$field['ismain']][$name.'_lng'];
                $this->data[$field['ismain']][$name.'_lat'] = (double)$this->data[$field['ismain']][$name.'_lat'];
            } elseif ($field['fieldtype'] == 'Syn') {
                $temp = string2array($this->data[$field['ismain']][$name]);
                $temp['name'] = $name;
                $this->syn_content = $temp;
                unset($temp);
            } else {
                if (strpos($field['setting']['option']['fieldtype'], 'INT') !== FALSE) {
                    $this->data[$field['ismain']][$name] = (int)$this->data[$field['ismain']][$name];
                } elseif ($field['setting']['option']['fieldtype'] == 'DECIMAL'
                    || $field['setting']['option']['fieldtype'] == 'FLOAT') {
                    $this->data[$field['ismain']][$name] = (double)$this->data[$field['ismain']][$name];
                }
            }
        }

        unset($this->post, $this->_data);
        return $this->data;
    }

    /**
     * 表单提交数据后执行的方法
     *
     * @param	intval	$id		主键id
     * @param	array	$_field	字段
     * @param	array	$_data	修改后的数据
     * @return  array
     */
    protected function validate_table($id, $_field, $_data = array()) {

        if (!$_field) {
            return;
        }

        $this->data = $_data;
        $this->load->library('Dfield', array(APP_DIR));
        foreach ($_field as $field) {
            // 验证字段对象的有效性
            $obj = $this->dfield->get($field['fieldtype']);
            if (!$obj) {
                continue;
            }
            // 执行脚本
            $obj->insert_last_value($id, $_data[$field['ismain']][$field['fieldname']], $field);
        }
    }

    /**
     * 附件处理
     *
     * @param	intval	$uid		会员uid
     * @param	array	$field		表字段
     * @param	string	$related	使用源的标识（表名-ID1-ID2...）
     * @param	array	$_data		原数据
     * @param	bool	$update		是否更新原附件
     * @return	NULL
     */
    public function attachment_handle($uid, $related, $field, $_data = NULL, $update = TRUE) {

        if (!$field) {
            return NULL;
        }

        // 当前POST的数据
        $attach = array();
        $attach_id = array();
        $this->load->library('Dfield', array(APP_DIR));

        // 查询使用的附件
        foreach ($field as $f) {
            // 加载字段处理对象
            $obj = $this->dfield->get($f['fieldtype']);
            if (!$obj) {
                continue;
            }
            $files = $obj->get_attach_id($this->data[$f['ismain']][$f['fieldname']]);
            $files && $attach_id = $attach_id ? array_merge($attach_id, $files) : $files;
        }

        // 筛选删除的附件
        foreach ($field as $f) {
            // 加载字段处理对象
            $obj = $this->dfield->get($f['fieldtype']);
            if (!$obj) {
                continue;
            }
            // 通过附件处理方法获得增加和删除的附件
            list($add_id, $del_id) = $obj->attach($this->data[$f['ismain']][$f['fieldname']], $_data[$f['fieldname']]);
            // 百度编辑器时暂时不同步删除附件，可能会出现误删除情况
            $f['fieldtype'] == 'Ueditor' && $del_id = array();
            // 删除附件
            if ($del_id) {
                foreach ($del_id as $id) {
                    if ($id && $update && !in_array($id, $attach_id)) {
                        $fj = get_attachment($id);
                        $log = dr_now_url().'  --->>>  删除附件('.$id.'#'.$related.'#'.$f['fieldname'].')：'.$fj['attachment'];
                        $this->system_log($log, 1); // 强制写入后台日志
                        $this->models('system/attachment')->delete_for_handle($uid, $related, $id);
                    }
                }
            }
            // 无新增附件时跳过
            if (!$add_id) {
                continue;
            }
            $attach = $attach ? array_merge($attach, $add_id) : $add_id;
        }

        $attach = $attach ? array_merge($attach, $attach_id) : $attach_id;
        if (count($attach) == 0) {
            return NULL;
        }

        // 更新至已用附件表
        $this->models('system/attachment')->replace_attach($uid, $related, array_unique($attach));

        unset($this->data);

        return NULL;
    }

    /**
     * 附件归属替换（用于草稿箱）
     *
     * @return	NULL
     */
    protected function attachment_replace_draft($id, $cid, $eid, $related, $status = 9) {


        $data = $this->db->where('related', $related.'_draft-'.$id)->select('id,tableid')->get('attachment')->result_array();
        if (!$data) {
            return NULL;
        }

        // 数据来自扩展表时
        $eid ? $related.= '-'.$cid.'-'.$eid : $related.= '-'.$cid;

        foreach ($data as $t) {
            $this->db->where('id', $t['id'])->update('attachment', array('related' => $related));
            $this->db->where('id', $t['id'])->update('attachment_use', array('related' => $related));
        }

        return NULL;
    }


    /**
     * 附件归属替换
     *
     * @param	intval	$uid		会员uid
     * @param	intval	$id			使用源id
     * @param	string	$related	使用源表名称
     * @return	NULL
     */
    protected function attachment_replace($uid, $id, $related) {

        if (!$uid || !$id || !$related) {
            return NULL;
        }

        $data = $this->db->where('related', $related.'_verify-'.$id)->select('id,tableid')->get('attachment')->result_array();
        if (!$data) {
            return NULL;
        }

        foreach ($data as $t) {
            $this->db->where('id', $t['id'])->update('attachment', array('related' => $related.'-'.$id));
            $this->db->where('id', $t['id'])->update('attachment_use', array('related' => $related.'-'.$id));
        }

        return NULL;
    }

    /**
     * 字段输出表单（新版本格式）
     *
     * @param	array	$field	字段数组
     * @param	array	$data	表单值
     * @param	bool	$cat	是否显示栏目附加字段
     * @param	string	$id		id字符串
     * @param	string	$html   html布局格式
     * @param	bool	$is_diy	是否仅输出自定义输出字段
     * @return	string
     */
    public function new_field_input($field, $data = NULL, $cat = FALSE, $id = 'id', $html = '', $is_diy = 0) {

        if (!$field) {
            return '';
        }

        $id = $id ? $id : 'id';
        $mygroup = $mymerge = $merge = $group = $diyfield = array();
        $myfield = $mark = '';


        // 分组字段筛选
        foreach ($field as $t) {
            if ($t['fieldtype'] == 'Group'
                && preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
                foreach ($value[1] as $v) {
                    $group[$v] = $t['fieldname'];
                }
            }
        }

        // 字段合并分组筛选
        foreach ($field as $t) {
            if ($t['fieldtype'] == 'Merge'
                && preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
                foreach ($value[1] as $v) {
                    $merge[$v] = $t['fieldname'];
                }
            }
        }

        // 字段类
        $this->load->library('dfield', array(APP_DIR));
        $pchtml = $this->get_cache('member', 'setting', 'field');
        $mbhtml = $this->get_cache('member', 'setting', 'mbfield');
        if (!IS_ADMIN) {
            if ($this->mobile && $mbhtml) {
                // 移动端格式
                A_Field::set_input_format($mbhtml);
                unset($mbhtml);
            } elseif ($pchtml) {
                // Pc端格式
                A_Field::set_input_format($pchtml);
                unset($pchtml);
            }
        }

        // 主字段
        foreach ($field as $t) {
            if (!IS_ADMIN && !$t['ismember']) {
                continue;
            } elseif (!IS_ADMIN && @in_array((int)$this->member['groupid'], $t['setting']['show_member'])) {
                continue;
            } elseif (IS_ADMIN && $this->member['adminid'] > 1
                && @in_array($this->member['adminid'], $t['setting']['show_admin'])) {
                continue;
            }
            $obj = $this->dfield->get($t['fieldtype']);
            $html && $obj->set_input_format($html);
            // 判断自定义输出字段
            if (!$is_diy && $t['setting']['is_right'] == 2) {
                $diyfield[$t['fieldname']] = $t;
                continue;
            }

            if (is_object($obj)) {
                // 百度地图特殊字段
                $obj->remove_div = 0;
                $value = $t['fieldtype'] == 'Baidumap' ? ($data[$t['fieldname'].'_lng'] && $data[$t['fieldname'].'_lat'] ? $data[$t['fieldname'].'_lng'].','.$data[$t['fieldname'].'_lat'] : $data[$t['fieldname']]) : $data[$t['fieldname']];

                if (isset($group[$t['fieldname']])) {
                    // 属于分组字段,重新获取字段表单
                    $obj->remove_div = 1;
                    $mygroup[$t['fieldname']] = $obj->input($t['name'], $t['fieldname'], $t['setting'], $value, isset($data[$id]) ? $data[$id] : 0);
                } elseif (isset($merge[$t['fieldname']])) {
                    // 属于合并字段
                    $input = $obj->input($t['name'], $t['fieldname'], $t['setting'], $value, isset($data[$id]) ? $data[$id] : 0);
                    $mymerge[$t['fieldname']] = $input;
                } elseif ($t['fieldtype'] == 'Merge') {
                    $myfield.= '{merge_'.$t['fieldname'].'}';
                } else {
                    $input = $obj->input($t['name'], $t['fieldname'], $t['setting'], $value, isset($data[$id]) ? $data[$id] : 0);
                    $myfield.= $input;
                }
            }
        }

        if ($merge) {
            $html = '

					    </div>
					</div>
				</div>
                <div class="portlet light bordered" id="dr_{name}">
                    <div class="portlet-title mytitle">
                        <div class="caption"><span class="caption-subject font-green">{text}</span></div>
                    </div>
                    <div class="portlet-body">
                        <div class="form-body">
{value}';
            if (!IS_ADMIN) {
                $pchtml = $this->get_cache('member', 'setting', 'mergefield');
                $mbhtml = $this->get_cache('member', 'setting', 'mbmergefield');
                if ($this->mobile && $mbhtml) {
                    $html = $mbhtml;
                } elseif ($pchtml) {
                    // Pc端格式
                    $html = $pchtml;
                }
            }

            $data = array();
            foreach ($merge as $fname => $mname) {
                $data[$mname][] = $fname;
            }
            foreach ($data as $mname => $value) {
                $code = '';
                if ($value) {
                    foreach ($value as $fname) {
                        $code.= $mymerge[$fname];
                    }
                    $myfield = $code ? str_replace(
                        '{merge_'.$mname.'}',
                        str_replace(
                            array('{text}', '{name}', '{value}'),
                            array(L($field[$mname]['name']), $mname, $code),
                            $html
                        ),
                        $myfield
                    ) : str_replace(
                        '{merge_'.$mname.'}',
                        '',
                        $myfield
                    );
                }

            }
        }

        if ($mygroup) {
            foreach ($mygroup as $name => $t) {
                $myfield = str_replace('{'.$name.'}', $t, $myfield);
            }
        }

        // 存在自定义输出地址
        $diyfield && $this->template->assign('diyfield', $this->new_field_input($diyfield, $data, $cat, $id, $html, 1));

        return $myfield;
    }

    /**
     * 字段输出表单（兼容老版本）
     *
     * @param	array	$field	字段数组
     * @param	array	$data	表单值
     * @param	bool	$cat	是否显示栏目附加字段
     * @param	string	$id		id字符串
     * @return	string
     */
    public function field_input($field, $data = NULL, $cat = FALSE, $id = 'id') {

        return $this->new_field_input($field, $data, $cat, $id);
    }

    /**
     * 字段输出格式化
     *
     * @param	array	$fields 	可用字段集
     * @param	array	$data		数据
     * @param	intval	$curpage	分页id
     * @param	string	$dirname	模块目录
     * @return	string
     */
    public function field_format_value($fields, $data, $curpage = 1, $dirname = NULL) {

        if (!$fields
            || !$data
            || !is_array($data)) {
            return $data;
        }

        foreach ($data as $n => $value) {
            if (isset($fields[$n]) && $fields[$n]) {
                $format = dr_get_value($fields[$n]['fieldtype'], $value, $fields[$n]['setting']['option'], $dirname);
                if ($format !== $value) {
                    $data['_'.$n] = $value;
                    $data[$n] = $format;
                } elseif (SITE_MOBILE !== TRUE
                    && $n == 'content' && $fields[$n]['fieldtype'] == 'Ueditor'
                    && strpos($value, '<div name="dr_page_break" class="pagebreak">') !== FALSE
                    && preg_match_all('/<div name="dr_page_break" class="pagebreak">(.*)<\/div>/Us', $value, $match)
                    && preg_match('/(.*)<div name="dr_page_break"/Us', $value, $frist)) {
                    // 编辑器分页 老版本
                    $page = 1;
                    $content = $title = array();
                    $data['_'.$n] = $value;
                    $content[$page]['title'] = L('第%s页', $page);
                    $content[$page]['body'] = $frist[1];
                    foreach ($match[0] as $i => $t) {
                        $page ++;
                        $value = str_replace($content[$page - 1]['body'].$t, '', $value);
                        $body = preg_match('/(.*)<div name="dr_page_break"/Us', $value, $match_body) ? $match_body[1] : $value;
                        $title[$page] = trim($match[1][$i]);
                        $content[$page]['title'] = trim($match[1][$i]) ? trim($match[1][$i]) : L('第%s页', $page);
                        $content[$page]['body'] = $body;
                    }
                    $page = max(1, min($page, $curpage));
                    $data[$n] = $content[$page]['body'];
                    $data[$n.'_page'] = $content;
                    $data[$n.'_title'] = $title[$page];
                } elseif (SITE_MOBILE !== TRUE
                    && $fields[$n]['fieldtype'] == 'Ueditor'
                    && strpos($value, '<p class="pagebreak">') !== FALSE
                    && preg_match_all('/<p class="pagebreak">(.*)<\/p>/Us', $value, $match)
                    && preg_match('/(.*)<p class="pagebreak">/Us', $value, $frist)) {
                    // 编辑器分页 新版
                    $page = 1;
                    $content = $title = array();
                    $data['_'.$n] = $value;
                    $content[$page]['title'] = L('第%s页', $page);
                    $content[$page]['body'] = $frist[1];
                    foreach ($match[0] as $i => $t) {
                        $page ++;
                        $value = str_replace($content[$page - 1]['body'].$t, '', $value);
                        $body = preg_match('/(.*)<p class="pagebreak"/Us', $value, $match_body) ? $match_body[1] : $value;
                        $title[$page] = trim($match[1][$i]);
                        $content[$page]['title'] = trim($match[1][$i]) ? trim($match[1][$i]) : L('第%s页', $page);
                        $content[$page]['body'] = $body;
                    }
                    $page = max(1, min($page, $curpage));
                    $data[$n] = $content[$page]['body'];
                    $data[$n.'_page'] = $content;
                    $data[$n.'_title'] = $title[$page];
                }
            } elseif (strpos($n, '_lng') !== FALSE) {
                // 百度地图
                $name = str_replace('_lng', '', $n);
                $data[$name] = isset($data[$name.'_lat']) && ($data[$name.'_lng'] > 0 || $data[$name.'_lat'] > 0) ? $data[$name.'_lng'].','.$data[$name.'_lat'] : '';
            }
        }

        return $data;
    }

    /**
     * 当前会员对模块的可用栏目发布权限
     *
     * @param	array	$module		模块缓存数据
     * @param	string	$markrule	权限标识
     * @return  array	可用栏目id
     */
    protected function _module_post_catid($module, $markrule = NULL) {

        // 当模块没有添加栏目数据时标识为禁用状态
        if (!$module['category']) {
            return NULL;
        }

        $catid = array();
        $markrule = $markrule ? $markrule : $this->markrule;

        foreach ($module['category'] as $cat) {
            // 跳过有下级栏目的判断
            if ($module['setting']['pcatpost'] ? 0 : $cat['child']) {
                continue;
            }
            // 当栏目中存在一项是非禁用就标识为非禁用状态
            isset($cat['permission'][$markrule]['add']) && $cat['permission'][$markrule]['add'] == 1 && $catid[] = (int) $cat['id'];
        }

        return $catid;
    }

    /**
     * 判断当前管理角色权限 (管理)
     *
     * @param	string	$uri	模块缓存数据
     * @return  bool	TRUE可以管理 | FALSE不能管理
     */
    public function _is_module_admin($uri) {

        $MOD = $this->get_module(SITE_ID);
        list($dir, $directory, $class, $method) = explode('/', $uri);

        // 非模块时跳出不判断
        if (!isset($MOD[$dir])) {
            return TRUE;
        }

        // 非内容控制器跳过
        if ($class != 'home' || $method != 'index') {
            return TRUE;
        }

        // 当模块没有添加栏目数据时标识为不可以管理
        if (!$MOD[$dir]['category']) {
            return FALSE;
        }

        foreach ($MOD[$dir]['category'] as $cat) {
            // 跳过有下级栏目的判断
            if ($MOD[$dir]['setting']['pcatpost'] ? 0 : $cat['child']) {
                continue;
            }
            // 当栏目中存在一项是管理就标识为管理状态
            if (isset($cat['setting']['admin'][$this->admin['adminid']]['show'])
                && $cat['setting']['admin'][$this->admin['adminid']]['show'] == 1) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * 检查目录可写
     *
     * @param	string	$pathfile
     * @return	boolean
     */
    protected function _check_write_able($pathfile) {
        if (!$pathfile) {
            return FALSE;
        }

        $isDir = in_array(substr($pathfile, -1), array('/', '\\')) ? TRUE : FALSE;
        if ($isDir) {
            if (is_dir($pathfile)) {
                mt_srand((double) microtime() * 1000000);
                $pathfile = $pathfile.'dr_'.uniqid(mt_rand()).'.tmp';
            } elseif (@mkdir($pathfile)) {
                return self::_check_write_able($pathfile);
            } else {
                return FALSE;
            }
        }
        @chmod($pathfile, 0777);
        $fp = @fopen($pathfile, 'ab');
        if ($fp === FALSE) {
            return FALSE;
        }

        fclose($fp);
        $isDir && @unlink($pathfile);

        return TRUE;
    }

    // 执行sql
    public function sql_query($sql, $db = NULL) {

        if (!$sql) {
            return NULL;
        }

        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $sql)));

        foreach ($sql_data as $query) {
            if (!$query) {
                continue;
            }
            $ret = '';
            $queries = explode('SQL_FINECMS_EOL', trim($query));
            foreach ($queries as $query) {
                $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
            }
            if (!$ret) {
                continue;
            }
            $this->db->query($ret);
        }
    }

    /**
     * 站点间的同步退出
     */
    protected function api_synlogin() {

        $code = dr_authcode(str_replace(' ', '+', $this->input->get('code')));
        if (!$code) {
            exit('code is null');
        }

        list($uid, $salt) = explode('-', $code);

        if (!$uid || !$salt) {
            exit('data is null');
        }

        $member = $this->db->select('password,salt')->where('uid', $uid)->get('member')->row_array();
        if (!$member) {
            exit('check error');
        } elseif ($member['salt'] != $salt) {
            exit('error');
        }

        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        $expire = max((int)$this->input->get('expire'), 86400);

        ob_start();
        set_cookie('member_uid', $uid, $expire);
        set_cookie('member_cookie', substr(md5(SYS_KEY.$member['password']), 5, 20), $expire);
        ob_get_clean();
        exit('ok uid='.$uid);
    }

    /**
     * 站点间的同步登录
     */
    protected function api_synlogout() {
        if ($this->session->userdata('member_auth_uid')) {
            $this->session->unset_userdata('member_auth_uid');
        } else {
            set_cookie('member_uid', 0, -1);
            set_cookie('member_cookie', '', -1);
            if ($this->uid) {
                $this->db->delete('member_online', 'uid='.$this->uid);
            }
        }
    }

    /**
     * 自定义信息JS调用
     */
    protected function api_template() {
        ob_start();
        $this->template->cron = 0;
        $_GET['page'] = max(1, (int)$this->input->get('page'));
        $get = @json_decode(urldecode($this->input->get('get')), TRUE);
        $params = @json_decode(urldecode($this->input->get('params')), TRUE);
        $this->template->assign(array(
            'get' => $get,
            'params' => $params,
            'dirname' => $this->input->get('dirname')
        ));
        $this->template->assign($get);
        $this->template->assign($params);
        $name = str_replace(array('\\', '/', '..', '<', '>'), '', safe_replace($this->input->get('name', TRUE)));
        $this->template->display(strpos($name, '.html') ? $name : $name.'.html');
        $html = ob_get_contents();
        ob_clean();

        // 格式输出
        if (isset($_GET['return']) && $_GET['return'] == 'js') {
            $html = addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html));
            echo 'document.write("'.$html.'");';
        } else {
            exit($html);
        }
        exit;
    }

    /**
     * 引用404页面
     */
    public function goto_404_page($msg) {
        header("status: 404 Not Found");
        $this->template->assign(array(
            'msg' => $msg,
            'meta_title' => $msg
        ));
        $this->template->display('404.html');exit;
    }

    /**
     * 后台日志
     */
    protected function system_log($action, $insert = 0) {

        if (!$insert) {
            if (!SYS_LOG || !IS_ADMIN) {
                return NULL;
            }
        }

        $data = array(
            'ip' => $this->input->ip_address(),
            'uid' => $this->member['uid'],
            'username' => $this->member['username'],
            'action' => addslashes($action),
            'time' => SYS_TIME,
        );

        $path = DATAPATH.'log/option/'.date('Ym', SYS_TIME).'/';
        $file = $path.date('d', SYS_TIME).'.log';
        !is_dir($path) && file_mkdirs($path);

        file_put_contents($file, PHP_EOL.array2string($data), FILE_APPEND);
    }

    // 任意表字段信息
    public function field_table($table, $sid) {

        $name = 'table_'.$table.$sid;
        $data = $this->dcache->get($name);
        if (!$data) {
            $field = $this->db
                ->where('disabled', 0)
                ->where('relatedname', 'table-'.$table)
                ->where('relatedid', $sid)
                ->order_by('displayorder ASC, id ASC')
                ->get('field')
                ->result_array();
            if ($field) {
                foreach ($field as $f) {
                    $data[$f['fieldname']] = $f;
                }
            }
            $this->dcache->set($name, $data);
        }

        return $data;
    }

    // jsonp 格式返回
    public function return_jsonp($data) {
        echo safe_replace($this->input->get('callback', TRUE)).'('.$data.')';exit;
    }

    // 获取任意表的自定义字段
    public function get_mytable_field($table, $siteid = 0) {

        $name = 'mytable-'.$table.'-'.$siteid;
        $value = $this->get_cache($name);
        if (!$value) {
            $field = $this->db
                ->where('disabled', 0)
                ->where('relatedid', $siteid)
                ->where('relatedname', 'table-'.$table)
                ->order_by('displayorder ASC,id ASC')
                ->get('field')
                ->result_array();
            if ($field) {
                foreach ($field as $t) {
                    $t['setting'] = string2array($t['setting']);
                    $value[$t['fieldname']] = $t;
                }
            }
            $this->dcache->set($name, $value);
        }
        return $value;
    }

    // 去除url中的域名
    protected function _remove_domain($url) {

        if (!$this->_temp['domain']) {
            $domain = require CONFPATH.'domain.php';
            foreach ($domain as $u => $i) {
                $this->_temp['domain'][] = 'http://'.$u.'/';
                $this->_temp['domain'][] = 'https://'.$u.'/';
            }
        }

        return str_replace($this->_temp['domain'], '', $url);
    }

    // 网站首页方法
    protected function _indexc() {

        $file = DATAPATH.'index/'.(IS_MOBILE ? 'mobile-' : '').DOMAIN_NAME.'-home-'.max(intval($_GET['page']), 1).'.html';

        // 系统开启静态首页、静态文件不存在时，才生成文件
        if (defined('SYS_AUTO_CACHE') && SYS_AUTO_CACHE && SYS_CACHE_INDEX && !is_file($file) && !SITE_CLOSE) {
            ob_start();
            $this->template->assign(array(
                'indexc' => 1,
                'meta_title' => SITE_TITLE,
                'meta_keywords' => SITE_KEYWORDS,
                'meta_description' => SITE_DESCRIPTION,
            ));
            $this->template->display('index.html');
            $html = ob_get_clean();
            @file_put_contents($file, $html, LOCK_EX);
            echo $html;exit;
        } else {
            $this->template->assign(array(
                'indexc' => 1,
                'meta_title' => SITE_TITLE,
                'meta_keywords' => SITE_KEYWORDS,
                'meta_description' => SITE_DESCRIPTION,
            ));
            $this->template->display('index.html');
        }
    }

    // 初始化模块
    protected function _module_init() {

        // 定义模块目录
        !$this->dir && $this->dir = APP_DIR;
        
        // 检查模块
        $this->module = $this->get_cache('module-'.SITE_ID.'-'.$this->dir);
        if (!$this->module) {
            $this->admin_msg(L('模块【'.$this->dir.'】不存在'));
        }

        // 判断是否拒绝使用
        !IS_ADMIN && isset($this->module['setting']['member'][$this->markrule]) && $this->msg(L('无权限使用此模块'));

        // 模块常量
        define('MOD_DIR', $this->dir);
        define('IS_SHARE', $this->module['share'] ? $this->dir : '');
        define('MODULE_ID', $this->module['id']);
        define('MODULE_URL', IS_SHARE ? '/' : $this->module['url']); // 共享模块没有模块url
        define('MODULE_NAME', $this->module['name']);
        define('MODULE_TITLE', $this->module['site'][SITE_ID]['module_title']);
        define('MODULE_PCATE_POST', intval($this->module['setting']['pcatpost']));
        define('MODULE_THEME_PATH', IS_SHARE ? HOME_THEME_PATH : (strpos($this->module['theme'], 'http://') === 0 ? trim($this->module['theme'], '/').'/' : THEME_PATH.($this->module['theme'] ? $this->module['theme'] : 'default').'/'));

        // 设置模块模板
        $this->template->module($this->dir);

        // 定位项目目录
        !APP_DIR && $this->load->add_package_path(WEBPATH.'module/'.$this->dir.'/');

        // 模块语言文件
        $this->lang->load('my');

        // 初始化会员中心部分
        if (IS_MEMBER) {
            $this->field = $this->module['field'];
            $this->load->library('Dfield', array($this->dir));
            // 当前会员组可用的推荐位
            $data = $this->module['setting']['flag'];
            if ($data) {
                foreach ($data as $i => $t) {
                    isset($t[$this->member['mark']])
                    && $t[$this->member['mark']]
                    && $t['name'] && $this->flag[$i] = $t;
                }
            }
        }
    }

    protected function _init_member(){

        $MEMBER = $this->get_cache('member');

        if(IS_API){
            $header = $this->input->request_headers();
            if($header['X-Token'] && $header['X-Sign']){
                $this->uid = $this->models('member/login')->authcode_check($header['X-Token'], $header['X-Sign']);
            }
        } else {
            // 获取当前的登录记录
            $this->uid or $this->uid = (int)$this->models('member')->member_uid();
            $this->member = $this->models('member')->get_member($this->uid);

            // 验证账号cookie的有效性
            if (!$this->models('member/login')->check()) {
                $this->uid = 0;
                $this->member = array();
            }
        }

        $this->uid && $this->member = $this->models('member')->get_member($this->uid);

        // 会员不存在时，uid设置为0
        !$this->member && $this->uid = 0;

        // 当前会员组的权限信息
        $this->markrule = $this->member ? $this->member['mark'] : 0;
        $this->member_rule = isset($MEMBER['setting']['permission'][$this->markrule]) ? $MEMBER['setting']['permission'][$this->markrule] : NULL; // 当前会员权限规则

        // 当前会员的模块栏目权限规则
        $s = 0;
        $MOD = $this->get_module(SITE_ID);
        if ($MOD && APP_DIR && $MOD[APP_DIR]['category']) {
            foreach ($MOD[APP_DIR]['category'] as $c) {
                $c['share'] && $s == 0 && $s = 1;
                $this->module_rule[$c['id']] = $c['permission'][$this->markrule];
            }
        }

        // 系统存在共享模块时
        define('SYS_SHARE', $s);

        // 会员域名
        $url = SITE_URL.'member.php';
        if (!$this->mobile
            && isset($MEMBER['setting']['domain'][SITE_ID]) && $MEMBER['setting']['domain'][SITE_ID]) {
            // 当非移动端时且存在当前站点的会员域名就采用指定的域名
            $url = dr_http_prefix($MEMBER['setting']['domain'][SITE_ID].'/');
        } elseif (isset($MEMBER['rule']['member']) && $MEMBER['rule']['member']) {
            // 自定义URL
            define('MEMBER_URL_RULE', 1);
            $url = SITE_URL.$MEMBER['rule']['member'];
        }

        if (IS_MEMBER) {
            if (defined('DR_UEDITOR') && is_dir(WEBPATH.'api/'.DR_UEDITOR)) {
                require WEBPATH.'api/'.DR_UEDITOR.'/php/controller.php';
                exit;
            }
            $uri = trim(str_replace('member/', '/', str_replace('/member/', '/', $this->duri->uri(NULL, TRUE))), '/');
            $menu = $this->get_cache('member-menu');
            $this->pagesize = $MEMBER['setting']['pagesize'] ? (int)$MEMBER['setting']['pagesize'] : 10;
            if ($menu['data']) {
                foreach ($menu['data'] as $i => $t) {
                    // 不存在时删除
                    if (!isset($menu['data'][$i]['left'])
                        || !$menu['data'][$i]['left']) {
                        unset($menu['data'][$i]);
                    }
                    // 筛选模块权限
                    if ($t['mark'] == 'm_mod') {
                        // 循环分组菜单（模块）
                        foreach ($menu['data'][$i]['left'] as $one) {
                            $pid = $one['id'];
                            list($a, $dir) = explode('-', $one['mark']);
                            if ($a != 'left') {
                                continue;
                            }
                            // 判断模块可用权限（订单模块除外）
                            if ($dir != 'order' && (!isset($MOD[$dir]) || isset($MOD[$dir]['setting']['member'][$this->markrule]))) {
                                unset($menu['data'][$i]['left'][$pid]);
                                continue;
                            }
                            if ($MOD[$dir]['is_system'] && $one['link'] && !$this->_module_post_catid($MOD[$dir])) {
                                // 判断发布权限
                                foreach ($one['link'] as $o) {
                                    // 过滤无发布权相关的菜单
                                    if (in_array($o['uri'], array(
                                        $dir.'/home/index',
                                        $dir.'/home/flag'
                                    ))) {
                                        unset($menu['data'][$i]['left'][$pid]['link'][$o['id']]);
                                    }
                                }
                            } elseif ($MOD[$dir]['is_system'] && $one['link']) {
                                // 表单权限
                                foreach ($one['link'] as $o) {
                                    if (strpos($o['uri'], $dir.'/form_') !== false) {
                                        list($a, $b, $c) = @explode('/', $o['uri']);
                                        list($a, $name) = @explode('_', $b);
                                        if ($name
                                            && $this->get_cache('module-'.SITE_ID.'-'.$dir, 'form', $name, 'permission', $this->markrule, 'disabled')) {
                                            unset($menu['data'][$i]['left'][$pid]['link'][$o['id']]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // 提取第一个菜单作为顶级菜单地址
                    $left = @reset($menu['data'][$i]['left']);
                    if ($left['link']) {
                        $link = @reset($left['link']);
                        if ($link) {
                            $menu['data'][$i]['uri'] = $link['uri'];
                            $menu['data'][$i]['url'] = $link['url'];
                        } else {
                            unset($menu['data'][$i]);
                        }
                    } else {
                        unset($menu['data'][$i]);
                    }
                }
            }

            // 增加内容扩展菜单的右侧关联显示
            $cur = strpos($uri, APP_DIR.'/extend/') !== FALSE || preg_match('/^'.APP_DIR.'\/form_[0-9]+_[0-9]+\/listc/Ui', $uri)
                ? $menu['uri'][APP_DIR.'/home/index'] : ( isset($menu['uri'][$uri]) ? $menu['uri'][$uri]
                    : $menu['uri'][str_replace(strrchr($uri, '/'), '/index', $uri)]);

            // 当模块下不存在左侧栏目时默认选中列表页
            $cur = !$cur && APP_DIR && (is_dir(WEBPATH.'module/'.APP_DIR) || is_dir(FCPATH.'app/'.APP_DIR)) && isset($menu['uri'][APP_DIR.'/home/index'])
                ? $menu['uri'][APP_DIR.'/home/index'] : $cur;

            $this->template->assign(array(
                'uid' => $this->uid,
                'menu' => $menu['data'],
                'menu_id' => $cur['id'], // 当前URI对应的菜单id
                'menu_pid' => $cur['pid'], // 当前URI对应的父级菜单id
                'menu_tid' => $cur['tid'], // 当前URI对应的顶级菜单id
                'meta_name' => $cur['name'], // 当前菜单名称作为标题名称
                'member_rule' => $this->member_rule,
            ));

            // 登录判断
            if ($this->_is_login_member()) {
                // 游客发布权限不验证
                $verify = APP_DIR != 'member' && in_array($uri, array('home-add', 'home-field')) && !$this->member ? FALSE : TRUE;
                if($verify){
                    // 没有登录时
                    if(!$this->member){
                        $this->member_msg(L('请先登录'), '', -4001);
                    }
                    // 待审核会员组
                    if($this->member['groupid'] == 1 && $uri != 'home-index'){
                        $this->member_msg(L('对不起，您还没有通过审核，无法进行此操作'), MEMBER_URL);
                    }
                }
                // 已经登录时
                if ($this->uid) {
                    $this->models('member')->init();
                }
            }
        } elseif (IS_ADMIN) {
            // 后台部分
            $uri = $this->duri->uri(); 
            $this->admin = $this->is_admin_login();

            // 权限判断
            !$this->is_auth($uri)
            && (IS_AJAX ? exit(L('您无权限操作(%s)%s', $uri)) : $this->admin_msg(L('您无权限操作(%s)', $uri)));

            // 更新缓存
            if (IS_POST) {
                
                $this->load->helper('file');
                delete_files(DATAPATH.'sql/');
                delete_files(DATAPATH.'file/');
                delete_files(DATAPATH.'page/');
                delete_files(DATAPATH.'index/');
                delete_files(DATAPATH.'views/');
                
                function_exists('opcache_reset') && opcache_reset();
            }

            $this->template->assign('admin', $this->admin);

            // 后台性能分析
            SYS_DEBUG && !IS_AJAX && IS_PC && $this->router->class != 'api' && $this->output->enable_profiler(TRUE);
        }
        unset($MOD, $MEMBER, $url, $uri, $cur, $menu);
    }
    // 获取返回时的URL
    protected function _get_back_url($uri, $param = array()) {

        $name = md5($uri.$this->uid.SITE_ID.$this->input->ip_address().$this->input->user_agent());
        $value = $this->cache->file->get($name);
        return $value ? $value : dr_url($uri, $param);
    }

    // 设置返回时的URL, uri页面标识,param参数,nuri当前页优先
    protected function _set_back_url($uri, $param = array(), $nuri = '') {

        !is_array($param) && $param = array();
        $name = md5($uri.$this->uid.SITE_ID.$this->input->ip_address().$this->input->user_agent());
        $param['page'] = $_GET['page'];
        $this->cache->file->save($name, dr_url($nuri ? $nuri : $uri, $param), 3600);
    }

    // 替换语言
    public function replace_lang($string) {

        if ($this->replace_lang === 1) {
            $lang = array();
            $this->replace_lang = array();
            is_file(FCPATH.'language/'.SITE_LANGUAGE.'/replace_lang.php') && require FCPATH.'language/'.SITE_LANGUAGE.'/replace_lang.php';
            is_file(APPPATH.'language/'.SITE_LANGUAGE.'/replace_lang.php') && require_once APPPATH.'language/'.SITE_LANGUAGE.'/replace_lang.php';
            if ($lang) {
                foreach ($lang as $name => $value) {
                    $this->replace_lang[1][] = $name;
                    $this->replace_lang[2][] = $value;
                }
            }
        }

        return $this->replace_lang ? str_replace($this->replace_lang[1], $this->replace_lang[2], $string) : $string;
    }


    public function html_thumb($p) {

        // 参数解析
        list($id, $width, $height, $water, $size) = explode('-', $p);

        $attach = get_attachment($id); // 图片信息
        if (!$attach) {
            return THEME_PATH.'admin/images/nopic.gif';
        }

        // 缓存文件
        $thumb = trim(SYS_THUMB_DIR, '/').'/'.md5($id).'/'.file_safename("$width-$height-$water-$size").'.jpg';
        
        file_mkdirs(dirname(MTBASE . $thumb));

        if ($attach && in_array($attach['fileext'], array('jpg', 'gif', 'png', 'jpeg'))) {
            // 远程图片下载到本地缓存目录
            if (isset($attach['remote']) && $attach['remote']) {
                return dr_get_file($id);
            } else {
                if ($size) {
                    $file_size =  str_replace(
                        basename($attach['attachment']),
                        basename($attach['attachment'], '.'.$attach['fileext']).'_'.$size.'.'.$attach['fileext'],
                        $attach['attachment']
                    );
                    $file = is_file(SYS_UPLOAD_PATH.'/'.$file_size) ? SYS_UPLOAD_PATH.'/'.$file_size : SYS_UPLOAD_PATH.'/'.$attach['attachment'];
                } else {
                    $file = SYS_UPLOAD_PATH.'/'.$attach['attachment'];
                }
            }
            if (!is_file($file)) {
                return THEME_PATH.'admin/images/nopic.gif';
            }
        } else {
            return THEME_PATH.'admin/images/nopic.gif';
        }

        // 处理宽高
        list($_width, $_height) = @getimagesize($file);
        $width = $width ? $width : $_width;
        $height = $height ? $height : $_height;

        // 站点配置信息
        $site = $this->get_cache('siteinfo', $attach['siteid']);

        // 生成新图参数
        $config['width'] = $width;
        $config['height'] = $height;
        $config['create_thumb'] = TRUE;
        $config['source_image'] = $file;
        $config['new_image'] = MTBASE . $thumb;
        $config['thumb_marker'] = '';
        $config['image_library'] = 'gd2';
        $config['dynamic_output'] = false; // 覆盖图片
        $config['maintain_ratio'] = (bool)$site['SITE_IMAGE_RATIO']; // 使图像保持原始的纵横比例

        // 水印判断
        if (isset($attach['remote']) && $attach['remote'] && !$site['SITE_IMAGE_REMOTE']
            ? FALSE : ((bool)$site['SITE_IMAGE_WATERMARK'] && $water ? TRUE : FALSE)) {
            // 水印参数
            $config['wm_type'] = $site['SITE_IMAGE_TYPE'] ? 'overlay' : 'text';
            $config['wm_vrt_offset'] = $site['SITE_IMAGE_VRTOFFSET'];
            $config['wm_hor_offset'] = $site['SITE_IMAGE_HOROFFSET'];
            $config['wm_vrt_alignment'] = $site['SITE_IMAGE_VRTALIGN'];
            $config['wm_hor_alignment'] = $site['SITE_IMAGE_HORALIGN'];
            // 文字模式
            $config['wm_text'] = $site['SITE_IMAGE_TEXT'];
            $config['wm_font_size'] = $site['SITE_IMAGE_SIZE'];
            $config['wm_font_path'] = STATICS.'watermark/'.($site['SITE_IMAGE_FONT'] ? $site['SITE_IMAGE_FONT'] : 'default.ttf');
            $config['wm_font_color'] = $site['SITE_IMAGE_COLOR'] ? str_replace('#', '', $site['SITE_IMAGE_COLOR']) : '#000000';
            // 图片模式
            $config['wm_opacity'] = $site['SITE_IMAGE_OPACITY'] ? $site['SITE_IMAGE_OPACITY'] : 80;
            $config['wm_overlay_path'] = STATICS.'watermark/'.($site['SITE_IMAGE_OVERLAY'] ? $site['SITE_IMAGE_OVERLAY'] : 'default.png');
            // 生成图片的临时文件
            $this->load->library('image_lib');
            $this->image_lib->initialize($config);
            $this->image_lib->resize();
            // 打开临时文件再水印
            $this->image_lib->full_src_path = $config['new_image'];
            $this->image_lib->watermark();
        } else {
            // 默认模式
            $this->load->library('image_lib');
            $this->image_lib->initialize($config);
            $this->image_lib->resize();
        }
        
        return SITE_PC . $thumb;
    }
    /**
     * 图片处理2
     */
    public function html_thumb2($p) {

        list($id, $width, $height, $autocut) = explode('-', $p);

        $this->load->library('dthumb');

        // 是附件id时
        if (is_numeric($id)) {
            $info = get_attachment($id);
            if (!$info) {
                return THEME_PATH.'admin/images/nopic.gif';
            }
            // 输出图片的地址
            $display = trim(SYS_THUMB_DIR, '/').'/'.md5("index.php?c=image&m=thumb&p=$id-$width-$height-$autocut").'.jpg';
            // 远程图片下载到本地缓存目录
            if (isset($info['remote']) && $info['remote']) {
                $file = WEBPATH.'cache/attach/'.time().'_'.basename($info['attachment']);
                file_put_contents($file, dr_catcher_data($info['attachment']));
            } else {
                $file = SYS_UPLOAD_PATH.'/'.$info['attachment'];
            }
            unset($info);
        } else {

            return THEME_PATH.'admin/images/nopic.gif';
        }

        // 图片不存在时调用默认图片
        if (!is_file($file)) {
            return THEME_PATH.'admin/images/nopic.gif';
        }

        // 生成缩略图
        $this->dthumb->thumb($file, $display, $width, $height, '', $autocut);

        // 输出缩略图
        $this->dthumb->html(WEBPATH.$display);

        return SITE_URL.$display;
    }

    function _get_keyword($kw){
        if (!$kw) {
            return '';
        }
        $rt = '';
        $tags = $this->dcache->get('tags-'.SITE_ID);
        if ($tags) {
            foreach ($tags as $t) {
                if (strpos($kw, $t['name']) !== false) {
                    $rt.= ','.$t['tags'];
                }
            }
        }
        if ($rt) {
            return trim($rt, ',');
        }
        $return = array();
        $tags = $this->dcache->get('tag-'.SITE_ID);
        if ($tags) {
            foreach ($tags as $t) {
                strpos($kw, $t) !== false && $return[] = $t;
            }
        }
        $rt = @implode(',', $return);
        if (!$rt) {
            $info = @file_get_contents('http://zhannei.baidu.com/api/customsearch/keywords?title='.rawurlencode($kw));
            $info=rawurldecode($info);
            if ($info) {
                $kws = array();
                $comtxts = json_decode($info, true);
                $keyword_list = $comtxts['result']['res']['keyword_list'];
                foreach ($keyword_list as $v) {
                    $kw = trim($v);
                    (strlen($kw) > 5) && $kws[] = $kw;
                }
                $rt = @implode(',', $kws);
            }
        }
        return $rt ? $rt : '';
    }

    public function render($array, $template = ''){
        if(IS_AJAX || IS_API){
            foreach ($array as $key=>$value) {
                // 黑名单
                if(in_array($key, ['related', 'parent', 'top', 'cat', 'field', 'myfield', 'search_sql', 'sql'])){
                    unset($array[$key]);
                }
                // // 白名单
                // if(in_array($key, explode(',', $this->input->get('fileds')))){
                //     $new[$key] = $value;
                // }
            }
            $this->json($array);
        } else {
            $this->template->assign($array);
            $this->template->display($template);
        }
    }

    public function admin_msg($code = 0, $msg = '', $url = '', $time = 1) {
        $this->msg($code, $msg, $url, $time, 'admin');
    }

    public function member_msg($code = 0, $msg = '', $url = '', $time = 1) {
        $this->msg($code, $msg, $url, $time, 'member');
    }

    public function msg($code = 0, $msg = '', $url = '', $time = 1) {
        if (IS_AJAX || IS_API) {
            $this->json($code, $msg);
        } else {
            // 兼容老版本
            if(is_string($code)){
                $url = $msg;
                $msg = $code;
                $code = 1;
            }
            // 兼容老版本
            $this->template->assign([
                'code' => $code,
                'msg' => $msg,
                'url' => $url,
                'time' => $time
            ]);
            $this->template->display('msg.html');
        }
        exit;
    }

    public function json($code = 0, $msg = '', $array = []){
        x_json($code, $msg, $array);
    }

    public function models($model){
        if($model){
            $model = ucfirst($model);
            $path = '';
            if (($last_slash = strrpos($model, '/')) !== FALSE)
            {
                $path = substr($model, 0, ++$last_slash);
                $model = ucfirst(substr($model, $last_slash));
            }
            $name =  ($path ? str_replace('/', '_', $path): '') . $model.'_model';
            $_this =& get_instance();
            if(!(is_array($_this->_ci_models) && in_array($model, $_this->_ci_models))){
                $this->load->model($path.$model);
            }
            return $_this->$name;
        }
    }
}
