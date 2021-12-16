<?php

class Module_model extends CI_Model
{

    public $system_table; // 系统默认表

    /*
     * 模块模型类
     */
    public function __construct()
    {
        parent::__construct();
        $this->system_table = array(
            'recycle' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL,
			  `catid` smallint(5) unsigned NOT NULL COMMENT '分类id',
			  `content` mediumtext NOT NULL COMMENT '具体内容',
			  `inputtime` int(10) unsigned NOT NULL COMMENT '删除时间',
			  PRIMARY KEY `id` (`id`),
			  KEY `catid` (`catid`),
			  KEY `inputtime` (`inputtime`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='回收站表';",

            'draft' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
			  `eid` int(10) DEFAULT NULL COMMENT '扩展id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `catid` smallint(5) unsigned NOT NULL COMMENT '分类id',
			  `content` mediumtext NOT NULL COMMENT '具体内容',
			  `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
			  PRIMARY KEY `id` (`id`),
			  KEY `eid` (`eid`),
			  KEY `uid` (`uid`),
			  KEY `cid` (`cid`),
			  KEY `catid` (`catid`),
			  KEY `inputtime` (`inputtime`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容草稿表';",

            'index' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `catid` smallint(5) unsigned NOT NULL COMMENT '分类id',
	          `status` tinyint(2) NOT NULL COMMENT '审核状态',
			  `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
			  PRIMARY KEY (`id`),
			  KEY `uid` (`uid`),
			  KEY `catid` (`catid`),
			  KEY `status` (`status`),
			  KEY `inputtime` (`inputtime`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容索引表';",

            'extend_index' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `catid` smallint(5) unsigned NOT NULL COMMENT '分类id',
	          `status` tinyint(2) NOT NULL COMMENT '审核状态',
			  PRIMARY KEY (`id`),
			  KEY `cid` (`cid`),
			  KEY `uid` (`uid`),
			  KEY `catid` (`catid`),
			  KEY `status` (`status`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='扩展索引表';",

            'category' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
				`id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
				`pid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
				`pids` varchar(255) NOT NULL COMMENT '所有上级id',
				`name` varchar(30) NOT NULL COMMENT '分类名称',
				`letter` char(1) NOT NULL COMMENT '首字母',
				`dirname` varchar(30) NOT NULL COMMENT '分类目录',
				`pdirname` varchar(100) NOT NULL COMMENT '上级目录',
				`child` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有下级',
				`childids` text NOT NULL COMMENT '下级所有id',
				`thumb` varchar(255) NOT NULL COMMENT '分类图片',
				`show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
				`permission` text NULL COMMENT '会员权限',
				`setting` text NOT NULL COMMENT '属性配置',
				`displayorder` tinyint(3) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				KEY `show` (`show`),
				KEY `module` (`pid`,`displayorder`,`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='分类表';",

            'category_data' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `catid` smallint(5) unsigned NOT NULL COMMENT '分类id',
			  PRIMARY KEY (`id`),
			  KEY `uid` (`uid`),
			  KEY `catid` (`catid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='分类附加表';",

            'category_data_0' => "
			CREATE TABLE IF NOT EXISTS `{tablename}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `catid` smallint(5) unsigned NOT NULL COMMENT '分类id',
			  PRIMARY KEY (`id`),
			  KEY `uid` (`uid`),
			  KEY `catid` (`catid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='分类附加表';"

        );
    }

    /**
     * 所有模块
     *
     * @return    array
     */
    public function get_data()
    {

        $_data = $this->db->order_by('displayorder ASC,id ASC')->get('module')->result_array();
        if (!$_data) {
            return null;
        }

        $data = array();
        foreach ($_data as $t) {
            $t['site'] = string2array($t['site']);
            $t['setting'] = string2array($t['setting']);
            $data[$t['dirname']] = $t;
        }

        return $data;
    }

    /**
     * 模块数据
     *
     * @param int $id
     * @return    array
     */
    public function get($id)
    {

        if (is_numeric($id)) {
            $this->db->where('id', (int)$id);
        } else {
            $this->db->where('dirname', (string)$id);
        }
        $data = $this->db->limit(1)->get('module')->row_array();
        if (!$data) {
            return null;
        }

        $data['site'] = string2array($data['site']);
        $data['setting'] = string2array($data['setting']);

        // 模块名称
        $name = $this->db->select('name')->where('pid', 0)->where('mark',
            'module-' . $data['dirname'])->get('admin_menu')->row_array();
        $data['name'] = $name['name'] ? $name['name'] : $data['dirname'];

        return $data;
    }

    /**
     * 模块入库
     *
     * @param string $dir
     * @return    intval
     */
    public function add($dir, $config, $nodb = '')
    {

        if (!$dir) {
            return null;
        } elseif ($this->db->where('dirname', $dir)->count_all_results('module')) {
            // 判断重复安装
            return null;
        }

        $m = array(
            'site' => '',
            'extend' => $config['extend'],
            'dirname' => $dir,
            'setting' => '',
            'sitemap' => 1,
            'disabled' => 0,
            'displayorder' => 0,
        );
        $this->db->replace('module', $m);
        $m['id'] = $id = $this->db->insert_id();

        if (!$id) {
            return null;
        }

        // 非自定义表时
        if (!$nodb) {
            // 字段入库
            $main = require WEBPATH . 'module/' . $dir . '/config/main.table.php'; // 主表信息
            foreach ($main['field'] as $field) {
                $this->add_field($id, $field, 1);
            }
            $data = require WEBPATH . 'module/' . $dir . '/config/data.table.php'; // 附表信息
            if ($data['field']) {
                foreach ($data['field'] as $field) {
                    $this->add_field($id, $field, 0);
                }
            }
            //扩展内容表
            if ($config['extend']) {
                // 字段入库
                $main = require WEBPATH . 'module/' . $dir . '/config/extend.main.table.php'; // 主表信息
                foreach ($main['field'] as $field) {
                    $this->add_field($id, $field, 1, 1);
                }
                $data = require WEBPATH . 'module/' . $dir . '/config/extend.data.table.php'; // 附表信息
                if ($data['field']) {
                    foreach ($data['field'] as $field) {
                        $this->add_field($id, $field, 0, 1);
                    }
                }
            }
        } else {
            $install_file = WEBPATH . 'module/' . $dir . '/config/install.php'; // 自定义安装文件
            if (is_file($install_file)) {
                $is_add = 1;
                require $install_file;
            }
        }

        // 删除后台菜单
        $this->db->where('mark', 'module-' . $dir)->delete('admin_menu');
        $this->db->like('mark', 'module-' . $dir . '-%')->delete('admin_menu');

        // 重新安装菜单
        if (is_file(WEBPATH . 'module/' . $dir . '/config/menu.php')) {
            // 后台菜单
            $this->models('site/menu')->set('admin')->init_module($m);
        }

        return $id;
    }

    // 模块的导出
    public function export($dir, $name)
    {

        if (!is_dir(WEBPATH . 'module/' . $dir)) {
            return '模块目录不存在';
        }

        // 模块信息
        $module = $this->db->limit(1)->where('dirname', $dir)->get('module')->row_array();
        if (!$module) {
            return '模块不存在或者尚未安装';
        }
        $site = string2array($module['site']);
        if (!isset($site[SITE_ID]) || !$site[SITE_ID]['use']) {
            return '当前站点尚未安装此模块，无法生成';
        }

        // 模块配置文件
        $config = require WEBPATH . 'module/' . $dir . '/config/module.php';
        if (isset($config['nodb']) && $config['nodb']) {
            return '自定义数据表模块不允许生成';
        }

        $config['key'] = 0;
        $config['name'] = $name ? $name : $config['name'];
        $config['author'] = SITE_NAME;
        $config['version'] = '';
        $this->load->library('dconfig');
        $size = $this->dconfig->file(WEBPATH . 'module/' . $dir . '/config/module.php')->note('模块配置文件')->space(24)->to_require_one($config);
        if (!$size) {
            return '目录' . $dir . '不可写！';
        }

        // 主表字段
        $file = WEBPATH . 'module/' . $dir . '/config/main.table.php';
        $table = array();
        $header = $this->dconfig->file($file)->note('主表结构（由开发者定义）')->to_header();
        $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix(SITE_ID . '_' . $dir) . "`")->row_array();
        $table['sql'] = str_replace(array($sql['Table'], 'CREATE TABLE'),
            array('{tablename}', 'CREATE TABLE IF NOT EXISTS'), $sql['Create Table']);
        $field = $this->db
            ->where('relatedname', 'module')
            ->where('relatedid', (int)$module['id'])
            ->where('ismain', 1)
            ->get('field')
            ->result_array();
        if (!$field) {
            return '此模块无主表字段，不支持生成';
        }
        foreach ($field as $t) {
            $t['textname'] = $t['name'];
            unset($t['id'], $t['name']);
            $t['issystem'] = 1;
            $t['setting'] = string2array($t['setting']);
            $table['field'][] = $t;
        }
        file_put_contents($file, $header . PHP_EOL . 'return ' . var_export($table, true) . ';?>');

        // 附表字段
        $file = WEBPATH . 'module/' . $dir . '/config/data.table.php';
        $table = array();
        $header = $this->dconfig->file($file)->note('附表结构（由开发者定义）')->to_header();
        $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix(SITE_ID . '_' . $dir . '_data_0') . "`")->row_array();
        $table['sql'] = str_replace(array($sql['Table'], 'CREATE TABLE'),
            array('{tablename}', 'CREATE TABLE IF NOT EXISTS'), $sql['Create Table']);
        $field = $this->db
            ->where('relatedname', 'module')
            ->where('relatedid', (int)$module['id'])
            ->where('ismain', 0)
            ->get('field')
            ->result_array();
        if ($field) {
            foreach ($field as $t) {
                $t['textname'] = $t['name'];
                unset($t['id'], $t['name']);
                $t['issystem'] = 1;
                $t['setting'] = string2array($t['setting']);
                $table['field'][] = $t;
            }
        }
        file_put_contents($file, $header . PHP_EOL . 'return ' . var_export($table, true) . ';?>');

        if ($config['extend']) {
            // 内容扩展表字段
            $file = WEBPATH . 'module/' . $dir . '/config/extend.main.table.php';
            $table = array();
            $header = $this->dconfig->file($file)->note('内容扩展表结构（由开发者定义）')->to_header();
            $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix(SITE_ID . '_' . $dir . '_extend') . "`")->row_array();
            $table['sql'] = str_replace(array($sql['Table'], 'CREATE TABLE'),
                array('{tablename}', 'CREATE TABLE IF NOT EXISTS'), $sql['Create Table']);
            $field = $this->db->where('relatedname', 'extend')->where('relatedid',
                (int)$module['id'])->get('field')->result_array();
            if ($field) {
                foreach ($field as $t) {
                    $t['textname'] = $t['name'];
                    unset($t['id'], $t['name']);
                    $t['issystem'] = 1;
                    $t['setting'] = string2array($t['setting']);
                    $table['field'][] = $t;
                }
            }
            file_put_contents($file, $header . PHP_EOL . 'return ' . var_export($table, true) . ';?>');

            // 内容扩展附表字段
            $file = WEBPATH . 'module/' . $dir . '/config/extend.data.table.php';
            $table = array();
            $header = $this->dconfig->file($file)->note('内容扩展附表结构（由开发者定义）')->to_header();
            $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix(SITE_ID . '_' . $dir . '_extend_data_0') . "`")->row_array();
            $table['sql'] = str_replace(array($sql['Table'], 'CREATE TABLE'),
                array('{tablename}', 'CREATE TABLE IF NOT EXISTS'), $sql['Create Table']);
            $field = $this->db->where('relatedname', 'extend')->where('relatedid',
                (int)$module['id'])->get('field')->result_array();
            if ($field) {
                foreach ($field as $t) {
                    $t['textname'] = $t['name'];
                    unset($t['id'], $t['name']);
                    $t['issystem'] = 0;
                    $t['setting'] = string2array($t['setting']);
                    $table['field'][] = $t;
                }
            }
            file_put_contents($file, $header . PHP_EOL . 'return ' . var_export($table, true) . ';?>');
        }

        return null;
    }

    /**
     * 字段入库
     *
     * @param intval $id 模块id
     * @param array $field 字段信息
     * @param intval $ismain 是否主表
     * @param intval $extend 是否是扩展表
     * @return    bool
     */
    private function add_field($id, $field, $ismain, $extend = 0)
    {

        $rname = $extend ? 'extend' : 'module';
        if ($this->db->where('fieldname', $field['fieldname'])->where('relatedid', (int)$id)->where('relatedname',
            $rname)->count_all_results('field')) {
            return;
        }

        $this->db->insert('field', array(
            'name' => $field['textname'],
            'ismain' => $ismain,
            'setting' => array2string($field['setting']),
            'issystem' => isset($field['issystem']) ? (int)$field['issystem'] : 1,
            'ismember' => isset($field['ismember']) ? (int)$field['ismember'] : 1,
            'disabled' => isset($field['disabled']) ? (int)$field['disabled'] : 0,
            'fieldname' => $field['fieldname'],
            'fieldtype' => $field['fieldtype'],
            'relatedid' => (int)$id,
            'relatedname' => $rname,
            'displayorder' => (int)$field['displayorder'],
        ));
    }

    /**
     * 安装到站点
     *
     * @param intval $id 模块id
     * @param string $dir 模块目录
     * @param array $siteid 站点id
     * @param array $config 模块配置
     * @param intval $nodb 是否自定义数据模块
     * @param intval $_siteid 已经安装过的站点id
     * @return    void
     */
    public function install($id, $dir, $siteid, $config, $nodb = 0, $_siteid = 0)
    {

        if (!$id || !$dir || !$siteid) {
            return 'id、dir、siteid不完整';
        } elseif (!isset($this->db)) {
            return 'db为空';
        }

        $install = null; // 初始化数据

        // 表前缀部分：站点id_模块目录[_表名称]
        $prefix = $this->db->dbprefix($siteid . '_' . $dir);

        // 非系统表属性时才导入系统表
        if (!$nodb) {
            // 主表
            $sql = '';
            if ($_siteid) {
                // 从站点现存表中获取表结构
                $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix($_siteid . '_' . $dir) . "`")->row_array();
                $sql = str_replace(
                    array($sql['Table'], 'CREATE TABLE'),
                    array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                    $sql['Create Table']
                );
            }
            if (!$sql) {
                // 从本地配置中获取表结构
                $cfg = require WEBPATH . 'module/' . $dir . '/config/main.table.php';
                $sql = $cfg['sql'];
            }
            $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '`');
            $this->db->query(trim(str_replace('{tablename}', $prefix, $sql)));
            // 更改状态字段长度
            $this->db->query('ALTER TABLE `' . $prefix . '` CHANGE `status` `status` TINYINT(2) NOT NULL COMMENT "状态";');
            // 附表
            $sql = '';
            if ($_siteid) {
                // 从站点现存表中获取表结构
                $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix($_siteid . '_' . $dir . '_data_0') . "`")->row_array();
                $sql = str_replace(
                    array($sql['Table'], 'CREATE TABLE'),
                    array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                    $sql['Create Table']
                );
            }
            if (!$sql) {
                // 从本地配置中获取表结构
                $cfg = require WEBPATH . 'module/' . $dir . '/config/data.table.php';
                $sql = $cfg['sql'];
            }
            $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '_data_0' . '`');
            $this->db->query(trim(str_replace('{tablename}', $prefix . '_data_0', $sql)));

            // 扩展表
            if ($config['extend']) {
                // 扩展主表
                $sql = '';
                if ($_siteid) {
                    // 从站点现存表中获取表结构
                    $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix($_siteid . '_' . $dir . '_extend') . "`")->row_array();
                    $sql = str_replace(
                        array($sql['Table'], 'CREATE TABLE'),
                        array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                        $sql['Create Table']
                    );
                }
                if (!$sql) {
                    // 从本地配置中获取表结构
                    $cfg = require WEBPATH . 'module/' . $dir . '/config/extend.main.table.php';
                    $sql = $cfg['sql'];
                }
                $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '_extend' . '`');
                $this->db->query(trim(str_replace('{tablename}', $prefix . '_extend', $sql)));

