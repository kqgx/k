<?php

class Member_Register_model extends CI_Model {

    public $prefix; // 表头
    public $tablename; // 表

    public $pagesize = 10; // 默认分页条数
    public $cache = 0; // 查询缓存

    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_register';
    }

    public function module($dir) {
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_register';
    }

    /*
     * 注册会员 验证
     *
     * @param	array	$data	会员数据
     * @return	int
     * int	uid	注册成功
     * int	-1	会员名称已经存在
     * int	-2	Email格式有误
     * int	-3	Email已经被注册
     * int	-4	同一IP注册限制
     * int	-5	Ucenter 会员名不合法
     * int	-6	Ucenter 包含不允许注册的词语
     * int	-7	Ucenter Email 格式有误
     * int	-8	Ucenter Email 不允许注册
     * int	-9	Ucenter Email 已经被注册
     * int	-10	手机号码不正确
     * int	-11	手机号码已经被注册
     */
    public function add($data, $groupid = NULL, $uid = NULL) {
        $this->hooks->call_hook('register_before', $data); // 注册之前挂钩点

        $setting = $this->ci->get_cache('member', 'setting');
        $this->ucsynlogin = $this->synlogin = '';

        if (!IS_ADMIN && !$uid
            && $setting['regiptime']
            && $this->db->where('regip', $this->input->ip_address())->where('regtime>', SYS_TIME - 3600 * $setting['regiptime'])->count_all_results('member')) {
            return -4;
        }

        // 模式认证
        if (!IS_ADMIN) {
            if (count($setting['regfield']) == 1 && in_array('phone', $setting['regfield'])) {
                // 当只有手机号码时
                $data['email'] = '';
                $data['username'] = $data['phone'];
            } elseif (count($setting['regfield']) == 1 && in_array('username', $setting['regfield'])) {
                $data['phone'] = '';
                $data['email'] = '';
            } elseif (count($setting['regfield']) == 1 && in_array('email', $setting['regfield'])) {
                $data['phone'] = '';
                $data['username'] = $data['email'];
            }
        }

        !$data['username'] && $data['phone'] && $data['username'] = $data['phone'];
        !$data['username'] && $data['email'] && $data['username'] = $data['email'];

        // 验证邮箱
        if (@in_array('email', $setting['regfield'])) {
            if (!$data['email'] || !is_valid('email', $data['email'])) {
                return -2;
            } elseif ($this->db->where('email', $data['email'])->count_all_results('member')) {
                return -3;
            }
        }
        // 验证手机
        if (@in_array('phone', $setting['regfield'])) {
            if (!is_valid('phone', $data['phone'])) {
                return -10;
            } elseif ($this->db->where('phone', $data['phone'])->count_all_results('member')) {
                return -11;
            }
        }

        // 验证账号
        if ($this->db->where('username', $data['username'])->count_all_results('member')) {
            return -1;
        }

        $this->hooks->call_hook('register_after', $data);

        return $this->models('member')->add($data, NULL, $groupid, $uid);
    }


    public function error_msg($code){
        if ($code == -1) {
			$error = L('该会员【%s】已经被注册', $data['username']);
		} elseif ($code == -2) {
			$error = L('邮箱格式不正确');
		} elseif ($code == -3) {
			$error = L('该邮箱【%s】已经被注册', $data['email']);
		} elseif ($code == -4) {
			$error = L('同一IP在限制时间内注册过多');
		} elseif ($code == -5) {
			$error = L('Ucenter：会员名称不合法');
		} elseif ($code == -6) {
			$error = L('Ucenter：包含不允许注册的词语');
		} elseif ($code == -7) {
			$error = L('Ucenter：Email格式有误');
		} elseif ($code == -8) {
			$error = L('Ucenter：Email不允许注册');
		} elseif ($code == -9) {
			$error = L('Ucenter：Email已经被注册');
		} elseif ($code == -10) {
			$error = L('手机号码必须是11位的整数');
		} elseif ($code == -11) {
            $error = L('该手机号码已经注册');
        } else {
			$error = L('注册失败');
		}
        return $error;
    }

    public function install(){

    }

    public function uninstall(){

    }
}
