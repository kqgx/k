<?php
	
class Attachment extends M_Controller {

	private $cache_file;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$menu = array(
			L('附件管理') => array('admin/attachment/index', 'folder'),
            L('未使用的附件') => array('admin/attachment/unused', 'folder-o')
		);
		$this->template->assign('menu', $this->get_menu_v3($menu));
		$this->cache_file = md5('admin/attachment'.$this->uid.SITE_ID.$this->input->ip_address().$this->input->user_agent()); // 缓存文件名称
    }
	
	/**
     * 搜索
     */
    public function index() {
		
		$error = 0;
		
		if (IS_POST) {
			$data = $this->input->post('data');
			$data['id'] = (int)$data['id'];
			if (!$data['name'] && !$data['id'] && !$data['author'] && !$data['ext']) {
				$error = L('必须填写其中一项搜索条件');
			} elseif (!$data['name'] && $data['id']) {
				$error = L('表主键必须与表名称配合搜索');
			} else {
				$where = array();
				if ($data['name'] && $data['id']) {
					$where[] = '`related`="'.$data['name'].'-'.$data['id'].'"';
				} elseif ($data['name']) {
					$where[] = '`related` LIKE "'.$data['name'].'-%"';
				} else {
					$where[] = '`related` <> ""';
				}
				if ($data['author']) {
					$uid = get_member_id($data['author']);
					if ($uid) {
						$where[] = '`uid`='.$uid;
					} else {
						$error = L('会员不存在');
						$where = NULL;
					}
				}
				if (!$error && $data['ext']) {
					$ext = explode(',', $data['ext']);
					$_ext = array();
					foreach ($ext as $t) {
						$_ext[] = '`fileext`="'.$t.'"';
					}
					$where[] = '('.implode(' OR ', $_ext).')';
				}
				if ($where) {
					$where = implode(' AND ', $where);
					$attach = $this->db->select('id')->where($where)->get('attachment')->result_array();
					if ($attach) {
						$cache = array();
						foreach ($attach as $t) {
							$cache[] = (int)$t['id'];
						}
						$this->cache->file->save($this->cache_file, $cache, 7200);
						$this->admin_msg(L('正在搜索中，请稍后...'), dr_url('attachment/result'), 2, 3);
					}
				}
				$error = L('没有搜索到相关附件，请检查搜索条件');
			}
			$data['id'] = $data['id'] ? $data['id'] : '';
		} else {
		    $data['author'] = $this->admin['username'];
        }
		
		$this->template->assign(array(
			'data' => $data,
			'error' => $error,
		));
		$this->template->display('attachment_index.html');
    }
	
	/**
     * 搜索结果
     */
	public function result() {
	
		if ($this->input->post('ids')) {
			$ids = $this->input->post('ids');
			$data = $this->db->where_in('id', $ids)->get('attachment')->result_array();
			if ($data) {
				foreach ($data as $t) {
					$this->db->delete('attachment', 'id='.$t['id']);
					$this->db->delete('attachment_unused', 'id='.$t['id']);
					$this->models('system/attachment')->_delete_attachment($t);
                    $this->system_log('删除附件【#'.$t['id'].'】'); // 记录日志
				}
			}
			$this->msg(1, L('操作成功，正在刷新...'));
		}
	
		$cache = $this->cache->file->get($this->cache_file);
		$total = count($cache);
		if (!$total) {
            $this->admin_msg(L('搜索缓存已过期，请重新搜索'));
        }
		
		$page = max((int)$this->input->get('page'), 1);
		$data = $this->db
					 ->select('id,tableid')
					 ->where_in('id', $cache)
                     ->order_by('id desc')
					 ->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1))
					 ->get('attachment')
					 ->result_array();
		foreach ($data as $i => $t) {
			$data[$i] = $this->db->where('id', (int)$t['id'])->get('attachment_use')->row_array();
		}

		$this->template->assign('menu', $this->get_menu_v3(array(
			L('搜索') => array('admin/attachment/index', 'search'),
			L('附件管理') => array('admin/attachment/result', 'folder'),
			L('未使用的附件') => array('admin/attachment/unused', 'folder-o')
		)));
		$this->template->assign(array(
			'list' => $data,
			'pages'	=> $this->get_pagination(dr_url(APP_DIR.'/attachment/result'), $total),
            'totals' => $total,
		));
		$this->template->display('attachment_result.html');
	}
	
	/**
     * 未使用的附件
     */
    public function unused() {
		
		if ($this->input->post('ids')) {
			$ids = $this->input->post('ids');
			$data = $this->db->where_in('id', $ids)->get('attachment_unused')->result_array();
			if ($data) {
				foreach ($data as $t) {
				    if ($this->input->post('action') == 'order') {
                        // 归档附表id
                        $id = (int)$t['id'];
                        // 更新主索引表
                        $this->db->where('id', $id)->update('attachment', array(
                            'uid' => $t['uid'],
                            'author' => $t['author'],
                            'related' => 'ok'
                        ));
                        // 更新至附表
                        $this->db->replace('attachment_use', array(
                            'id' => $t['id'],
                            'uid' => $t['uid'],
                            'remote' => $t['remote'],
                            'author' => $t['author'],
                            'related' => 'ok',
                            'fileext' => $t['fileext'],
                            'filesize' => $t['filesize'],
                            'filename' => $t['filename'],
                            'inputtime' => $t['inputtime'],
                            'attachment' => $t['attachment'],
                            'attachinfo' => $t['attachinfo'],
                        ));
                        // 删除未使用附件
                        $this->db->delete('attachment_unused', 'id='.$id);
                        $this->system_log('归档附件【#'.$t['id'].'】'); // 记录日志
                    } else {
                        $this->db->delete($this->db->dbprefix('attachment'), 'id='.$t['id']);
                        $this->db->delete($this->db->dbprefix('attachment_unused'), 'id='.$t['id']);
                        $this->models('system/attachment')->_delete_attachment($t);
                        $this->system_log('删除附件【#'.$t['id'].'】'); // 记录日志
                    }
				}
			}
			$this->msg(1, L('操作成功，正在刷新...'));
		}
		
		$page = max((int)$this->input->get('page'), 1);
		$where = '`siteid`='.SITE_ID;
		$total = (int)$this->input->get('total');
		$param = array();
		
		if ($this->input->post('author')) {
			$param['author'] = $this->input->post('author', TRUE);
			$where.= ' AND `author`="'.$param['author'].'"';
			$total = 0;
		} elseif ($this->input->get('author')) {
			$param['author'] = $this->input->get('author', TRUE);
			$where.= ' AND `author`="'.$param['author'].'"';
		}
		
		$param['total'] = $total ? $total : $this->db->where($where)->count_all_results($this->db->dbprefix('attachment_unused'));
		
		$data = $this->db
					 ->where($where)
					 ->order_by('inputtime DESC')
					 ->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1))
					 ->get($this->db->dbprefix('attachment_unused'))
					 ->result_array();
		
		$this->template->assign(array(
			'list' => $data,
			'param'	=> $param,
			'pages'	=> $this->get_pagination(dr_url(APP_DIR.'/attachment/unused', $param), $param['total'])
		));
		$this->template->display('attachment_unused.html');
    }

    // 生成附件缓存
	public function cache() {

        $page = (int)$this->input->get('page');
        if (!$page) {
            if (!SYS_AUTO_CACHE) {
                $this->admin_msg(L('系统未开启缓存'));
            } elseif (!SYS_CACHE_ATTACH) {
                $this->admin_msg(L('未设置附件缓存时间'));
            }
            $query = $this->db;
            // 统计数量
            $total = $query->count_all_results('attachment');
            if ($total) {
                $this->admin_msg(L('可生成附件%s个，正在准备执行...', $total), dr_url('attachment/cache', array('total' => $total, 'page'=>1)), 2);
            } else {
                $this->admin_msg(L('没有找到可生成的附件'));
            }
        }

        $psize = 50; // 每页处理的数量
        $table = 'attachment';
        $total = (int)$this->input->get('total');
        $tpage = ceil($total / $psize); // 总页数
        if ($page > $tpage) {
            // 更新完
            $this->admin_msg(L('已更新%s个附件', $total), NULL, 1);
        }
        $data = $this->db->limit($psize, $psize * ($page - 1))->order_by('id DESC')->get($table)->result_array();
        foreach ($data as $t) {
            get_attachment($t['id']);
        }
        $this->admin_msg(L('正在执行中(%s) ... ', "$tpage/$page"), dr_url('attachment/cache', array('total' => $total, 'page' => $page + 1)), 2, 0);

    }
}