                // 更改状态字段长度
                $this->db->query('ALTER TABLE `' . $prefix . '` CHANGE `status` `status` TINYINT(2) NOT NULL COMMENT "状态";');
                // 扩展附表
                $sql = '';
                if ($_siteid) {
                    // 从站点现存表中获取表结构
                    $sql = $this->db->query("SHOW CREATE TABLE `" . $this->db->dbprefix($_siteid . '_' . $dir . '_extend_data_0') . "`")->row_array();
                    $sql = str_replace(
                        array($sql['Table'], 'CREATE TABLE'),
                        array('{tablename}', 'CREATE TABLE IF NOT EXISTS'),
                        $sql['Create Table']
                    );
                }
                if (!$sql) {
                    // 从本地配置中获取表结构
                    $cfg = require WEBPATH . 'module/' . $dir . '/config/extend.data.table.php';
                    $sql = $cfg['sql'];
                }
                $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '_extend_data_0' . '`');
                $this->db->query(trim(str_replace('{tablename}', $prefix . '_extend_data_0', $sql)));
            }

            // 系统默认表
            foreach ($this->system_table as $table => $sql) {
                // 不是扩展模块就不执行扩展表
                if (strpos($table, 'extend_') === 0 && !$config['extend']) {
                    continue;
                }
                $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '_' . $table . '`');
                $this->db->query(trim(str_replace('{tablename}', $prefix . '_' . $table, $sql)));
            }
            $this->install_models($config['models'], $dir, $siteid);
            if ($config['extend']) {
                $this->models('Module/comment')->extend($dir);
                $this->models('Module/comment')->install($siteid);
            }

        } else {
            $install_file = WEBPATH . 'module/' . $dir . '/config/install.php'; // 自定义安装文件
            if (is_file($install_file)) {
                $is_install = 1;
                require $install_file;
            }
        }

        // 插入初始化数据
        if (is_file(WEBPATH . 'module/' . $dir . '/config/install.sql')
            && $install = file_get_contents(WEBPATH . 'module/' . $dir . '/config/install.sql')) {
            $_sql = str_replace(
                array('{tablename}', '{dbprefix}', '{moduleid}', '{moduledir}', '{siteid}'),
                array($prefix, $this->db->dbprefix, $id, $dir, SITE_ID),
                $install
            );
            $sql_data = explode(';SQL_FINECMS_EOL',
                trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $_sql)));
            foreach ($sql_data as $query) {
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach ($queries as $query) {
                    $ret .= $query[0] == '#' || $query[0] . $query[1] == '--' ? '' : $query;
                }
                if (!$ret) {
                    continue;
                }
                // 如果此模块已经在其他站点中安装就不导入插入语句
                if ($_siteid &&
                    (stripos($ret, 'REPLACE INTO') === 0 || stripos($ret, 'INSERT INTO') === 0)) {
                    continue;
                }
                $this->db->query($ret);
            }
            unset($query, $sql_data, $_sql, $queries, $ret);
        }
        return 'true';
    }

    /**
     * 从站点中卸载
     *
     * @param intval $id 模块id
     * @param string $dir 模块目录
     * @param array $siteid 站点id
     * @param intval $delete 是否删除菜单
     * @return    void
     */
    public function uninstall($id, $dir, $siteid, $delete = 0)
    {

        if (!$id || !$dir || !$siteid || !isset($this->db)) {
            return null;
        }

        $config = require WEBPATH . 'module/' . $dir . '/config/module.php'; // 配置信息

        // 表前缀部分：站点id_模块目录[_表名称]
        $prefix = $this->db->dbprefix($siteid . '_' . $dir);

        // 清空附件
        $this->models('system/attachment')->delete_for_table($prefix, true);

        // 判断是否为系统模块
        $nodb = isset($config['nodb']) && $config['nodb'] ? 1 : 0;
        if (!$nodb) {
            // 主表
            $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '`');
            // 附表
            for ($i = 0; $i < 100; $i++) {
                if (!$this->db->query("SHOW TABLES LIKE '" . $prefix . '_data_' . $i . "'")->row_array()) {
                    break;
                }
                $this->db->query('DROP TABLE IF EXISTS ' . $prefix . '_data_' . $i);
            }

            // 扩展表
            if ($config['extend']) {

                // 主表
                $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '_extend`');
                // 附表
                for ($i = 0; $i < 100; $i++) {
                    if (!$this->db->query("SHOW TABLES LIKE '" . $prefix . '_extend_data_' . $i . "'")->row_array()) {
                        break;
                    }
                    $this->db->query('DROP TABLE IF EXISTS ' . $prefix . '_extend_data_' . $i);
                }
            }

            // 系统默认表
            foreach ($this->system_table as $table => $sql) {
                $this->db->query('DROP TABLE IF EXISTS `' . $prefix . '_' . $table . '`');
            }
            $this->uninstall_models($config['models'], $dir, $siteid);

            // 删除分类字段
            $this->db->where('relatedname', $dir . '-' . $siteid)->delete('field');

        }

        // 当站点数量小于2时删除菜单
        if ($delete < 2) {
            // 删除后台菜单
            $this->db->where('mark', 'module-' . $dir)->delete('admin_menu');
            $this->db->like('mark', 'module-' . $dir . '-%')->delete('admin_menu');
        }

        // 插入初始化数据
        if (is_file(WEBPATH . 'module/' . $dir . '/config/uninstall.sql') && $uninstall = file_get_contents(WEBPATH . 'module/' . $dir . '/config/uninstall.sql')) {
            $_sql = str_replace(
                array('{tablename}', '{dbprefix}', '{moduleid}', '{moduledir}', '{siteid}'),
                array($prefix, $this->db->dbprefix, $id, $dir, SITE_ID),
                $uninstall
            );
            $sql_data = explode(';SQL_FINECMS_EOL',
                trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $_sql)));
            foreach ($sql_data as $query) {
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach ($queries as $query) {
                    $ret .= $query[0] == '#' || $query[0] . $query[1] == '--' ? '' : $query;
                }
                if (!$ret) {
                    continue;
                }
                $this->db->query($ret);
            }
            unset($query, $sql_data, $_sql, $queries, $ret);
        }

        // 删除应用相关表
        $app = $this->ci->get_cache('app');
        if ($app) {
            foreach ($app as $adir) {
                if (is_file(WEBPATH . 'module/' . $adir . '/models/' . $adir . '_model.php')) {
                    $this->load->add_package_path(FCPATH . 'app/' . $adir . '/');
                    $this->load->model($adir . '_model', 'app_model');
                    $this->app_model->delete_for_module($dir, $siteid);
                    $this->load->remove_package_path(FCPATH . 'app/' . $adir . '/');
                }
            }
        }
    }

    /**
     * 清空当前站点的模块数据
     *
     * @param string $dir 模块目录
     * @return    void
     */
    public function clear($dir, $site)
    {

        if (!$dir) {
            return null;
        }

        $config = require WEBPATH . 'module/' . $dir . '/config/module.php'; // 配置信息

        // 表前缀部分：站点id_模块目录[_表名称]
        $prefix = $this->db->dbprefix($site . '_' . $dir);
        // 主表
        $this->db->query('TRUNCATE TABLE `' . $prefix . '`');
        // 附表
        for ($i = 0; $i < 100; $i++) {
            if (!$this->db->query("SHOW TABLES LIKE '" . $prefix . '_data_' . $i . "'")->row_array()) {
                break;
            }
            $this->db->query('TRUNCATE TABLE ' . $prefix . '_data_' . $i);
        }
        // 扩展模块
        if ($config['extend']) {
            // 主表
            $this->db->query('TRUNCATE TABLE `' . $prefix . '_extend`');
            // 扩展表
            for ($i = 0; $i < 100; $i++) {
                if (!$this->db->query("SHOW TABLES LIKE '" . $prefix . '_extend_data_' . $i . "'")->row_array()) {
                    break;
                }
                $this->db->query('TRUNCATE TABLE ' . $prefix . '_extend_data_' . $i);
            }
        }
        // 系统默认表
        foreach ($this->system_table as $table => $sql) {
            // 不是扩展模块就不执行扩展表
            if (strpos($table, 'extend_') === 0 && !$config['extend']) {
                continue;
            }
            $this->db->query('TRUNCATE TABLE `' . $prefix . '_' . $table . '`');
        }
        // 删除应用相关表
        $app = $this->ci->get_cache('app');
        if ($app) {
            foreach ($app as $adir) {
                if (is_file(FCPATH . 'app/' . $adir . '/models/' . $adir . '_model.php')) {
                    $this->load->add_package_path(FCPATH . 'app/' . $adir . '/');
                    $this->load->model($adir . '_model', 'app_model');
                    $this->app_model->delete_for_module($dir, $site);
                }
            }
        }
    }

    /**
     * 修改
     *
     * @param array $_data 老数据
     * @param array $data 新数据
     * @return    void
     */
    public function edit($id, $data)
    {
        $this->db->where('id', $id)->update('module', array(
            'sitemap' => (int)$data['sitemap'],
            'setting' => array2string($data['setting'])
        ));
    }

    /**
     * 删除
     *
     * @param intval $id
     * @return    void
     */
    public function del($id)
    {
        // 模块信息
        $data = $this->get($id);
        if (!$data) {
            return null;
        }
        // 删除模块数据和卸载全部站点
        $this->db->where('id', $id)->delete('module');
        foreach ($data['site'] as $siteid => $url) {
            $this->uninstall($data['id'], $data['dirname'], $siteid);
            $this->db->where('relatedname', $data['dirname'] . '-' . $siteid)->delete('field');
        }
        // 删除模块字段
        $this->db->where('relatedname', 'module')->where('relatedid', (int)$id)->delete('field');
        // 删除扩展字段
        $this->db->where('relatedname', 'extend')->where('relatedid', (int)$id)->delete('field');
    }

    /**
     * 格式化字段数据
     *
     * @param array $data 新数据
     * @return    array
     */
    private function get_field_value($data)
    {
        if (!$data) {
            return null;
        }
        $data['setting'] = string2array($data['setting']);
        return $data;
    }

    /**
     * 模块缓存
     *
     * @param string $data 模块
     * @return    NULL
     */
    public function _cache($data)
    {

        if (!is_array($data)) {
            return null;
        }

        $dirname = $data['dirname'];

        $this->load->library('dconfig');
        // 加载站点域名配置文件
        $site_domain = require CONFPATH . 'domain.php';

        if (is_dir(WEBPATH . 'module/' . $dirname . '/')) {
            // 独立模块
            $config = require WEBPATH . 'module/' . $dirname . '/config/module.php'; // 配置信息

            $data['site'] = string2array($data['site']);
            $config['nodb'] = $dirname == 'weixin' ? 1 : intval($config['nodb']); // 将微信强制列入非系统数据表类型
            $data['setting'] = string2array($data['setting']);

            // 按站点生成缓存
            foreach ($this->site_info as $siteid => $t) {
                if (!$siteid) {
                    continue;
                }
                $cache = $data;
                $this->dcache->set('module-' . $siteid . '-' . $dirname, array());
                if (isset($data['site'][$siteid]['use']) && $data['site'][$siteid]['use']) {
                    // 模块域名
                    $domain = $data['site'][$siteid]['domain'];
                    if ($domain) {
                        $site_domain[$domain] = $siteid;
                    }
                    $mobile_domain = $data['site'][$siteid]['mobile_domain'];
                    if ($mobile_domain) {
                        $site_domain[$mobile_domain] = $siteid;
                    }
                    // 将站点保存至域名配置文件
                    $cache['html'] = $data['site'][$siteid]['html'];
                    $cache['domain'] = $domain ? dr_http_prefix($domain . '/') : '';
                    $cache['mobile_domain'] = $mobile_domain ? dr_http_prefix($mobile_domain . '/') : '';
                    // 模块的URL地址
                    $cache['url'] = dr_module_url($cache, $siteid);
                    // 模块的自定义字段
                    $field = $this->db
                        ->where('disabled', 0)
                        ->where('relatedid', (int)$data['id'])
                        ->where('relatedname', 'module')
                        ->order_by('displayorder ASC, id ASC')
                        ->get('field')->result_array();
                    if ($field) {
                        foreach ($field as $f) {
                            $cache['field'][$f['fieldname']] = $this->get_field_value($f);
                        }
                    } else {
                        $cache['field'] = array();
                    }
                    // 模块扩展的自定义字段
                    if ($data['extend']) {
                        $field = $this->db
                            ->where('disabled', 0)
                            ->where('relatedid', (int)$data['id'])
                            ->where('relatedname', 'extend')
                            ->order_by('displayorder ASC, id ASC')
                            ->get('field')->result_array();
                        $cache['extend'] = array();
                        if ($field) {
                            foreach ($field as $f) {
                                $cache['extend'][$f['fieldname']] = $this->get_field_value($f);
                            }
                        }
                    } else {
                        $cache['extend'] = 0;
                    }
                    // 系统模块格式
                    if ($config['nodb'] == 0) {
                        $cdir = ($config['category'] ? $config['category'] : $dirname);
                        $category = $this->db->order_by('displayorder ASC, id ASC')->get($siteid . '_' . $cdir . '_category')->result_array();
                        if ($category) {
                            $category_cache = $this->ci->get_cache('module-' . $siteid . '-' . $dirname, 'category');
                            $CAT = $CAT_DIR = $fenzhan = $level = array();
                            foreach ($category as $c) {
                                $pid = explode(',', $c['pids']);
                                $level[] = substr_count($c['pids'], ',');
                                $c['mid'] = isset($c['mid']) ? $c['mid'] : $cache['dirname'];
                                $c['topid'] = isset($pid[1]) ? $pid[1] : $c['id'];
                                $c['domain'] = isset($c['domain']) ? $c['domain'] : $cache['domain'];
                                $c['catids'] = explode(',', $c['childids']);
                                $c['setting'] = string2array($c['setting']);
                                $c['pcatpost'] = intval($cache['setting']['pcatpost']);
                                $c['setting']['html'] = $cache['html'];
                                $c['setting']['urlrule'] = intval($cache['site'][$siteid]['urlrule']);
                                $c['permission'] = $c['child'] && !$c['setting']['pcatpost'] ? '' : string2array($c['permission']);
                                if (isset($c['tid']) && $c['tid'] != 2) {
                                    $c['setting']['linkurl'] = '';
                                }

                                $c['url'] = isset($c['setting']['linkurl']) && $c['setting']['linkurl'] ? $c['setting']['linkurl'] : dr_category_url($cache,
                                    $c, 0, $siteid);
                                $c['total'] = ($category_cache[$c['id']]['total']);
                                // 删除过期的部分
                                unset($c['setting']['urlmode']);
                                unset($c['setting']['url']);
                                $CAT[$c['id']] = $c;
                                $CAT_DIR[$c['dirname']] = $c['id'];
                            }
                            // 分类自定义字段，把父级分类的字段合并至当前分类
                            $field = $this->db
                                ->where('disabled', 0)
                                ->where('relatedname', ($dirname) . '-' . $siteid)
                                ->order_by('displayorder ASC, id ASC')
                                ->get('field')->result_array();
                            if ($field) {
                                foreach ($field as $f) {
                                    if (isset($CAT[$f['relatedid']]['childids'])
                                        && $CAT[$f['relatedid']]['childids']) {
                                        // 将该字段同时归类至其子分类
                                        $child = explode(',', $CAT[$f['relatedid']]['childids']);
                                        foreach ($child as $catid) {
                                            $CAT[$catid] && $CAT[$catid]['field'][$f['fieldname']] = $this->get_field_value($f);
                                        }
                                    }
                                }
                            }
                            $cache['category'] = $CAT;
                            $cache['category_dir'] = $CAT_DIR;
                            $cache['category_field'] = $field ? 1 : 0;
                            $cache['category_level'] = $level ? max($level) : 0;
                        } else {
                            $cache['category'] = array();
                            $cache['category_dir'] = array();
                            $cache['category_field'] = $cache['category_level'] = 0;
                        }
                        $cache['is_system'] = 1;

                    } else {
                        $cache['is_system'] = 0;
                    }
                    // 模块名称
                    $name = $this->db
                        ->select('name,icon')
                        ->where('pid', 0)
                        ->where('mark', 'module-' . $dirname)
                        ->get('admin_menu')
                        ->row_array();
                    $cache['name'] = $name['name'] ? $name['name'] : $config['name'];
                    $cache['icon'] = $name['icon'] ? $name['icon'] : 'fa fa-square';
                    $this->dcache->set('module-' . $siteid . '-' . $dirname, $cache);
                }
            }
        } else {
            return null;
        }

        $this->dconfig->file(CONFPATH . 'domain.php')->note('站点域名文件')->space(32)->to_require_one($site_domain);
    }

    public function cache($temp = '')
    {
        if ($temp) {
            return;
        }
        $this->_cache(array(
            'dirname' => 'share'
        ));
        $module = $this->db->where('disabled', 0)->get('module')->result_array();
        if ($module) {
            foreach ($module as $m) {
                $this->_cache($m);
            }
        }
    }

    public function install_models($models, $dir, $siteid)
    {
        foreach (string2array($models) as $value) {
            $this->models($value)->module($dir, $siteid);
            $this->models($value)->install();
        }
    }

    public function uninstall_models($models, $dir, $siteid)
    {
        foreach (string2array($models) as $value) {
            $this->models($value)->module($dir, $siteid);
            $this->models($value)->uninstall();
        }
    }

    public function flagChapter($flag, $mod = 'news', $nums = 7, $where = [])
    {
        $this->db->from(SITE_ID . "_{$mod} as news")
                 ->join(SITE_ID . "_{$mod}_flag as flag", 'news.id=flag.id', 'left');
        if ($mod == 'team') {
            $this->db->select('news.id,news.updatetime,news.thumb,news.title,news.url, news.description, news.zhiwei, news.image');
        } else {
            $this->db->select('news.id,news.updatetime,news.thumb,news.title,news.url, news.description');
        }

        if($where) {
            $this->db->where($where);
        }
         return $this->db->where(['news.status' => 9, 'flag.flag' => $flag])
                       ->order_by('news.displayorder desc, news.id DESC')
                       ->limit($nums)
                       ->get()
                       ->{$nums > 1 ? 'result_array' : 'row_array'}();
    }

    public function getListCategory($module, $nums)
    {
        return $this->db
            ->order_by('displayorder desc, id asc')
            ->limit($nums)
            ->get('1_' . $module . '_category')
            ->result_array();
    }
}