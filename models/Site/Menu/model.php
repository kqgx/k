<?php

class Site_Menu_model extends CI_Model{

    public $prefix; // 表头
    public $tablename; // 表
    
	private $ids;
	
    public function __construct() {
        parent::__construct();
    }
	
	public function set($dir) {
        $this->prefix = $this->link->dbprefix($dir);
        $this->tablename = $this->prefix.'_menu';
        return $this;
	}
	
	/**
	 * 顶级菜单id
	 *
	 * @return	array
	 */
	public function get_top_id() {
		$_data = $this->link->select('id')->where('pid', 0)->order_by('id ASC')->get($this->tablename)->result_array();
		if (!$_data) {
			return NULL;
		}
		$data = array();
		foreach ($_data as $t) {
			$data[] = $t['id'];
		}
		return $data;
	}

	/**
	 * 分组菜单id
	 *
	 * @return	array
	 */
	public function get_left_id() {
		$_data = $this->link->select('id')->where_in('pid', $this->get_top_id())->order_by('id ASC')->get($this->tablename)->result_array();
		if (!$_data) {
			return NULL;
		}
		$data = array();
		foreach ($_data as $t) {
			$data[] = $t['id'];
		}
		return $data;
	}

	/**
	 * 添加菜单
	 *
	 * @param	array	$data	添加数据
	 * @return	void
	 */
	public function add($data) {
		if (!$data) {
			return NULL;
		}
		$uri = '/';
		$data['dir'] && $uri.= $data['dir'].'/';
		$data['directory'] && $uri.= $data['directory'].'/';
		$data['class'] && $uri.= $data['class'].'/';
		$data['method'] && $uri.= $data['method'].'/';
		$data['param'] && $uri.= $data['param'].'/';
		$insert	= array(
		    'pid' => $data['pid'],
			'uri' => trim($uri, '/'),
			'url' => $data['url'],
			'name' => $data['name'],
            'mark' => $data['mark'] ? $data['mark'] : '',
			'icon' => $data['icon'],
			'target' => $data['target'],
			'hidden' => (int)$data['hidden'],
			'displayorder' => 0,
		);
		$this->link->insert($this->tablename, $insert);
		$insert['id'] = $this->link->insert_id();
		$this->cache();
		return TRUE;
	}

	/**
	 * 修改菜单
	 *
	 * @param	intval	$id		
	 * @param	array	$data	数据
	 * @return	void
	 */
	public function edit($id, $data) {

		if (!$data || !$id) {
			return NULL;
		}
        
		$uri = '/';
		$data['dir'] && $uri.= $data['dir'].'/';
		$data['directory'] && $uri.= $data['directory'].'/';
		$data['class'] && $uri.= $data['class'].'/';
		$data['method'] && $uri.= $data['method'].'/';
		$data['param'] && $uri.= $data['param'].'/';

		$this->link->where('id', $id)->update($this->tablename, array(
			'uri' => trim($uri, '/'),
			'url' => $data['url'],
			'pid' => $data['pid'],
			'name' => $data['name'],
            'mark' => $data['mark'] ? $data['mark'] : '',
			'icon' => $data['icon'],
			'target' => $data['target'],
			'hidden' => (int)$data['hidden'],
		));

		$this->cache();

		return $id;
	}

	/**
	 * 父级菜单选择
	 *
	 * @param	intval	$level	级别
	 * @param	intval	$id		选中项id
	 * @param	intval	$name	select部分
	 * @return	string
	 */
	public function parent_select($level, $id = NULL, $name = NULL) {

		$select = $name ? $name : '<select name="data[pid]">';

		switch ($level) {
			case 0: // 顶级菜单
				$select.= '<option value="0">'.L('顶级菜单').'</option>';
				break;
			case 1: // 分组菜单
				$topdata = $this->link->select('id,name')->where('pid=0')->get($this->tablename)->result_array();
				foreach ($topdata as $t) {
					$select.= '<option value="'.$t['id'].'"'.($id == $t['id'] ? ' selected' : '').'>'.$t['name'].'</option>';
				}
				break;
			case 2: // 链接菜单
				$topdata = $this->link->select('id,name')->where('pid=0')->get($this->tablename)->result_array();
				foreach ($topdata as $t) {
					$select.= '<optgroup label="'.$t['name'].'">';
					$linkdata = $this->link->select('id,name')->where('pid='.$t['id'])->get($this->tablename)->result_array();
					foreach ($linkdata as $c) {
						$select.= '<option value="'.$c['id'].'"'.($id == $c['id'] ? ' selected' : '').'>'.$c['name'].'</option>';
					}
					$select.= '</optgroup>';
				}
				break;
		}

		$select.= '</select>';

		return $select;
	}

