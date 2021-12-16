<?php

require_once FCPATH.'core/D_Common.php';

class Extends_Application extends D_Common {

    public $app; // 当前应用的配置情况

    /**
     * 应用继承类
     */
    public function __construct() {
        parent::__construct();
        $this->app = $this->get_cache('app-'.APP_DIR);
        // 应用不存在或者被禁用
        !IS_ADMIN && !$this->app && $this->msg(L('应用尚未安装或者被禁用了'));
    }

    /**
     * url方法
     *
     * @param	string	$uri	URL规则(相对于当前应用)，如home/index
     * @param	array	$query	相关参数
     * @return	string	项目入口文件.php?参数
     */
    public function url($uri, $query = '') {
        $url = dr_url(APP_DIR.'/'.$uri, $query);
        return IS_MEMBER ? MEMBER_URL.$url : (IS_ADMIN ? $url : SITE_URL.$url);
    }

    // 应用配置继承类
    public function _admin_config() {

        // 判断是否具有配置权限
        !$this->is_auth('admin/application/config') && $this->admin_msg(L('抱歉！您无权限操作(%s)', 'application/config'));

        // 当前应用配置
        $data = $this->models('application')->get(APP_DIR);
        $config = require APPPATH.'config/app.php';

        if (IS_POST) {

            $setting = $this->input->post('data');

            if ($this->models('application')->edit($data['id'], array(
                'module' => array2string($this->input->post('module')),
                'setting' => array2string($setting)
            ))) {
                // 查询增加的模块
                $del = $this->input->post('del');
                if ($del) {
                    foreach ($del as $dir => $value) {
                        $value && $this->_delete_for_module($dir); // 删除该模块时
                    }
                }
                $this->admin_msg(L('操作成功，正在刷新...'), $this->url('home/cache', array('todo' => 1)), 1);
            }
        }

        $mod = array();
        $local = @array_diff(dr_dir_map(WEBPATH.'module/', 1), array('member')); // 搜索本地模块
        if ($local) {
            foreach ($local as $dir) {
                is_file(WEBPATH.'module/'.$dir.'/config/module.php') && $mod[$dir] = require WEBPATH.'module/'.$dir.'/config/module.php';
            }
        }

        $this->template->assign(array(
            'mod' => $data['module'],
            'data' => $data['setting'],
            'menu' => $this->get_menu(array(
                L('应用管理') => 'admin/application/index',
                L('应用配置') => APP_DIR.'/admin/home/index',
                L('更新缓存') => APP_DIR.'/admin/home/cache',
            )),
            'menu2' => $this->get_menu_v3(array(
                L('应用管理') => array('admin/application/index', 'cloud'),
                L('应用配置') => array(APP_DIR.'/admin/home/index', 'cog'),
                L('更新缓存') => array(APP_DIR.'/admin/home/cache', 'refresh')
            )),
            'module' => $mod,
            'module_app' => isset($config['related']) ? 1 : 0,
        ));

        return $data;
    }

    // 应用安装继承类
    public function _admin_install() {

        !$this->is_auth('admin/application/install') && $this->admin_msg(L('抱歉！您无权限操作(%s)', 'application/install'));

        $this->db->where('dirname', APP_DIR)->count_all_results('application') && $this->admin_msg(L('应用【%s】已经存在，安装失败', APP_DIR));
        
        // 插入应用数据库
        $id = $this->models('application')->add(APP_DIR);

        // 插入初始化数据
        if (is_file(FCPATH.'app/'.APP_DIR.'/config/install.sql')
            && $install = file_get_contents(FCPATH.'app/'.APP_DIR.'/config/install.sql')) {
            $_sql = str_replace(
                array('{dbprefix}', '{appid}', '{appdir}', '{siteid}'),
                array($this->db->dbprefix, $id, APP_DIR, SITE_ID),
                $install
            );
            $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $_sql)));
            foreach($sql_data as $query) {
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach($queries as $query) {
                    $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
                }
                if (!$ret) {
                    continue;
                }
                $this->db->query($ret);
            }
        }

        // 安装菜单
        if (is_file(FCPATH.'app/'.APP_DIR.'/config/menu.php')) {
            $menu = require FCPATH.'app/'.APP_DIR.'/config/menu.php';
            $this->models('site/menu')->set('admin')->add_app_menu($menu, APP_DIR, $id);
        }

        return $id;
    }

    // 应用卸载继承类
    public function _admin_uninstall() {

        !$this->is_auth('admin/application/uninstall') && $this->admin_msg(L('抱歉！您无权限操作(%s)', 'application/uninstall'));
       
        $data = $this->models('application')->get(APP_DIR);

        // 删除菜单
        $this->db->where('mark', 'app-'.APP_DIR)->delete('admin_menu');

        // 删除缓存
        $this->dcache->delete('app-'.APP_DIR);

        // 删除应用表数据
        $this->models('application')->del($data['id']);

        // 插入初始化数据
        if (is_file(FCPATH.'app/'.APP_DIR.'/config/uninstall.sql')
            && $install = file_get_contents(FCPATH.'app/'.APP_DIR.'/config/uninstall.sql')) {
            $_sql = str_replace(
                array('{dbprefix}', '{appid}', '{appdir}', '{siteid}'),
                array($this->db->dbprefix, $data['id'], APP_DIR, SITE_ID),
                $install
            );
            $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $_sql)));
            foreach($sql_data as $query) {
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach($queries as $query) {
                    $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
                }
                if (!$ret) {
                    continue;
                }
                $this->db->query($ret);
            }
        }

        return $data;
    }

    // 应用缓存继承类
    public function _admin_cache() {

        $data = $this->models('application')->get(APP_DIR);
        $config = require APPPATH.'config/app.php';

        $data['name'] = $config['name'];
        $data['related'] = $config['related'];

        $this->models('application')->cache();
        $this->dcache->set('app-'.APP_DIR, $data);
        
        return $data;
    }
}