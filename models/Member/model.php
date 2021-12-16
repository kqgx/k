<?php

class Member_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 会员修改信息
     *
     * @param    array $main 主表字段
     * @param    array $data 附加表字段
     * @return    void
     */
    public function edit($main, $data)
    {
        if (isset($main['check']) && $main['check']) {
            $main['ismobile'] = 1;
            $main['randcode'] = '';
            unset($main['check']);
        }

        if (isset($main['check'])) {
            unset($main['check']);
        }

        $this->db->where('uid', $this->uid)->update('member', $main);

        $data['uid'] = $this->uid;
        $data['complete'] = 1;

        // 屏蔽数据库错误
        $this->db->db_debug = false;
        $data['is_auth'] = dr_is_app('auth') && $this->db->where('uid', $this->uid)->where('status', 3)->count_all_results('member_auth') ? 1 : 0;
        $this->db->db_debug = true;

        $this->db->replace('member_data', $data);

        return TRUE;
    }

    /**
     * 会员基本信息
     *
     * @param    intval|string $key
     * @param    intval $type 0按id，1按会员名
     * @return    array
     */
    public function get_base_member($key, $type = 0)
    {

        if (!$key) {
            return NULL;
        }

        $type ? $this->db->where('username', $key) : $this->db->where('uid', (int)$key);

        $data = $this->db
            ->limit(1)
            ->select('uid,username,email,levelid,groupid,score,experience')
            ->get('member')
            ->row_array();
        if (!$data) {
            return NULL;
        }

        $data['markrule'] = $data['groupid'] < 3 ? $data['groupid'] : ($data['groupid'] . '_' . $data['levelid']);

        return $data;
    }

    /**
     * 会员权限标识
     *
     * @param    intval    uid
     * @return    string
     */
    public function get_markrule($uid)
    {

        if (!$uid) {
            return 0;
        }

        $data = $this->db->select('groupid,levelid')->where('uid', (int)$uid)->limit(1)->get('member')->row_array();
        if (!$data) {
            return 0;
        }

        return $data['groupid'] < 3 ? $data['groupid'] : ($data['groupid'] . '_' . $data['levelid']);
    }

    /**
     * 根据手机号获取用户信息
     * @param $phone
     * @return array
     */
    public function getByPhone($phone)
    {
        return $this->db->where('phone', $phone)->get('member')->row_array();
    }

    /**
     * 会员信息
     *
     * @param    intval    uid
     * @return    array
     */
    public function get_member($uid)
    {

        $uid = intval($uid);
        if (!$uid) {
            return NULL;
        }

        // 查询会员信息
        $db = $this->db
            ->from($this->db->dbprefix('member') . ' AS m2')
            ->join($this->db->dbprefix('member_data') . ' AS a', 'a.uid=m2.uid', 'left')
            ->where('m2.uid', $uid)
            ->limit(1)
            ->get();
        if (!$db) {
            return NULL;
        }
        $data = $db->row_array();
        if (!$data) {
            return NULL;
        }

        $group = $this->ci->get_cache('member', 'group');
        $data['uid'] = $uid;
        $data['tableid'] = (int)substr((string)$uid, -1, 1);
        $data['groupname'] = $group[$data['groupid']]['name'];
        $data['levelname'] = $group[$data['groupid']]['level'][$data['levelid']]['name'];
        $data['avatar_url'] = '';
        
        foreach (array('png', 'jpg', 'gif', 'jpeg') as $ext) {
            if (is_file(SYS_UPLOAD_PATH . '/member/' . $uid . '/180x180.' . $ext)) {
                $data['avatar_url'] = SYS_ATTACHMENT_URL . 'member/' . $uid . '/180x180.' . $ext;
                break;
            }
        }
        $data['avatar_url'] = $data['avatar_url'] ? $data['avatar_url'] : THEME_PATH . 'avatar/default.png';
        
        $data['levelstars'] = $group[$data['groupid']]['level'][$data['levelid']]['stars'];

        // 快捷登陆用户信息提取
        $data['bang'] = 0;
        $oauth = require CONFPATH.'oauth.php';
        if ($oauth) {
            $bang = 0;
            // 判断是否有可用的快捷登陆配置
            foreach ($oauth as $n => $t) {
                if ($t['use']) {
                    $bang = 1;
                    break;
                }
            }
            // 当存在快捷登陆时才查询绑定表，减少一次查询次数
            if ($bang) {
                $oauth2 = $this->db->where('uid', $uid)->order_by('expire_at desc')->get('member_oauth')->result_array();
                if ($oauth2) {
                    foreach ($oauth2 as $i => $t) {
                        $t['nickname'] = dr_weixin_emoji($t['nickname']);
                        if (!$data['username']) {
                            $data['bang'] = 1;
                            $data['username'] = $t['nickname'];
                        }
                        $data['Oauth'][$t['Oauth']] = $t;
                    }
                }
            }
        }

        // 会员组过期判断
        if (!$data['groupid']
            || ($data['overdue'] && $group[$data['groupid']]['price'] && $data['overdue'] < SYS_TIME)) {
            if ($group[$data['groupid']]['unit'] == 1
                && $data['score'] - abs(intval($group[$data['groupid']]['price'])) > 0) {
                // 虚拟币自动扣费
                $this->models('member/score')->edit(1, $uid, -abs(intval($group[$data['groupid']]['price'])), '', L('会员组到期自动扣费'));
                $time = $this->upgrade($uid, $data['groupid'], $group[$data['groupid']]['limit'], $data['overdue']);
                $time = $time > 2000000000 ? L('永久') : dr_date($time);
                // 邮件提醒
                $this->models('system/email')->queue(
                    $this->member['email'],
                    L('会员组续费成功'),
                    L(@file_get_contents(WEBPATH . 'cache/email/xufei.html'), $data['name'] ? $data['name'] : $data['username'], $group[$data['groupid']]['name'], $time)
                );
                $this->models('member/notice')->add($uid, 1, L('会员组续费成功'));
            } else {
                // 转为过期的后的会员组
                $data['groupid'] = intval($data['group']['overdue'] ? $data['group']['overdue'] : 3);
                $this->db->where('uid', $uid)->update('member', array(
                    'levelid' => 0,
                    'overdue' => 0,
                    'groupid' => $data['groupid'],
                ));
                $data['groupname'] = $group[$data['groupid']]['name'];
                $this->models('member/notice')->add($uid, 1, L('很遗憾，您的会员组已经过期，被自动初始化'));
            }
        }

        // 会员组等级升级
        if ($group[$data['groupid']]['level']) {
            $level = array_reverse($group[$data['groupid']]['level']); // 倒序判断
            foreach ($level as $t) {
                if ($data['experience'] >= $t['experience']) {
                    if ($data['levelid'] != $t['id']) {
                        $data['levelid'] = $t['id'];
                        $data['levelname'] = $group[$data['groupid']]['level'][$data['levelid']]['name'];
                        $data['levelstars'] = $group[$data['groupid']]['level'][$data['levelid']]['stars'];
                        $this->db->where('uid', $uid)->update('member', array('levelid' => $t['id']));
                        /* 挂钩点：会员组等级升级 */
                        $this->hooks->call_hook('member_level_upgrade', array('uid' => $uid, 'groupid' => $data['groupid'], 'level' => $t));
                        $this->models('member/notice')->add($uid, 1, L('您的会员组等级升级成功'));
                    }
                    break;
                }
            }
        }

        $data['mark'] = $data['groupid'] < 3 ? $data['groupid'] : ($data['groupid'] . '_' . $data['levelid']);

        return $data;
    }

    /**
     * 通过会员id取会员名称
     *
     * @param    intval $uid
     * @return  string
     */
    function get_username($uid)
    {

        if (!$uid) {
            return NULL;
        }

        $data = $this->db->select('username')->where('uid', (int)$uid)->limit(1)->get('member')->row_array();

        return $data['username'];
    }

    /**
     * 会员组续费/升级
     *
     * @param    intval $uid 会员uid
     * @param    intval $groupid 组id
     * @param    intval $limit limit值
     * @param    intval $time 当前过期时间，为0时表示新开
     * @return    intval
     */
    public function upgrade($uid, $groupid, $limit, $time = 0, $num = 1)
    {

        if (!$uid || !$groupid || !$limit) {
            return FALSE;
        }

        $time = max($time, SYS_TIME);

        // 得到增加的时间戳
        switch ($limit) {

            case 1: // 月
                $time = strtotime('+1 month', $time);
                break;

            case 2: // 半年
                $time = strtotime('+6 month', $time);
                break;

            case 3: // 年
                $time = strtotime('+1 year', $time);
                break;

            case 4: // 永久
                $time = 4294967295;
                break;
        }

        $time *= $num;

        // 更新至数据库
        $this->db->where('uid', $uid)->update('member', array(
            'groupid' => $groupid,
            'overdue' => $time,
        ));

        // 发送通知
        $this->models('member/notice')->add($uid, 1, L('恭喜亲，您的会员组续费成功'));

        // 会员组升级挂钩点
        $this->hooks->call_hook('member_group_upgrade', array('uid' => $uid, 'groupid' => $groupid));

        return $time;
    }

    /**
     * 管理员用户信息
     *
     * @param    int $uid 用户id
     * @param    int $verify 是否验证该管理员权限
     * @return    array|int
     * int    -3    您无权限登录管理平台
     * int    -4    您无权限登录该站点
     * array    管理员用户信息数组
     */
    public function get_admin_member($uid, $verify = 0)
    {

        // 查询用户信息
        $data = $this->db
            ->select('m.uid,m.email,m.username,m.adminid,m.groupid,m.name,a.usermenu,a.color')
            ->from($this->db->dbprefix('member') . ' AS m')
            ->join($this->db->dbprefix('admin') . ' AS a', 'a.uid=m.uid', 'left')
            ->where('m.uid', $uid)
            ->limit(1)
            ->get()
            ->row_array();
        if (!$data) {
            return 0;
        } elseif ($verify) {
            // 判断用户状态
            if ($data['adminid'] == 0) {
                return -3;
            } elseif (!$this->models('Admin')->is_admin_auth($data['adminid'])) {
                return -4;
            }
        }

        $role = $this->dcache->get('role');
        $data['role'] = $role[$data['adminid']];
        $data['realname'] = $data['name'];
        $data['usermenu'] = string2array($data['usermenu']);
        $data['color'] = string2array($data['color']);

        return $data;
    }

    /**
     * 管理人员
     *
     * @param    int $roleid 角色组id
     * @param    string $keyword 匹配关键词
     * @return    array
     */
    public function get_admin_all($roleid = 0, $keyword = NULL)
    {

        $select = $this->db
            ->from($this->db->dbprefix('admin') . ' AS a')
            ->join($this->db->dbprefix('member') . ' AS b', 'a.uid=b.uid', 'left');
        $select->join($this->db->dbprefix('admin_role') . ' AS c', 'b.adminid=c.id', 'left');

        $roleid && $select->where('b.adminid', $roleid);
        $keyword && $select->like('b.username', $keyword);

        return $select->get()->result_array();
    }

    /**
     * 添加管理人员
     *
     * @param    array $insert 入库管理表内容
     * @param    array $update 更新会员表内容
     * @param    int $uid uid
     * @return    void
     */
    public function insert_admin($insert, $update, $uid)
    {
        $this->db->where('uid', $uid)->update('member', $update);
        $this->db->replace('admin', $insert);
    }

    /**
     * 修改管理人员
     *
     * @param    array $insert 入库管理表内容
     * @param    array $update 更新会员表内容
     * @param    int $uid uid
     * @return    void
     */
    public function update_admin($insert, $update, $uid)
    {
        $this->db->where('uid', $uid)->update('member', $update);
        $this->db->where('uid', $uid)->update('admin', $insert);
    }

    /**
     * 移除管理人员
     *
     * @param    int $uid uid
     * @return    void
     */
    public function del_admin($uid)
    {

        if ($uid == 1) {
            return NULL;
        }

        $this->db->where('uid', $uid)->delete('admin');
        $this->db->where('uid', $uid)->delete('admin_login');
        $this->db->where('uid', $uid)->update('member', array('adminid' => 0));
    }

    /**
     * 取会员COOKIE
     *
     * @return    int    $uid    会员uid
     */
    public function member_uid($login = 0)
    {

        if (!$login && IS_MEMBER && $uid = $this->session->userdata('member_auth_uid')) {
            return $uid;
        } else {
            $uid = (int)get_cookie('member_uid');
            if (!$uid) {
                return NULL;
            }
            if (!$this->session->userdata('uid')) {
                $this->models('member/login')->add($uid); // 更新登录时间
                $this->session->set_userdata('uid', $uid); // 更新会员活动时间
            }
            return $uid;
        }
    }

    /**
     * 会员配置信息
     *
     * @return    array
     */
    public function setting($isdomain = FALSE)
    {

        $domain = $member = $data = array();

        // 查询出配置信息
        $setting = $this->db->get('member_setting')->result_array();
        foreach ($setting as $t) {
            $t['name'] == 'member' ? $member = string2array($t['value']) : $data[$t['name']] = string2array($t['value']);
        }
        $data = $data + $member;
        // 返回域名信息
        if ($isdomain && $data['domain']) {
            foreach ($data['domain'] as $c) {
                $c && $domain[] = dr_http_prefix($c);
            }
        }

        return $isdomain ? array($data, $domain) : $data;
    }

    /**
     * 会员配置
     *
     * @return    array
     */
    public function member($set = NULL)
    {

        $data = $this->db->where('name', 'member')->get('member_setting')->row_array();
        $data = string2array($data['value']);

        // 修改数据
        if ($set) {
            $this->db->where('name', 'member')->update('member_setting', array('value' => array2string($set)));
            $data = $set;
        }

        return $data;
    }

    /**
     * 会员权限
     *
     * @param    intval $id 权限组标识
     * @param    string $set 权限组值
     * @return    array
     */
    public function permission($id, $set = NULL)
    {

        $data = $this->db->where('name', 'permission')->get('member_setting')->row_array();
        $data = string2array($data['value']);

        // 修改数据
        if ($set) {
            $data[$id] = $set;
            $this->db->where('name', 'permission')->update('member_setting', array('value' => array2string($data)));
        }

        return isset($data[$id]) ? $data[$id] : NULL;
    }

    /**
     * 支付配置
     *
     * @param    array $set 修改数据
     * @return    array
     */
    public function pay($set = NULL)
    {

        $data = $this->db->where('name', 'pay')->get('member_setting')->row_array();
        $data = string2array($data['value']);

        // 修改数据
        if ($set) {
            $this->db->where('name', 'pay')->update('member_setting', array('value' => array2string($set)));
            $data = $set;
        }

        return $data;
    }

    /**
     * 提现配置
     *
     * @param    array $set 修改数据
     * @return    array
     */
    public function cash($set = NULL)
    {

        $data = $this->db->where('name', 'cash')->get('member_setting')->row_array();
        $data = string2array($data['value']);

        // 修改数据
        if ($set) {
            $this->db->replace('member_setting', array(
                'name' => 'cash',
                'value' => array2string($set)
            ));
            return $set;
        }

        return $data;
    }

    /**
     * 游客配置
     *
     * @param    array $set 修改数据
     * @return    array
     */
    public function guest($set = NULL)
    {

        $data = $this->db->where('name', 'guest')->get('member_setting')->row_array();
        !$data && $this->db->insert('member_setting', array('name' => 'guest', 'value' => ''));
        $data = string2array($data['value']);
        // 修改数据
        if ($set) {
            $this->db->where('name', 'guest')->update('member_setting', array('value' => array2string($set)));
            $data = $set;
        }

        return $data;
    }

    /**
     * 会员缓存
     *
     * @param    int $id
     * @return    NULL
     */
    public function cache()
    {

        $cache = array();
        $this->dcache->delete('member');

        // 会员自定义字段
        $field = $this->db
            ->where('disabled', 0)
            ->where('relatedid', 0)
            ->where('relatedname', 'member')
            ->order_by('displayorder ASC,id ASC')
            ->get('field')
            ->result_array();
        if ($field) {
            foreach ($field as $t) {
                $t['setting'] = string2array($t['setting']);
                $cache['field'][$t['fieldname']] = $t;
            }
        }

        // 会员组
        $group = $this->db->order_by('displayorder ASC, id ASC')->get('member_group')->result_array();
        if ($group) {
            foreach ($group as $t) {
                $t['allowfield'] = string2array($t['allowfield']);
                // 会员等级
                $level = $this->db->where('groupid', $t['id'])->order_by('experience ASC')->get('member_level')->result_array();
                if ($level) {
                    foreach ($level as $l) {
                        $t['level'][$l['id']] = $l;
                    }
                    $cache['group'][$t['id']] = $t;
                } elseif ($t['id'] < 3) {
                    $cache['group'][$t['id']] = $t;
                }
            }
        }

        $cache['synurl'] = array();
        list($cache['setting'], $cache['synurl']) = $this->setting(TRUE);
        $cache['rule'] = $this->ci->get_cache('urlrule', (int)$cache['setting']['urlrule'], 'value'); // 会员规则
        $domain = require CONFPATH.'domain.php'; // 加载站点域名配置文件
        // 加载分站域名配置文件
        $fenzhan_domain = SITE_FID ? require CONFPATH.'fenzhan.php' : array();

        // 增加到登录同步列表中
        foreach ($this->site_info as $sid => $t) {
            // 主站点域名
            $cache['synurl'][] = dr_http_prefix($t['SITE_DOMAIN']);
            // 移动端域名
            $t['SITE_MOBILE'] && $cache['synurl'][] = dr_http_prefix($t['SITE_MOBILE']);
            // 将站点的域名配置文件加入同步列表中
            foreach ($domain as $url => $site_id) {
                if ($url && $site_id == $sid) {
                    if (isset($fenzhan_domain[$url]) && $fenzhan_domain[$url]) {
                        // 分站域名
                        $cache['synurl'][] = dr_http_prefix($url);
                    } elseif ($t['SITE_DOMAIN'] != $url && $t['SITE_MOBILE'] != $url) {
                        // 筛选出站点域名和移动端域名
                        $cache['synurl'][] = dr_http_prefix($url);
                    }
                }
            }
        }
        $cache['synurl'] = array_unique($cache['synurl']);

        $this->ci->clear_cache('member');
        $this->dcache->set('member', $cache);

        return $cache;
    }

    /**
     * 条件查询
     *
     * @param    object $select 查询对象
     * @param    array $param 条件参数
     * @return    array
     */
    private function _where(&$select, $data)
    {

        // 存在POST提交时，重新生成缓存文件
        if (IS_POST) {
            $data = $this->input->post('data');
            foreach ($data as $i => $t) {
                if ($t == '') {
                    unset($data[$i]);
                }
            }
        }

        // 存在search参数时，读取缓存文件
        if ($data) {
            if (isset($data['keyword']) && $data['keyword'] != '' && $data['field']) {
                if ($data['field'] == 'uid') {
                    // 按id查询
                    $id = array();
                    $ids = explode(',', $data['keyword']);
                    foreach ($ids as $i) {
                        $id[] = (int)$i;
                    }
                    $select->where_in('member.uid', $id);
                } elseif ($data['field'] == 'ismobile') {
                    $select->where($data['field'], intval($data['keyword']));
                } elseif (in_array($data['field'], array('complete', 'is_auth'))) {
                    $select->where('member.uid IN (select uid from `' . $this->db->dbprefix('member_data') . '` where `' . $data['field'] . '` = ' . intval($data['keyword']) . ')');
                } elseif (in_array($data['field'], array('phone', 'name', 'email', 'username'))) {
                    $select->like($data['field'], urldecode($data['keyword']));
                } else {
                    // 查询附表字段
                    $select->where('`' . $data['field'] . '` LIKE "%' . urldecode($data['keyword']) . '%"');
                }
            }
            // 查询会员组
            isset($data['groupid']) && $data['groupid'] && $select->where('groupid', (int)$data['groupid']);
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'first':
                        isset($data['invitation_code']) && $data['invitation_code'] && $select->where('invitation_code', $data['invitation_code']);
                        break;
                    case 'second':
                        isset($data['invitation_code']) && $data['invitation_code'] && $select->where("invitation_code IN(SELECT randcode FROM imt_member WHERE invitation_code='{$data['invitation_code']}')");
                        break;
                    case 'dis':
                        isset($data['uid']) && $data['uid'] && $select->where('imt_member.uid', (int)$data['uid']);
                        break;
                    case 'fin':
                        isset($data['uid']) && $data['uid'] && $select->where('imt_member.uid', (int)$data['uid']);
                        break;
                }
            }
        }
        // 判断groupid
        !isset($data['groupid']) && $_GET['groupid'] && $select->where('groupid', (int)$_GET['groupid']);
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'first':
                    !isset($data['invitation_code']) && $_GET['invitation_code'] && $select->where('invitation_code', $_GET['invitation_code']);
                    break;
                case 'second':
                    !isset($data['invitation_code']) && $_GET['invitation_code'] && $select->where("invitation_code IN(SELECT randcode FROM imt_member WHERE invitation_code='{$data['invitation_code']}')");
                    break;
                case 'dis':
                    !isset($data['uid']) && $_GET['uid'] && $select->where('imt_member.uid', (int)$_GET['uid']);
                    break;
                case 'fin':
                    !isset($data['uid']) && $_GET['uid'] && $select->where('imt_member.uid', (int)$_GET['uid']);
                    break;
            }
        }
        
        return $data;
    }

    /**
     * 数据分页显示
     *
     * @param    array $param 条件参数
     * @param    intval $page 页数
     * @param    intval $total 总数据
     * @return    array
     */
    public function limit_page($param, $page, $total)
    {

        if (!$total || IS_POST) {
            $select = $this->db->select('count(*) as total');
            $_param = $this->_where($select, $param);
            $data = $select->join('member_data', 'member_data.uid = member.uid', 'left')->get('member')->row_array();
            unset($select);
            $total = (int)$data['total'];
            if (!$total) {
                $_param['total'] = 0;
                return array(array(), $_param);
            }
            $page = 1;
        }

        $select = $this->db->select('member_data.*,member.*,member.uid as uid')->join('member_data', 'member_data.uid = member.uid', 'left')->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1));
        $_param = $this->_where($select, $param);
        $order = dr_get_order_string(isset($_GET['order']) && strpos($_GET['order'], "undefined") !== 0 ? $this->input->get('order', TRUE) : 'member.uid desc', 'member.uid desc');
        $data = $select->order_by($order)->get('member')->result_array();
        $_param['total'] = $total;
        $_param['order'] = $order;

        return array($data, $_param);
    }

    /**
     * 会员初始化处理
     */
    public function init()
    {

        // 明天凌晨时间戳
        $time = strtotime(date('Y-m-d', strtotime('+1 day')));

        // 每日登录经验处理
        if (!get_cookie('login_experience_' . $this->uid)
            && !$this->db
                ->where('uid', $this->uid)
                ->where('type', 0)
                ->where('mark', 'login')
                ->where('DATEDIFF(from_unixtime(inputtime),now())=0')
                ->count_all_results('member_scorelog')) {
            set_cookie('login_experience_' . $this->uid, TRUE, $time - SYS_TIME);
            $this->models('member/score')->edit(0, $this->uid, (int)$this->member_rule['login_experience'], 'login', L('每日登陆'));
        }

        // 每日登录虚拟币处理
        if (!get_cookie('login_score_' . $this->uid)
            && !$this->db
                ->where('uid', (int)$this->uid)
                ->where('type', 1)
                ->where('mark', 'login')
                ->where('DATEDIFF(from_unixtime(inputtime),now())=0')
                ->count_all_results('member_scorelog')) {
            set_cookie('login_score_' . $this->uid, TRUE, $time - SYS_TIME);
            $this->models('member/score')->edit(1, $this->uid, (int)$this->member_rule['login_score'], 'login', L('每日登陆'));
        }
        $this->hooks->call_hook('member_init'); // 会员中心初始化的钩子
    }

    /**
     * 验证码加密
     *
     * @param    intval $uid
     * @return  string
     */
    public function get_encode($uid)
    {
        $randcode = rand(1000, 999999);
        $this->encrypt->set_cipher(MCRYPT_BLOWFISH);
        $this->db->where('uid', $uid)->update('member', array('randcode' => $randcode));
        return $this->encrypt->encode(SYS_TIME . ',' . $uid . ',' . $randcode);
    }

    /**
     * 验证码解码
     *
     * @param    string $code
     * @return  string
     */
    public function get_decode($code)
    {
        $code = str_replace(' ', '+', $code);
        $this->encrypt->set_cipher(MCRYPT_BLOWFISH);
        return $this->encrypt->decode($code);
    }

    /**
     * 会员删除
     *
     * @param    intval $uid
     * @return  bool
     */
    public function delete($uids)
    {

        if (!$uids || !is_array($uids)) {
            return NULL;
        }

        $app = $this->db->get('application')->result_array();

        foreach ($uids as $uid) {
            if ($uid == 1) {
                continue;
            }
            $tableid = (int)substr((string)$uid, -1, 1);
            // 删除会员表
            $this->db->where('uid', $uid)->delete('member');
            // 删除会员附表
            $this->db->where('uid', $uid)->delete('member_data');
            // 删除会员地址表
            $this->db->where('uid', $uid)->delete('member_address');
            // 删除快捷登陆表
            $this->db->where('uid', $uid)->delete('member_oauth');
            // 删除会员登录日志表
            $this->db->where('uid', $uid)->delete('member_login');
            // 删除管理员表
            $this->db->where('uid', $uid)->delete('admin');
            // 删除支付记录
            $this->db->where('uid', $uid)->delete('member_paylog');
            // 删除积分记录
            $this->db->where('uid', $uid)->delete('member_scorelog');
            // 删除附件
            $this->models('system/attachment')->delete_for_uid($uid);
            // 按站点删除模块数据
            foreach ($this->site_info as $siteid => $v) {
                $cache = $this->dcache->get('module-' . $siteid);
                if ($cache) {
                    foreach ($cache as $dir => $mod) {
                        $table = $this->site[$siteid]->dbprefix($siteid . '_' . $dir);
                        if (!$this->site[$siteid]->where('uid', $uid)->count_all_results($table . '_index')) {
                            continue;
                        }
                        // 删除主表
                        $this->site[$siteid]->where('uid', $uid)->delete($table);
                        // 删除索引表
                        $this->site[$siteid]->where('uid', $uid)->delete($table . '_index');
                        // 删除审核表
                        $this->site[$siteid]->where('uid', $uid)->delete($table . '_verify');
                        // 删除标记表
                        $this->site[$siteid]->where('uid', $uid)->delete($table . '_flag');
                        // 删除栏目表
                        $this->site[$siteid]->where('uid', $uid)->delete($table . '_category_data');
                        // 删除附表
                        for ($i = 0; $i < 125; $i++) {
                            if (!$this->site[$siteid]->query("SHOW TABLES LIKE '%" . $table . '_data_' . $i . "%'")->row_array()) {
                                break;
                            }
                            $this->site[$siteid]->where('uid', $uid)->delete($table . '_data_' . $i);
                        }
                        // 删除栏目附表
                        for ($i = 0; $i < 125; $i++) {
                            if (!$this->site[$siteid]->query("SHOW TABLES LIKE '%" . $table . '_category_data_' . $i . "%'")->row_array()) {
                                break;
                            }
                            $this->site[$siteid]->where('uid', $uid)->delete($table . '_category_data_' . $i);
                        }
                    }
                }
            }
            // 按应用删除
            if ($app) {
                foreach ($app as $a) {
                    $dir = $a['dirname'];
                    if (is_file(FCPATH . 'app/' . $dir . '/models/' . $dir . '_model.php')) {
                        $this->load->add_package_path(FCPATH . 'app/' . $dir . '/');
                        $this->load->model($dir . '_model', 'app_model');
                        $this->app_model->delete_for_uid($uid);
                        $this->load->remove_package_path(FCPATH . 'app/' . $dir . '/');
                    }
                }
            }
            // 删除会员附件
            $this->load->helper('file');
            delete_files(SYS_UPLOAD_PATH . '/member/' . $uid . '/');
            // 删除通知
            $this->db->where('uid', $uid)->delete('member_notice');
        }
    }


    /**
     * 注册会员 入库
     *
     * @param    array $data 会员数据
     * @param    string $OAuth OAuth名称
     * @param    intval $groupid 组id
     * @return    int
     */
    public function add($data, $OAuth = NULL, $groupid = NULL, $uid = NULL)
    {
        $this->hooks->call_hook('member_add_before', $data);

        $salt = substr(md5(rand(0, 999)), 0, 10); // 随机10位密码加密码
        $regverify = $this->ci->get_cache('member', 'setting', 'regverify');

        if ($OAuth) {
            $groupid = 2;
            if ($this->ci->get_cache('member', 'setting', 'regoauth')) {
                $data['nickname'] = dr_clear_emoji(dr_weixin_emoji($data['nickname']));
                !$data['nickname'] && $data['nickname'] = rand(1, 99) . SYS_TIME;
                $data['username'] = $OAuth;
            } else {
                $data['username'] = '';
            }
            $username = $data['username'];
            $this->db->insert('member', array(
                'salt' => $salt,
                'name' => $data['nickname'] ? $data['nickname'] : '',
                'phone' => '',
                'regip' => $this->input->ip_address(),
                'email' => '',
                'spend' => 0,
                'money' => 0,
                'score' => 0,
                'avatar' => $data['avatar'] ? $data['avatar'] : '',
                'freeze' => 0,
                'regtime' => SYS_TIME,
                'groupid' => $groupid,
                'levelid' => 0,
                'overdue' => 0,
                'username' => $username,
                'password' => '',
                'randcode' => 0,
                'ismobile' => 0,
                'experience' => 0,
            ));
            $uid = $data['uid'] = $this->db->insert_id();
            unset($data['username']);
            $this->db->insert('member_oauth', $data);
            $data['username'] = $username;
            $this->hooks->call_hook('member_oauth_register', array('uid' => $uid, 'Oauth' => $OAuth));
        } elseif ($uid) {
            $data['email'] = strtolower($data['email']);
            $data['phone'] = trim($data['phone']);
            $data['password'] = trim($data['password']);
            $groupid = 3;
            $this->db->where('uid', (int)$uid)->update('member', array(
                'salt' => $salt,
                'email' => $data['email'],
                'groupid' => $groupid,
                'username' => $data['username'],
                'password' => md5(md5($data['password']) . $salt . md5($data['password']))
            ));
        } else {
            $data['email'] = strtolower($data['email']);
            $data['password'] = trim($data['password']);
            $groupid = $groupid ? $groupid : ($regverify ? 1 : 3);
            $randcode = create_randcode();
            $this->db->insert('member', array(
                'salt' => $salt,
                'name' => $data['nickname'] ?? '',
                'phone' => $data['phone'] ? $data['phone'] : '',
                'regip' => $this->input->ip_address(),
                'email' => $data['email'],
                'money' => 0,
                'score' => 0,
                'spend' => 0,
                'avatar' => $data['avatar'] ? $data['avatar'] : '',
                'freeze' => 0,
                'regtime' => SYS_TIME,
                'groupid' => $groupid,
                'levelid' => 0,
                'overdue' => 0,
                'username' => $data['username'],
                'password' => md5(md5($data['password']) . $salt . md5($data['password'])),
                'randcode' => $randcode,
                'ismobile' => 0,
                'experience' => 0,
            ));
            $uid = $this->db->insert_id();
            // 添加发票信息
            // $this->db->insert('1_form_invoice_info', array(
            //     'id' => $uid,
            //     'uid' => $uid,
            //     'author' => $data['username'],
            //     'invoice_type' => L('纸质发票'),
            //     'head_up_type' => L('企业抬头'),
            //     'invoice_content' => L('技术服务费'),
            //     'invoice_amount' => 1000.00,
            //     'inputtime' => SYS_TIME
            // ));
            if ($regverify) {
                switch ($regverify) {
                    case 1:
                        $url = dr_member_url('login/verify') . '&code=' . $this->get_encode($uid);
                        $this->models('system/email')->send($data['email'], L('会员注册-邮件验证'), L(@file_get_contents(WEBPATH . 'cache/email/verify.html'), $data['username'], $url, $url, $this->input->ip_address()));
                        break;
                    case 2:
                        $this->models('member/notice')->add_admin('member', L('新会员【%s】注册审核', $data['username']), 'admin/member/index/field/uid/keyword/' . $uid);
                        break;
                    default:
                        break;
                }
            }
        }

        $this->hooks->call_hook('member_add_after', $data);

        return $uid;
    }

    /**
     * 更改用户余额
     * @param int $uid
     * @param float $money 正数为增加，负数为减少
     * @return mixed
     */
    public function modify_money($uid, $money)
    {
        return $this->db->set('money', 'money + ' . (float)$money, false)->where('uid', (int)$uid)->update('member');
    }

    /**
     * 统计完成订单的数量
     * @param int $uid 会员 uid
     * @return int
     */
    public function get_all_over($uid)
    {
        $this->db->where(['buy_uid' => $uid, 'order_status' => 3]);
        return $this->db->count_all_results('1_order');
    }

    /**
     * 统计本月完成订单数量
     * @param int $uid 会员 uid
     * @return int
     */
    public function get_month_over($uid)
    {
        $month = get_month();
        $this->db->from('1_order as o')
                 ->join('1_order_operate as oo', 'oo.oid = o.id', 'inner')
                 ->where(['o.order_status' => 3, 'oo.order_status' => 3, 'o.buy_uid' => $uid, 'oo.inputtime >=' => $month['month_start'], 'oo.inputtime <=' => $month['month_end']]);
        return $this->db->count_all_results();
    }

    /**
     * 统计今日完成的订单数量
     * @param int $uid 会员 uid
     * @return int
     */
    public function get_today_over($uid)
    {
        $today = get_today();
        $this->db->from('1_order_operate as oo')
                 ->join('1_order as o', 'oo.oid = o.id', 'inner')
                 ->where(['oo.order_status' => 3, 'o.buy_uid' => $uid, 'oo.inputtime >=' => $today['today_start'], 'oo.inputtime <=' => $today['today_end']]);
        return $this->db->count_all_results();
    }

    /**
     * 本月收益
     * @param int $uid 会员 uid
     * @return float
     */
    public function get_month_income($uid)
    {
        $month = get_month();
        $this->db->select_sum('value')
                 ->where(['uid' => $uid, 'value >' => 0, 'status' => 1, 'inputtime >=' => $month['month_start'], 'inputtime <=' => $month['month_end']]);
        return $this->db->get('member_paylog')->row_array();
    }
}