	/**
	 * 更新缓存
	 *
	 * @return	array
	 */
	public function cache() {
        $menu = array();
		$data = $this->link->where('hidden', 0)->order_by('displayorder ASC,id ASC')->get($this->tablename)->result_array();
		if ($data) {
			foreach ($data as $t) {
				if ($t['pid'] == 0) {
					$menu[$t['id']] = $t;
					foreach ($data as $m) {
						if ($m['pid'] == $t['id']) {
							$menu[$t['id']]['left'][$m['id']] = $m;
							foreach ($data as $n) {
								$n['pid'] == $m['id'] && $menu[$t['id']]['left'][$m['id']]['link'][$n['id']] = $n;
							}
						}
					}
				}
			}
			$this->ci->dcache->set('menu', $menu);
		} else {
			$this->ci->dcache->delete('menu');
		}
		return $menu;
	}

	/**
	 * 初始化菜单
	 *
	 * @return	array
	 */
	public function init() {

		// 清空菜单
		$this->link->query('TRUNCATE `'.$this->link->dbprefix($this->tablename).'`');

		// 按模块安装菜单
		$module = $this->link->get('module')->result_array();
		if ($module) {
			foreach ($module as $m) {
				$this->init_module($m);
			}
		}
		// 按应用安装菜单
		$app = $this->link->get('application')->result_array();
		if ($app) {
			foreach ($app as $a) {
				$dir = $a['dirname'];
				if (is_file(WEBPATH.'app/'.$dir.'/config/menu.php')) {
					$menu = require WEBPATH.'app/'.$dir.'/config/menu.php';
					$this->add_app_menu($menu, $dir, $a['id']);
				}
			}
		}
	}

	// 获取自己id和子id
	private function _get_id($id) {

		if (!$id) {
			return NULL;
		}

		$this->ids[$id] = $id;

		$data = $this->link->select('id')->where('pid', $id)->get($this->tablename)->result_array();
		if (!$data) {
			return NULL;
		}

		foreach ($data as $t) {
			$this->ids[$t['id']] = $t['id'];
			$this->_get_id($t['id']);
		}
	}

	// 删除菜单
	public function delete($ids) {

		$this->ids = array();

		if (is_array($ids)) {
			foreach ($ids as $id) {
				$this->_get_id($id);
			}
		} else {
			$this->_get_id($ids);
		}

		$this->ids && $this->link->where_in('id', $this->ids)->delete($this->tablename);

	}

	// 安装模块菜单
	public function init_module($m) {

		$id = $m['id'];
		$dir = $m['dirname'];

		// 菜单
		if (is_file(WEBPATH.'module/'.$dir.'/config/menu.php')) {
			$config = require WEBPATH.'module/'.$dir.'/config/module.php';
			$menu = require WEBPATH.'module/'.$dir.'/config/menu.php';
			if ($menu['admin']) {
				// 插入后台的顶级菜单
				$this->link->insert($this->tablename, array(
					'uri' => '',
					'pid' => 0,
					'mark' => $menu['mark'] ? $menu['mark'] : 'module-'.$dir,
					'name' => $config['name'],
					'icon' => $menu['icon'],
					'hidden' => 0,
					'displayorder' => 0,
				));
				$topid = $this->link->insert_id();
				$left_id = 0;
				foreach ($menu['admin'] as $left) { // 分组菜单名称
					$this->link->insert($this->tablename, array(
						'uri' => '',
						'pid' => $topid,
						'mark' => $left['mark'] ? $left['mark'] : 'module-'.$dir,
						'name' => dr_replace_m_name($left['name'], $config['name']),
						'icon' => $left['icon'],
						'hidden' => 0,
						'displayorder' => 0,
					));
					$leftid = $this->link->insert_id();
					$left_id = $left_id ? $left_id : $leftid;
					foreach ($left['menu'] as $link) { // 链接菜单
                        if (in_array($link['uri'], array(
                                'admin/page/index',
                                'admin/content/index',
                                'admin/home/content',
                            ))) {
                            continue;
                        }
						$this->link->insert($this->tablename, array(
							'pid' => $leftid,
							'uri' => dr_replace_m_uri($link, $id, $dir),
							'mark' => 'module-'.$dir,
							'name' => dr_replace_m_name($link['name'], $config['name']),
							'icon' => $link['icon'],
							'hidden' => 0,
							'displayorder' => 0,
						));
					}
				}
			}
		}
	}
	
