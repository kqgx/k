<?php

class Member_Login_model extends CI_Model {
    
    public $prefix; // 表头
    public $tablename; // 表

    public $pagesize = 10; // 默认分页条数
    public $cache = 0; // 查询缓存
    
    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_login';
    }
    
    public function module($dir) {
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_login';
    }

    /**
     * 前端会员验证登录
     *
     * @param	string	$username	用户名
     * @param	string	$password	明文密码
     * @param	intval	$expire	    会话生命周期
     * @param	intval	$back	    是否返回字段
     * @return	string|intval|array
     * string	登录js同步代码
     * int	-1	会员不存在
     * int	-2	密码不正确
     * int  -3	Ucenter注册失败
     * int  -4	Ucenter：会员名称不合法
     */
    public function login($username, $password, $expire, $back = 0, $uid = 0) {

        // 查询会员信息
        if ($uid) {
            $data = $this->db->where('uid', (int)$username)->get('member')->row_array();
        } else {
            $data = $this->db->where('username', $username)->get('member')->row_array();
            // 查询是否是邮箱登录或手机号码登录
            if(!$data){
                if(is_valid('phone', $username)){
                    $data = $this->db->where('phone', $username)->get('member')->row_array();
                } elseif (is_valid('email', $username)){
                    $data = $this->db->where('email', $username)->get('member')->row_array();
                }
            }
        }
        
        if (!$data) {
            return -1;
        }
        
        $username = $data['username'];
         
        $MEMBER = $this->ci->get_cache('member');
        $synlogin = '';

        // 密码验证
        $password = trim($password);
        if (md5(md5($password).$data['salt'].md5($password)) != $data['password']) {
            return -2;
        }

		$this->ci->uid = $data['uid'];
        $this->models('member/login')->add($data['uid']);

        // 返字段值，默认返回email
        if ($back) {
            return $data;
        }
        
        $expire = $expire ? $expire : 36000;
        foreach ($MEMBER['synurl'] as $url) {
            $code = dr_authcode($data['uid'].'-'.$data['salt'], 'ENCODE');
            $synlogin.= '<script type="text/javascript" src="'.$url.'/index.php?c=api&m=synlogin&expire='.$expire.'&code='.$code.'"></script>';
        }
        
        if(!IS_API){
            $this->input->set_cookie('member_uid', $data['uid'], 86400);
            $this->input->set_cookie('member_cookie', substr(md5(SYS_KEY . $data['password']), 5, 20), 86400);
        }
        
        $this->hooks->call_hook('member_login', $data); // 登录成功挂钩点
        
        return $synlogin;
    }


    /**
     * 前端会员退出登录
     *
     * @return	string
     */
    public function logout() {

        // 注销授权登陆的会员
        if ($this->session->userdata('member_auth_uid')) {
            $this->session->set_userdata('member_auth_uid', 0);
            return;
        }

        $synlogin = '';
        $MEMBER = $this->ci->get_cache('member');
        $MEMBER['setting']['ucenter'] && $synlogin.= uc_user_synlogout();

        foreach ($MEMBER['synurl'] as $url) {
            $synlogin.= '<script type="text/javascript" src="'.$url.'/index.php?c=api&m=synlogout"></script>';
        }

        return $synlogin;
    }

    // 验证会员有效性
    public function check() {

        // 授权登陆时不验证
        if ($this->uid && $this->session->userdata('member_auth_uid') == $this->uid) {
            return 1;
        }

        $cookie = get_cookie('member_cookie');
        if (!$cookie) {
            return 0;
        }

        if (substr(md5(SYS_KEY.$this->member['password']), 5, 20) !== $cookie) {
            return 0;
        }

        // 避免频繁更新online表
        if (get_cookie('member_online_time') < SYS_TIME) {
            set_cookie('member_online_time', SYS_TIME + 3600, 3600);
            $this->db->replace('member_online', array('uid' => $this->uid, 'time' => SYS_TIME));
        }
        
        return 1;
    }  
    
        /**
     * 后台管理员验证登录
     *
     * @param	string	$username	会员名称
     * @param	string	$password	明文密码
     * @return	int
     * int	id	登录成功
     * int	-1	用户不存在
     * int	-2	密码不正确
     * int	-3	您无权限登录管理平台
     * int	-4	您无权限登录该站点
     */
    public function admin($username, $password) {

        $password = trim($password);
        // 查询用户信息
        $data = $this->db
                     ->select('`password`, `salt`, `adminid`,`uid`')
                     ->where('username', $username)
                     ->limit(1)
                     ->get('member')
                     ->row_array();
        // 判断用户状态
        if (!$data) {
            return -1;
        } elseif (md5(md5($password).$data['salt'].md5($password)) != $data['password']) {
            return -2;
        } elseif ($data['adminid'] == 0) {
            return -3;
        } elseif (!$this->models('Admin')->is_admin_auth($data['adminid'])) {
            return -4; // 站点权限判断
        }

        // 管理员登录日志记录
        $this->add($data['uid'], '', 1);

        // 保存会话
        $this->session->set_userdata('uid', $data['uid']);
        $this->session->set_userdata('admin', $data['uid']);
        $this->input->set_cookie('member_uid', $data['uid'], 86400);
        $this->input->set_cookie('member_cookie', substr(md5(SYS_KEY . $data['password']), 5, 20), 86400);

        return $data['uid'];
    }
    
    public function get(){
        
    }

    public function edit(){
        
    }
    
    public function delete(){
        
    }

    /**
     * 登录记录
     *
     * @param	intval	$uid		会员uid
     * @param	string	$OAuth		快捷登录
     * @param	intval	$is_admin	是否管理员
     */
    public function add($uid, $OAuth = '', $is_admin = 0) {

        $ip = $this->input->ip_address();
        if (!$ip || !$uid) {
            return;
        }

        $agent = ($this->agent->is_mobile() ? $this->agent->mobile() : $this->agent->platform()).' '.$this->agent->browser().' '.$this->agent->version();
        if (strlen($agent) <= 5) {
            return;
        }

        $data = array(
            'uid' => $uid,
            'loginip' => $ip,
            'logintime' => SYS_TIME,
            'useragent' => substr($agent, 0, 255),
        );

        if (!$is_admin) {
            $data['oauthid'] = $OAuth;
        }

        $table = $is_admin ? 'admin_login' : 'member_login';

        // 同一天Ip一致时只更新一次更新时间
        if ($row = $this->db
                        ->select('id')
                        ->where('uid', $uid)
                        ->where('loginip', $ip)
                        ->where('DATEDIFF(from_unixtime(logintime),now())=0')
                        ->get($table)
                        ->row_array()) {
            $this->db->where('id', $row['id'])->update($table, $data);
        } else {
            $this->db->insert($table, $data);
        }

        // 会员部分只保留10条登录记录
        if (!$is_admin) {
            $row = $this->db->where('uid', $uid)->order_by('logintime desc')->get($table)->result_array();
            if (count($row) > 10) {
                $del = array();
                foreach ($row as $i => $t) {
                    $del[] = (int) $t['id'];
                    if ($i >= 9) {
                        break;
                    }
                }
                $this->db->where('uid', $uid)->where_not_in('id', $del)->delete($table);
            }
        }
    }
    
    public function authcode($uid){
        $member = $this->db->where('uid', $uid)->get('member')->row_array();
        $array = array(
                'uid' => $uid,
                'overdue' => SYS_TIME+86400,
                'token' => md5(SYS_REFERER . $member['password']),
                'sign' => md5(SYS_TIME)
            );
        $this->link->replace("{$this->tablename}_token", $array);
        
        return $array;
    }
    
    public function authcode_check($token, $sign){
        $login = $this->link->where('token', $token)->where('sign', $sign)->get("{$this->tablename}_token")->row_array();
        if($login){
            return $login['uid'];
        } 
        return 0;
    }
    
    public function error_msg($code){
        if ($code == -1) {
			$error = L('会员不存在');
		} else if ($code == -2) {
			$error = L('密码不正确');
		} else if ($code == -3) {
			$error = L('注册失败');
		} else if ($code == -4) {
			$error = L('会员名称不合法');
		} else if ($code == -5) {
			$error = L('uid同步失败');
		} else if ($code == -404) {
			$error = L('服务端网络连接失败');
		} else {
            $error = L('登录失败');
        }
        return $error;
    }
    
    public function install(){
        
    }
    
    public function uninstall(){
        
    }
}
