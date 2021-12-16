<?php

class Member_Oauth_model extends CI_Model {

    public $prefix; // 表头
    public $tablename; // 表

    public $pagesize = 10; // 默认分页条数
    public $cache = 0; // 查询缓存

    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_oauth';
    }

    public function module($dir) {
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_oauth';
    }

    /**
     * 通过OAuth登录
     *
     * @param	string	$appid	OAuth服务商名称
     * @param	array	$data	授权返回数据
     * @return	string
     */
    public function oauth($appid, $data, &$member = null) {

        // 判断OAuth是否已经注册到oauth表
        $oauth = $this->db
            ->select('id,uid')
            ->where('oid', $data['oid'])
            ->where('oauth', $appid)
            ->limit(1)
            ->get('member_oauth')
            ->row_array();
        if ($oauth) {
            // 已经注册就直接保存登录会话，更新表中的记录
            $uid = $oauth['uid'];
            $this->db->where('id', $oauth['id'])->update('member_oauth', $data);
            // 快捷登陆时挂钩点
            $this->hooks->call_hook('member_oauth_login', array('uid' => $uid, 'oauth' => $appid));
        } else {
            // 没有注册时，就直接注册会员账号
            if ($this->ci->get_cache('member', 'setting', 'regoauth')) {
                // 直接注册
                $uid = $data['uid'] = $this->models('member')->add($data, $appid);
            } else {
                // 绑定账号
                return 'bang';
            }
        }

        // 查询会员表
        $member = $this->db
            ->where('uid', $uid)
            ->select('uid,username,salt,ismobile')
            ->limit(1)
            ->get('member')
            ->row_array();
        $MEMBER = $this->ci->get_cache('member');
        $synlogin = '';

        foreach ($MEMBER['synurl'] as $url) {
            $code = dr_authcode($member['uid'].'-'.$member['salt'], 'ENCODE');
            $synlogin.= '<script type="text/javascript" src="'.$url.'/member.php?c=api&m=synlogin&expire=36000&code='.$code.'"></script>';
        }

        // $this->_login_log($member['uid'], $appid);

        return $synlogin;
    }

    /**
     * OAuth绑定当前账户
     *
     * @param	string	$appid	OAuth服务商名称
     * @param	array	$data	授权返回数据
     * @return	sting
     */
    public function bind($appid, $data) {

        // 判断OAuth是否已经注册到oauth表
        $oauth = $this->db
                      ->select('id,uid')
                      ->where('oid', $data['oid'])
                      ->where('Oauth', $appid)
                      ->limit(1)
                      ->get('member_oauth')
                      ->row_array();
        // 已经存在就直接更新表中的记录
        if ($oauth) {
            // 其他账户绑定了时返回其他账户uid
            if ($oauth['uid'] !== $this->uid) {
                return $oauth['uid'];
            }
            $this->db->where('id', $oauth['id'])->update('member_oauth', $data);
        } else {
            // 不存在时就保存OAuth数据
            $data['uid'] = $this->uid;
            $this->db->insert('member_oauth', $data);
        }

        return NULL;
    }

    public function getOAuthID($uid, $oauth) {
        return $this->db->select('oid')->where('uid', $uid)->where('Oauth', $oauth)
            ->get('member_oauth')->row()->oid;
    }

    public function get($openid, $provider){
        return $this->where('openid', $openid)->where('provider', $provider)->result();
    }

    public function edit(){

    }

    public function add(){

    }

    public function delete(){

    }

    public function install(){

    }

    public function uninstall(){

    }
}