    // 插入后台菜单
    public function add_admin_menu($data) {
        $this->db->insert('admin_menu', $data);
        return $this->db->insert_id();
    }

    // 安装应用菜单
    public function add_app_menu($menu, $dir, $id) {

        // 后台菜单
        if (isset($menu['admin']) && isset($menu['admin']['menu']) && $menu['admin']['menu']) {
            // 查询应用的顶级菜单
            $top = $this->db->select('id')->where('mark', 'myapp')->where('pid', 0)->get('admin_menu')->row_array();
            if (!$top) {
                // 模糊查询
                $top = $this->db->select('id')->where('name', '插件')->where('pid', 0)->get('admin_menu')->row_array();
                if (!$top) {
                    $this->add_admin_menu(array(
                        'uri' => '',
                        'pid' => 0,
                        'mark' => 'myapp',
                        'name' => '插件',
                        'hidden' => 0,
                        'displayorder' => 0,
                    ));
                    $top['id'] = $this->db->insert_id();
                }
            }
            $topid = (int)$top['id'];
            // 分组菜单
            if ($menu['admin']['name'] && $menu['admin']['icon']) {
                // 新建分组菜单
                $this->add_admin_menu(array(
                    'uri' => '',
                    'pid' => $topid,
                    'mark' => 'appp-'.$dir,
                    'name' => $menu['admin']['name'],
                    'icon' => $menu['admin']['icon'],
                    'hidden' => 0,
                    'displayorder' => 0,
                ));
                $leftid = $this->db->insert_id();
            } else {
                // 查询现有的分组菜单
                $left = $this->db->select('id')->where('mark', 'cloud-cloud')->get('admin_menu')->row_array();
                $leftid = (int)$left['id'];
                if (!$leftid) {
                    $this->add_admin_menu(array(
                        'uri' => '',
                        'pid' => $topid,
                        'mark' => 'cloud-cloud',
                        'name' => '应用插件',
                        'hidden' => 0,
                        'displayorder' => 0,
                    ));
                    $leftid = $this->db->insert_id();
                }
            }
            // 链接菜单
            foreach ($menu['admin']['menu'] as $link) {
                $muri = dr_replace_m_uri($link, $id, $dir);
                if (!$this->db->where('uri', $muri)->count_all_results('admin_menu')) {
                    $this->add_admin_menu(array(
                        'pid' => $leftid,
                        'uri' => $muri,
                        'mark' => 'app-'.$dir,
                        'name' => $link['name'],
                        'icon' => $link['icon'],
                        'hidden' => 0,
                        'displayorder' => 0,
                    ));
                }
            }
        }
    }
    
    public function repair(){
        // page
        // form
        // navigator
        // 
    }
    
	public function install(){
	    $this->link->query(trim("
            CREATE TABLE  IF NOT EXISTS `{$this->tablename}` (
              `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
              `pid` smallint(5) unsigned NOT NULL COMMENT '上级菜单id',
              `name` text NOT NULL COMMENT '菜单语言名称',
              `uri` varchar(255) DEFAULT NULL COMMENT 'uri字符串',
              `url` varchar(255) DEFAULT NULL COMMENT '外链地址',
              `mark` varchar(100) DEFAULT NULL COMMENT '菜单标识',
              `hidden` tinyint(1) unsigned DEFAULT NULL COMMENT '是否隐藏',
              `target` tinyint(3) unsigned DEFAULT NULL COMMENT '新窗口',
              `icon` varchar(255) DEFAULT NULL COMMENT '图标标示',
              `displayorder` tinyint(3) unsigned DEFAULT NULL COMMENT '排序值',
              PRIMARY KEY (`id`),
              KEY `list` (`pid`),
              KEY `displayorder` (`displayorder`),
              KEY `mark` (`mark`),
              KEY `hidden` (`hidden`),
              KEY `uri` (`uri`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='后台菜单表';
        "));
	}
	
	public function uninstall(){
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
	}
}