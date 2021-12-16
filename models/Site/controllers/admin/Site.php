<?php

class Site extends M_Controller {
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		
		$this->template->assign(array(
			'menu' => $this->get_menu_v3(array(
				L('网站管理') => array('admin/site/index', 'globe'),
				L('添加') => array('admin/site/add_js', 'plus'),
				L('配置') => array(isset($_GET['id']) && $_GET['id'] ? 'admin/site/config/id/'.(int)$_GET['id'] : 'admin/site/config', 'cog'),
			))
		));
		$this->load->library('dconfig');
    }
	
	/**
     * 切换
     */
    public function select() {
	
		$id	= (int)$this->input->get('id');
		if (!isset($this->site_info[$id])) {
            exit($this->admin_msg(L('域名配置文件中站点(#%s)不存在', $id)));
        }

        // 异步通知对方域名
        $this->cache->file->save('admin_login_site_select', array2string(array(
            'uid' => $this->admin['uid'],
            'password' => $this->member['password'],
        )), 300);

        $this->admin_msg(
            L('正在切换到【%s】...',
            $this->site_info[$id]['SITE_NAME']).'<script src="'.$this->site_info[$id]['SITE_URL'].'index.php?c=api&m=aslogin&code='.dr_authcode($this->member['uid'].'-'.md5($this->member['uid'].$this->member['password']), 'ENCODE').'"></script>',
            $this->site_info[$id]['SITE_URL'].SELF, 1,
            0
        );
	}

    /**
     * 管理
     */
    public function index() {
	
		if (IS_POST) {
			$ids = $this->input->post('ids');
			if (!$ids) {
                $this->msg(0, L('您还没有选择呢'));
            }
			$_data = $this->input->post('data');
			foreach ($ids as $id) {
                if ($this->db->where('id<>', (int)$id)->where('domain', $_data[$id]['domain'])->count_all_results('site')) {
                    $this->msg(0, L('域名【%s】已经被使用了', $_data[$id]['domain']));
                }
				$this->db->where('id', (int)$id)->update('site', $_data[$id]);
			}
            $this->models('site')->cache();
            $this->system_log('修改网站站点【#'.@implode(',', $ids).'】'); // 记录日志
			$this->msg(1, L('操作成功，更新缓存生效'));
		}

		$this->template->assign('list', $this->models('site')->get_site_data());
		$this->template->display('site_index.html');
	}
	
	/**
     * 添加
     */
    public function add() {
	
		if (IS_POST) {
			$this->load->library('dconfig');
			$data = $this->input->post('data', TRUE);
			$domain	= require CONFPATH.'domain.php';
			if (!$data['name']) {
                $this->msg(0, '', 'name');
            } elseif (!preg_match('/[\w-_\.]+\.[\w-_\.]+/i', $data['domain'])) {
                $this->msg(0, '', 'domain');
            } elseif (in_array($data['domain'], $domain)) {
                $this->msg(0, L('%s已经存在', $data['domain']), 'domain');
            } elseif ($this->db->where('domain', $data['domain'])->count_all_results('site')) {
                $this->msg(0, L('域名【%s】已经被使用了', $data['domain']), 'domain');
            }
			// 初始化网站配置
			$cfg['SITE_NAME'] = $data['name'];
			$cfg['SITE_DOMAIN'] = $data['domain'];
			$cfg['SITE_DOMAINS'] = '';
			$cfg['SITE_TIMEZONE'] = '8';
			$cfg['SITE_LANGUAGE'] = 'zh-cn';
			$cfg['SITE_TIME_FORMAT'] = 'Y-m-d H:i';
			// 入库
			$data['setting'] = $cfg;
			$id	= $this->models('site')->add($data);
			if (!$id) {
                $this->msg(0, L('数据异常，入库失败'));
            }

			// 安装站点时执行的SQL
			if (is_file(WEBPATH.'cache/install/site/install.sql')
				&& $sql = file_get_contents(WEBPATH.'cache/install/site/install.sql')) {
				$this->sql_query(str_replace(
					array('{dbprefix}', '{siteid}'),
					array($this->db->dbprefix, $id),
					$sql
				));
			}
			
			// 保存域名
			$domain[$data['domain']] = $id;
			$size = $this->dconfig->file(CONFPATH.'site/'.$id.'.php')->note('站点配置文件')->space(32)->to_require_one($this->models('site')->config, $cfg);
			if (!$size) {
                $this->msg(0, L('网站域名文件创建失败，请检查config目录权限'));
            }
			$size = $this->dconfig->file(CONFPATH.'domain.php')->note('站点域名文件')->space(32)->to_require_one($domain);
			if (!$size) {
                $this->msg(0, L('站点配置文件创建失败，请检查config目录权限'));
            }
            $this->models('site')->cache();
            $this->system_log('添加网站站点【#'.$id.'】'.$data['name']); // 记录日志
			$this->msg(1, L('操作成功，更新缓存生效'));
		} else {
			$this->template->display('site_add.html');
		}
    }
	
	/**
     * 站点配置
     */
    public function config() {

		$id = isset($_GET['id']) ? max((int)$_GET['id'], 1) : SITE_ID;
        if ($this->admin['adminid'] > 1
            && !@in_array($id, $this->admin['role']['site'])) {
            $this->admin_msg(L('抱歉！您无权限操作(%s)', 'site'));
        }

		$data = $this->models('site')->get_site_info($id);
		if (!$data) {
            $this->admin_msg(L('域名配置文件中站点(#%s)不存在', $id));
        }

		$page = max((int)$this->input->get('page'), 0);
        $result	= '';

		if (IS_POST) {
			$cfg = $this->input->post('data', true);
			$cfg['SITE_DOMAIN'] = $this->input->post('domain');
            // 查询非当前站点绑定的域名
            $as = array();
            $all = $this->db->where('id<>', $id)->get('site')->result_array();
            if ($all) {
                foreach ($all as $b) {
                    $set = string2array($b['setting']);
                    $as[] = $b['domain'];
                    if ($set['SITE_MOBILE']) {
                        $as[] = $set['SITE_MOBILE'];
                    }
                    if ($set['SITE_DOMAINS']) {
                        $_arr = @explode(',', $set['SITE_DOMAINS']);
                        if ($_arr) {
                            foreach ($_arr as $_a) {
                                if ($_a) {
                                    $as[] = $_a;
                                }
                            }
                        }
                    }
                }
            }
            // 判断域名是否可用
            if (in_array($cfg['SITE_DOMAIN'], $as)) {
                $result = L('域名【%s】已经被使用了', $cfg['SITE_DOMAIN']);
            } else {
                $cfg['SITE_DOMAINS'] = str_replace(PHP_EOL, ',', $cfg['SITE_DOMAINS']);
                // 多域名验证
                if ($cfg['SITE_DOMAINS']) {
                    $arr = @explode(',', $cfg['SITE_DOMAINS']);
                    if ($arr) {
                        foreach ($arr as $a) {
                            if (in_array($a, $as)
                                || $a == $cfg['SITE_DOMAIN']
                                || $a == $cfg['SITE_MOBILE']) {
                                $result = L('域名【%s】已经被使用了', $a);
                                break;
                            }
                        }
                    }
                }
                if (!$result) {
                    $cfg['SITE_NAVIGATOR'] = @implode(',', $this->input->post('navigator', TRUE));
                    $cfg['SITE_IMAGE_CONTENT'] = $cfg['SITE_IMAGE_WATERMARK'] ? $cfg['SITE_IMAGE_CONTENT'] : 0;
                    $data = array(
                        'name' => $cfg['SITE_NAME'],
                        'domain' => $cfg['SITE_DOMAIN'],
                        'setting' => $cfg
                    );
                    $this->models('site')->edit_site($id, $data);
                    $domain	= require CONFPATH.'domain.php';
                    $domain[$cfg['SITE_DOMAIN']] = $id;
                    if ($cfg['SITE_MOBILE']) {
                        $domain[$cfg['SITE_MOBILE']] = $id;
                    }
                    $this->dconfig->file(CONFPATH.'site/'.$id.'.php')->note('站点配置文件')->space(32)->to_require_one($this->models('site')->config, $cfg);
                    $this->dconfig->file(CONFPATH.'domain.php')->note('站点域名文件')->space(32)->to_require_one($domain);
                    $result	= 1;
                }
            }
            $data = $cfg;
            $this->models('site')->cache();
			// 删除站点首页缓存
			$this->load->helper('file');
			delete_files(DATAPATH.'index/');
            $this->system_log('配置网站站点【#'.$id.'】'.$data['name']); // 记录日志
            $page = max((int)$this->input->post('page'), 0);
		}
		
		$this->load->helper('directory');
		$files = directory_map(MTBASE.'statics/watermark/', 1);
		$opacity = array();
		foreach ($files as $t) {
			if (substr($t, -3) == 'ttf') {
				$font[] = $t;
			} else {
				$opacity[] = $t;
			}
		}

		$template_path = array();
		$template_path1 = @array_diff(dr_dir_map(FCPATH.'views/', 1), array('admin', 'member'));
		$template_path1 && $template_path = $template_path1;
		$template_path2 = dr_dir_map(VIEWPATH.'pc/web/', 1);
		$template_path2 && $template_path = ($template_path ? array_merge($template_path, $template_path2) : $template_path2);

		$this->template->assign(array(
			'id' => $id,
            'ip' => server_ip(),
            'data' => $data,
			'page' => $page,
			'lang' => dr_dir_map(FCPATH.'language/', 1),
			'theme' => dr_get_theme(),
			'result' => $result,
			'is_theme' => strpos($data['SITE_THEME'], 'http://') === 0 ? 1 : 0,
			'navigator' => @explode(',', $data['SITE_NAVIGATOR']),
			'wm_opacity' => $opacity,
			'wm_font_path' => $font,
			'template_path' => @array_unique($template_path),
			'wm_vrt_alignment' => array('top', 'middle', 'bottom'),
			'wm_hor_alignment' => array('left', 'center', 'right'),
		));
		$this->template->display('site_config.html');
    }
	
	/**
     * 删除
     */
    public function del() {
		$id = (int)$this->input->get('id');
		if (!$this->site_info[$id]) {
            $this->admin_msg(L('站点不存在，请尝试更新一次缓存'));
        } elseif ($id == 1) {
            $this->admin_msg(L('主站点不能删除'));
        }
		// 卸载模块
		$module = $this->db->get('module')->result_array();
		if ($module) {
			foreach ($module as $t) {
				$site = string2array($t['site']);
				if (isset($site[$id])) {
					$this->models('module')->uninstall($t['id'], $t['dirname'], $id, count($site));
				}
			}
		}
        // 删除相关表
		foreach (array('page', 'form', 'remote', 'block') as $table) {
            $this->db->query('DROP TABLE IF EXISTS `'.$this->db->dbprefix($id.'_'.$table).'`');
        }
		// 删除站点
		$this->db->delete('site', 'id='.$id);
        // 删除字段
		$this->db->where('relatedid', $id)->where('relatedname', 'page')->delete('field');
		// 删除该站配置
		unlink(CONFPATH.'site/'.$id.'.php');
		// 删除该站附件
		$this->models('system/attachment')->delete_for_site($id);
		// 执行的SQL
		if (is_file(WEBPATH.'cache/install/site/uninstall.sql')
			&& $sql = file_get_contents(WEBPATH.'cache/install/site/uninstall.sql')) {
			$this->sql_query(str_replace(
				array('{dbprefix}', '{siteid}'),
				array($this->db->dbprefix, $id),
				$sql
			));
		}
        $this->system_log('删除网站站点【#'.$id.'】'); // 记录日志
		$this->admin_msg(L('操作成功，更新缓存生效'), dr_url('site/index'), 1);
    }
	
	/**
     * 缓存
     */
    public function cache() {
		$this->models('site')->cache();
        (int)$_GET['admin'] or $this->admin_msg(L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 1);
	}
}