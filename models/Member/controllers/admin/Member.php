<?php

class Member extends M_Controller {

    private $userinfo;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->template->assign('menu', $this->get_menu_v3(array(
			L('会员管理') => array('member/member/index', 'user'),
			L('添加') => array('member/member/add_js', 'plus')
		)));
    }

    /**
     * 首页
     */
    public function index() {

		if (IS_POST && $this->input->post('action')) {

            // ID格式判断
			$ids = $this->input->post('ids');
            !$ids && $this->msg(0, L('您还没有选择呢'));
			
			if ($this->input->post('action') == 'del') {
                // 删除
                !$this->is_auth('admin/member/del') && $this->msg(0, L('您无权限操作'));
                foreach ($ids as $i) {
                    // 角色权限验证
                    $data = $this->models('member')->get_admin_member($i);
                    !$this->models('admin/auth')->role_level($this->member['adminid'], $data['adminid']) && $this->msg(0, L('您无权操作（ta的权限高于你）'));
                }
				$this->models('member')->delete($ids);
                defined('UCSSO_API') && ucsso_delete($ids);
                $this->system_log('删除会员【#'.@implode(',', $ids).'】'); // 记录日志
				$this->msg(1, L('操作成功，正在刷新...'));
			} else {
                // 修改会员组
                !$this->is_auth('admin/member/edit') && $this->msg(0, L('您无权限操作'));
				$gid = (int)$this->input->post('groupid');
				$note = L('您的会员组由管理员%s改变成：%s', $this->member['username'], $this->get_cache('member', 'group', $gid, 'name'));
				$this->db->where_in('uid', $ids)->update('member', array('groupid' => $gid));
				$this->models('member/notice')->add($ids, 1, $note);
                foreach ($ids as $uid) {
                    // 会员组升级挂钩点
                    $this->hooks->call_hook('member_group_upgrade', array('uid' => $uid, 'groupid' => $gid));
                    // 表示审核会员 debug
                    // $this->models('member')->update_admin_notice('admin/member/index/field/uid/keyword/'.$uid, 3);
                }
                $this->system_log('修改会员【#'.@implode(',', $ids).'】的会员组'); // 记录日志
				$this->msg(1, L('操作成功，正在刷新...'));
			}
		}

        // 重置页数和统计
        IS_POST && $_GET['page'] = $_GET['total'] = 0;
	
		// 根据参数筛选结果
        $param = $this->input->get(NULL, TRUE);
        unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);
		// 数据库中分页查询
		list($data, $param) = $this->models('member')->limit_page($param, max((int)$_GET['page'], 1), (int)$_GET['total']);


        $field = $this->get_cache('member', 'field');
        $field = array(
            'username' => array('fieldname' => 'username','name' => L('会员名称')),
            'name' => array('fieldname' => 'name','name' => L('姓名')),
            'email' => array('fieldname' => 'email','name' => L('会员邮箱')),
            'phone' => array('fieldname' => 'phone','name' => L('手机号码')),
            'ismobile' => array('fieldname' => 'ismobile','name' => L('是否手机认证')),
            'complete' => array('fieldname' => 'complete','name' => L('是否完善资料')),
            'is_auth' => array('fieldname' => 'is_auth','name' => L('是否实名认证')),
        ) + ($field ? $field : array());

        // 存储当前页URL
        $this->_set_back_url('member/index', $param);

		$this->template->assign(array(
			'list' => $data,
            'field' => $field,
			'param'	=> $param,
			'pages'	=> $this->get_pagination(dr_url('admin/member/member/index', $param), $param['total']),
		));
		$this->template->display();
    }
	
	/**
     * 添加
     */
    public function add() {

        $MEMBER = $this->get_cache('member');
        if ($MEMBER['setting']['ucenter'] && is_file(WEBPATH.'api/ucenter/config.inc.php')) {
            include WEBPATH.'api/ucenter/config.inc.php';
            include WEBPATH.'api/ucenter/uc_client/client.php';
        }
		if (IS_POST) {
			$all = $this->input->post('all');
			$info = $this->input->post('info');
			$data = $this->input->post('data');
            !$data['groupid'] && $this->msg(0, L('先选择一个会员组吧'), 'groupid');
			if ($all) {
				// 批量添加
                !$info && $this->msg(0, L('批量注册信息填写不完整'), 'info');
				$data = explode(PHP_EOL, $info);
				$success = $error = 0;
				foreach ($data as $t) {
					list($username, $password, $email, $phone) = explode('|', $t);
					if ($username || $password || $email || $phone) {
						$uid = $this->models('member/register')->add(array(
                            'phone' => $phone,
                            'email' => $email,
							'username' => $username,
							'password' => trim($password),
						), $data['groupid']);
						if ($uid > 0) {
							$success ++;
                            $this->system_log('添加会员【#'.$uid.'】'.$username); // 记录日志
						} else {
							$error ++;
						}
					}
				}
				$this->msg(1, L('批量注册成功%s，失败%s', $success, $error));
			} else {
				// 单个添加
                $uid = $this->models('member/register')->add(array(
                    'email' => $data['email'],
                    'phone' => $data['phone'] ? $data['phone'] : '',
                    'username' => $data['username'],
                    'password' => trim($data['password']),
                ), $data['groupid']);
                if ($uid == -1) {
                    $this->msg(0, L('该会员【%s】已经被注册', $data['username']), 'username');
                } elseif ($uid == -2) {
                    $this->msg(0, L('邮箱格式不正确'), 'email');
                } elseif ($uid == -3) {
                    $this->msg(0, L('该邮箱【%s】已经被注册', $data['email']), 'email');
                } elseif ($uid == -4) {
                    $this->msg(0, L('同一IP在限制时间内注册过多'), 'username');
                } elseif ($uid == -5) {
                    $this->msg(0, L('Ucenter：会员名称不合法'), 'username');
                } elseif ($uid == -6) {
                    $this->msg(0, L('Ucenter：包含不允许注册的词语'), 'username');
                } elseif ($uid == -7) {
                    $this->msg(0, L('Ucenter：Email格式有误'), 'username');
                } elseif ($uid == -8) {
                    $this->msg(0, L('Ucenter：Email不允许注册'), 'username');
                } elseif ($uid == -9) {
                    $this->msg(0, L('Ucenter：Email已经被注册'), 'username');
                } elseif ($uid == -10) {
                    $this->msg(0, L('手机号码必须是11位的整数'), 'phone');
                } elseif ($uid == -11) {
                    $this->msg(0, L('该手机号码已经注册'), 'phone');
                } else {
                    $this->system_log('添加会员【#'.$uid.'】'.$data['username']); // 记录日志
                    $this->msg(1, L('操作成功，正在刷新...'));
                }
			}
		}
		$this->template->display();
    }
	
	/**
     * 修改
     */
    public function edit() {
	
		$uid = (int)$this->input->get('uid');
		$page = (int)$this->input->get('page');
		$data = $this->models('member')->get_member($uid);
        !$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
        // 角色权限验证
        !$this->models('admin/auth')->role_level($this->member['adminid'], $data['adminid']) && $this->admin_msg(L('您无权操作（ta的权限高于你）'));

		$field = array();
		$MEMBER = $this->get_cache('member');
        if ($MEMBER['setting']['ucenter'] && is_file(WEBPATH.'api/ucenter/config.inc.php')) {
            include WEBPATH.'api/ucenter/config.inc.php';
            include WEBPATH.'api/ucenter/uc_client/client.php';
        }

		if ($MEMBER['field'] && $MEMBER['group'][$data['groupid']]['allowfield']) {
			foreach ($MEMBER['field'] as $t) {
                in_array($t['fieldname'], $MEMBER['group'][$data['groupid']]['allowfield']) && $field[] = $t;
			}
		}

		$is_uc = function_exists('uc_user_edit') && $MEMBER['setting']['ucenter'];
		
		if (IS_POST) {
			$edit = $this->input->post('member');
			$page = (int)$this->input->post('page');
			$post = $this->validate_filter($field, $data);
			if (!$edit['groupid']) {
				$error = L('先选择一个会员组吧');
			} elseif (isset($post['error'])) {
				$error = $post['msg'];
			} else {
				$post[1]['uid'] = $uid;
				$post[1]['is_auth'] = (int)$data['is_auth'];
				$post[1]['complete'] = (int)$data['complete'];
				$this->db->replace('member_data', $post[1]);
				$this->attachment_handle($uid, $this->db->dbprefix('member').'-'.$uid, $field, $data);
				$update = array(
					'name' => $edit['name'],
                    'sex' => $edit['sex'],
					'phone' => $edit['phone'],
                    'address' => $edit['address'],
					'groupid' => $edit['groupid'],
				);
                if (!empty($post[1]['dealer_name'])) {
                    $update['username'] = $post[1]['dealer_name'];
                }
                if ( $MEMBER['setting']['ismobile']) {
                    $update['ismobile'] = intval($_POST['ismobile']);
                }
                // 修改密码
                $edit['password'] = trim($edit['password']);
				if ($edit['password']) {
                    if (defined('UCSSO_API')) {
                        $rt = ucsso_edit_password($uid, $edit['password']);
                        // 修改失败
                        if (!$rt['code']) {
                            $this->admin_msg(L($rt['msg']));
                        }
                    }
                    $is_uc && uc_user_edit($data['username'], '', $edit['password'], '', 1);
					$update['password'] = md5(md5($edit['password']).$data['salt'].md5($edit['password']));
                    $this->hooks->call_hook('member_edit_password', array('member' => $data, 'password' => $edit['password']));
					$this->models('member/notice')->add($uid, 1, L('您的密码被管理员%s修改了', $this->member['username']));
                    $this->system_log('修改会员【'.$data['username'].'】密码'); // 记录日志
				}
                // 修改邮箱
                if ($edit['email'] != $data['email']) {
                    !preg_match('/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/', $edit['email']) && $this->admin_msg(L('邮箱格式不正确'));
                    $this->db->where('email', $edit['email'])->where('uid<>', $uid)->count_all_results('member') && $this->admin_msg(L('该邮箱【%s】已经被注册', $edit['email']));
                    if ($is_uc) {
                        $ucid = uc_user_edit($data['username'], '', '', $edit['email'], 1);
                        if ($ucid == -4) {
                            $this->admin_msg(L('Ucenter：Email格式有误'));
                        } elseif ($ucid == -5) {
                            $this->admin_msg(L('Ucenter：Email不允许注册'));
                        } elseif ($ucid == -6) {
                            $this->admin_msg(L('Ucenter：Email已经被注册'));
                        }
                    }
                    if (defined('UCSSO_API')) {
                        $rt = ucsso_edit_email($uid, $edit['email']);
                        // 修改失败
                        if (!$rt['code']) {
                            $this->admin_msg(L($rt['msg']));
                        }
                    }
                    $update['email'] = $edit['email'];
                    $this->models('member/notice')->add($uid, 1, L('您的注册邮箱被管理员%s修改了', $this->member['username']));
                    $this->system_log('修改会员【'.$data['username'].'】邮箱'); // 记录日志
                }
                // 修改手机
                if  ($edit['phone'] != $data['phone']) {
                    if (defined('UCSSO_API')) {
                        $rt = ucsso_edit_phone($uid, $edit['phone']);
                        // 修改失败
                        if (!$rt['code']) {
                            $this->admin_msg(L($rt['msg']));
                        }
                    }
                }
				$this->db->where('uid', $uid)->update('member', $update);
                // 会员组升级挂钩点
                if ($data['groupid'] != $edit['groupid']) {
                    // 表示审核会员
                    $data['groupid'] == 1 && $this->models('member')->update_admin_notice('admin/member/index/field/uid/keyword/'.$uid, 3);
                    $this->hooks->call_hook('member_group_upgrade', array('uid' => $uid, 'groupid' => $edit['groupid']));
                    $this->system_log('修改会员【'.$data['username'].'】会员组'); // 记录日志
                }
                $this->system_log('修改会员【'.$data['username'].'】资料'); // 记录日志
				$this->admin_msg(L('操作成功，正在刷新...'), dr_url('member/edit', array('uid' => $uid, 'page' => $page)), 1);
			}
			$this->admin_msg($error, dr_url('member/edit', array('uid' => $uid, 'page' => $page)));
		}
		
		$this->template->assign(array(
			'data' => $data,
			'page' => $page,
			'ismobile' => $MEMBER['setting']['ismobile'],
			'myfield' => $this->field_input($field, $data, TRUE),
		));
		$this->template->display();
    }

    public function ajax_email() {
        $uid = (int)$this->input->get('uid');
        $email = $this->input->get('email');
        if (!$email || !preg_match('/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/', $email)) {
            exit(L('邮箱格式不正确'));
        } elseif ($this->db->where('email', $email)->where('uid<>', $uid)->count_all_results('member')) {
            exit(L('该邮箱【%s】已经被注册', $email));
        }
    }
    
    /**
     * 配置
     */
    public function setting() {

		$page = (int)$this->input->get('page');
		$result = 0;

		if (IS_POST) {
			$post = $this->input->post('data', true);
			$page = (int)$this->input->post('page');
			// 规则判断
			if (empty($post['regfield'])) {
				$this->admin_msg('至少需要选择一个注册字段，否则注册系统会崩溃', dr_url('member_setting/index', array('page'=> $page)), 0, 9);
			} elseif (!in_array('email', $post['regfield']) && $post['regverify'] == 1) {
				$this->admin_msg('开启邮件审核后，注册字段必须选择【邮箱】，否则注册系统会崩溃', dr_url('member_setting/index', array('page'=> $page)), 0, 9);
			} elseif (!in_array('email', $post['regfield']) && $post['ucenter'] == 1) {
				$this->admin_msg('开启Ucenter后，注册字段必须选择【邮箱】，否则注册系统会崩溃', dr_url('member_setting/index', array('page'=> $page)), 0, 9);
			} elseif (!in_array('phone', $post['regfield']) && $post['regverify'] == 3) {
				$this->admin_msg('开启手机验证码审核后，注册字段必须选择【手机】，否则注册系统会崩溃', dr_url('member_setting/index', array('page'=> $page)), 0, 9);
			}
			$this->models('member')->member($post);
			$cache = $this->models('member')->cache();
            $data = $cache['setting'];
            $result = 1;
            $this->system_log('会员配置'); // 记录日志
		} else {
			$cache = $this->models('member')->cache();
			$data = $cache['setting'];
        }

		$html = '

					</div>
				</div>
<div class="portlet light bordered" id="dr_{name}">
					<div class="portlet-title mytitle">
						{text}
					</div>
<div class="portlet-body">
{value}';
		!$data['mergefield'] && $data['mergefield'] = $html;
		!$data['mbmergefield'] && $data['mbmergefield'] = $html;

		$this->template->assign(array(
			'menu' => $this->get_menu_v3(array(
				L('功能配置') => array('admin/member/setting', 'cog')
			)),
			'data' => $data,
			'page' => $page,
			'result' => $result,
            'synurl' => $cache['synurl'],
		));
		$this->template->display();
    }
    
	/**
     * 会员权限划分
     */
	public function permission() {
		$this->template->assign(array(
			'menu' => $this->get_menu_v3(array(
				L('会员权限') => array('admin/member/permission', 'users'),
			))
		));
		$this->template->display();
	}
	
	/**
     * 会员设置规则
     */
    public function rule() {
		$id = $this->input->get('id');
		if (IS_POST) {
			$this->models('member')->permission($id, $this->input->post('data'));
			$this->models('member')->cache();
            $this->system_log('会员权限设置'); // 记录日志
			exit;
		}
		$this->template->assign(array(
			'data' => $this->models('member')->permission($id),
		));
		$this->template->display();
    }

    public function distributor()
    {
        $param['groupid'] = 7;
        if ($uid = $this->input->get('uid', TRUE)) {
            $user = dr_member_info($uid);
            switch ($action = $this->input->get('action')) {
                case 'first':
                    $param['invitation_code'] = $user['randcode'];
                    $param['action'] = $action;
                    $m = [
                        'name' => L('%s一级下线', $user['username']),
                        'url' => array('admin/member/distributor/uid/'.$uid.'/action/'.$action, 'cube')
                    ];
                    break;
                case 'second':
                    $param['invitation_code'] = $user['randcode'];
                    $param['action'] = $action;
                    $m = [
                        'name' => L('%s二级下线', $user['username']),
                        'url' => array('admin/member/distributor/uid/'.$uid.'/action/'.$action, 'cubes')
                    ];
                    break;
                case 'dis':
                    $param['uid'] = $uid;
                    $param['action'] = $action;
                    $m = [
                        'name' => L('%s分销数据', $user['username']),
                        'url' => array('admin/member/distributor/uid/'.$uid.'/action/'.$action, 'share-alt')
                    ];
                    $count_data = array(
                        //全部完成
                        'all_over' => $this->models('member')->get_all_over($uid) ?: 0,
                        //本月完成
                        'month_over' => $this->models('member')->get_month_over($uid) ?: 0,
                        //今日完成
                        'today_over' => $this->models('member')->get_today_over($uid) ?: 0,
                    );
                    break;
                case 'fin':
                    $param['uid'] = $uid;
                    $param['action'] = $action;
                    $m = [
                        'name' => L('%s财务流水', $user['username']),
                        'url' => array('admin/member/distributor/uid/'.$uid.'/action/'.$action, 'yen')
                    ];
                    $count_data = array(
                        // 今日收益
                        'today_income' => $this->models('index')->today_income($uid)['value'] ?: '0.00',
                        // 我的收益
                        'cumulative_income' => $this->models('index')->cumulative_income($uid)['value'] ?: '0.00',
                        // 本月收益
                        'month_income' => $this->models('member')->get_month_income($uid)['value'] ?: '0.00',
                    );
                    break;
            }
        }
		list($data, $param) = $this->models('member')->limit_page($param, max((int)$_GET['page'], 1), (int)$_GET['total']);
        $month = get_month();
        foreach ($data as $k => $v) {
            $data[$k]['month_hits']	= $this->db->where(['invitation_code' => $v['randcode'], 'bindtime >=' => $month['month_start'], 'bindtime <=' => $month['month_end']])
                                               ->count_all_results('member');
        }
        $menu = array();
        if (!empty($uid)) {
            $menu[L('返回')] = array('javascript:history.back();', 'reply');
        }
        $menu[L('分销商户')] = array('member/distributor', 'list');
        isset($m) && $menu[$m['name']] = $m['url'];
        $this->template->assign(array(
            'menu' => $this->get_menu_v3($menu),
			'list' => $data,
			'param'	=> $param,
			'pages'	=> $this->get_pagination(dr_url('admin/member/member/distributor', $param), $param['total']),
            'count_data' => $count_data ?: NULL
		));
		$this->template->display();
    }
}