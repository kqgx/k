<?php

class System_Pay_model extends CI_Model
{

    public $prefix;
    public $tablename;

    public $pagesize = 10;
    public $cache = 0;

    public function __construct()
    {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix . '_paylog';
    }

    public function module($dir)
    {
        $this->prefix = $this->link->dbprefix($dir);
        $this->tablename = $this->prefix . '_paylog';
    }


    public function get($uid)
    {
        return $this->where('uid', $uid)->result();
    }

    /**
     * 获取一条paylog记录
     * @param int $id
     * @return array|null
     */
    public function getItem($id)
    {
        $data = $this->db->where('id', (int)$id)->get('member_paylog')->row_array();
        $data['disabled_pays'] = explode(',', $data['disabled_pays']);
        return $data;
    }

    /**
     * 创建一条充值记录
     * @param int $uid 充值的用户
     * @param float $money 金额
     * @param bool $status 成功状态，传入true时直接触发成功回调，默认需支付成功后回调
     * @param string $note 订单描述
     * @return int 成功返回订单记录ID，失败返回false
     */
    public function recharge($uid, $money, $status = false, $note = '')
    {
        $data = [
            'uid' => (int)$uid,
            'value' => abs($money),
            'module' => 'recharge',
            'order' => '',
            'note' => $note ?: '充值',
            'disabled_pays' => 'balance',
            'type' => '',
            'status' => 0,
            'inputtime' => SYS_TIME
        ];
        $this->db->insert('member_paylog', $data);
        $data['id'] = $this->db->isnert_id();
        if ($data['id']) {
            if ($status) {
                $this->paySuccess('', $data);
            }
            return (int)$data['id'];
        } else {
            return false;
        }
    }

    /**
     * 创建一条待支付订单
     * @param int $uid 付款用户UID
     * @param float $money 消费金额
     * @param string $module 模块、业务标识
     * @param string $order 订单
     * @param string $note 订单描述
     * @param array $disabled_pays  支付方式禁用列表
     * @return int|bool 成功返回订单记录ID，失败返回false
     */
    public function create($uid, $money, $module, $order = '', $note = '', array $disabled_pays = [])
    {
        $this->db->insert('member_paylog', [
            'uid' => (int)$uid,
            'value' => -abs($money),
            'module' => $module,
            'order' => $order,
            'note' => $note ?: '消费',
            'disabled_pays' => join(',', $disabled_pays),
            'type' => '',
            'status' => 0,
            'inputtime' => SYS_TIME
        ]);
        return $this->db->insert_id() ?: false;
    }

    /**
     * 查询支付订单
     * @param int $uid
     * @param string $module
     * @param string|null $order
     * @param int|null $status
     * @return mixed
     */
    public function getOrders($uid, $module, $order = null, $status = null)
    {
        if (isset($order)) {
            $this->db->where('order', $order);
        }
        if (isset($status)) {
            $this->db->where('status', $status);
        }
        return $this->db->where('uid', $uid)->where('module', $module)->get('member_paylog')->result_array();
    }

    /**
     * 执行支付订单成功动作
     * @param string $type 支付方式
     * @param array|int $paylog 传入paylog记录内容数组或ID
     * @param array|null $notify_data 在线支付接口回调信息
     * @return bool
     */
    public function paySuccess($type, $paylog, $notify_data = null)
    {
        if (is_numeric($paylog)) {
            $paylog = $this->getItem($paylog);
            if (!$paylog) {
                return false;
            }
        }
        $member = $paylog['uid'] ? dr_member_info($paylog['uid']) : null;
        $this->updateLog($paylog['id'], ['type' => $type, 'status' => 1]);
        $this->hooks->call_hook('pay_callback', [
            'type' => $type,
            'notify' => $notify_data,
            'data' => $paylog,
            'member' => $member
        ]);
        $this->updateLog($paylog['id'], ['status' => 1]);
        $this->hooks->call_hook('pay_success', [
            'type' => $_GET['type'],
            'data' => $paylog,
            'member' => $member
        ]);
        return true;
    }

    /**
     * 更新paylog
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateLog($id, array $data)
    {
        unset($data['id']);
        return $this->db->set($data)->where('id', $id)->update('member_paylog');
    }

    /*
     * 条件查询
     *
     * @param	object	$select	查询对象
     * @param	array	$param	条件参数
     * @return	array
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

        if ($data) {
            isset($data['start']) && $data['start'] && $data['start'] != $data['end'] && $select->where('inputtime BETWEEN ' . $data['start'] . ' AND ' . ($data['end'] ? $data['end'] : SYS_TIME));
            strlen($data['status']) > 0 && $select->where('status', (int)$data['status']);
            strlen($data['keyword']) > 0 && $select->where('(uid in (select uid from ' . $this->db->dbprefix('member') . ' where `username`="' . $data['keyword'] . '"))');
            strlen($data['type']) > 0 && ($data['type'] == 1 ? $select->where('value>0') : $select->where('value<0'));
        }

        return $data;
    }

    /*
     * 数据分页显示
     *
     * @param	array	$param	条件参数
     * @param	intval	$page	页数
     * @param	intval	$total	总数据
     * @return	array
     */
    public function limit_page($param, $page, $total)
    {

        if (!$total || IS_POST) {
            $select = $this->db->select('count(*) as total');
            $this->_where($select, $param);
            $data = $select->get('member_paylog')->row_array();
            unset($select);
            $total = (int)$data['total'];
            if (!$total) return array(array(), array('total' => 0));
        }

        $select = $this->db->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1));
        $_param = $this->_where($select, $param);
        $data = $select->order_by('inputtime DESC')->get('member_paylog')->result_array();
        $_param['total'] = $total;

        return array($data, $_param);
    }

    // 充值
    public function add($uid, $value, $note)
    {

        if (!$uid || !$value) {
            return NULL;
        }

        // 更新RMB
        $db = $this->db->where('uid', $uid);
        if ($value > 0) {
            $db->set('money', 'money+' . $value, FALSE);
        } else {
            $db->set('money', 'money-' . abs($value), FALSE);
            $db->set('spend', 'spend+' . abs($value), FALSE);
        }
        $db->update('member');
        unset($db);

        // 更新记录表
        $this->db->insert('member_paylog', array(
            'uid' => $uid,
            'type' => '',
            'note' => $note,
            'value' => $value,
            'order' => 0,
            'status' => 1,
            'module' => '',
            'inputtime' => SYS_TIME,
        ));
    }

    // 支付成功，更改状态
    public function pay_success($sn, $money, $note = '')
    {

        list($a, $id, $uid, $module, $order) = explode('-', $sn);
        if (!$id) {
            return NULL;
        }

        if ($uid) {
            $this->uid = $this->ci->uid = $uid;
            $this->member = $this->ci->member = dr_member_info($uid);
        }

        // 查询支付记录
        $data = $this->db->where('id', $id)->limit(1)->get('member_paylog')->row_array();
        if (!$data) {
            return NULL;
        } elseif ($data['status']) {
            return $data['module'];
        } elseif ($data['uid'] != $uid) {
            return null;
        }

        $money = $money > 0 ? $money : $data['value'];

        // 标示支付订单成功
        $this->db->where('id', $id)->update('member_paylog', array('status' => 1, 'note' => $note));

        // 更新会员表金额
        // $uid && $this->db->where('uid', $uid)->set('money', 'money+' . $money, FALSE)->update('member');

        // 支付成功挂钩点
        $this->hooks->call_hook('pay_success', $data);

        return $data['module'];
    }

    // 在线充值
    public function add_for_online($pay, $money, $module = APP_DIR, $order = array())
    {

        if (!$pay || $money < 0) {
            return NULL;
        }

        $module = $module == 'member' ? '' : $module;
        // 更新记录表
        $this->db->insert('member_paylog', array(
            'uid' => $this->uid,
            'note' => '',
            'type' => $pay,
            'value' => $money,
            'order' => array2string($order),
            'status' => 0,
            'module' => $module,
            'inputtime' => SYS_TIME
        ));

        $id = $this->db->insert_id();
        if (!$id) {
            return NULL;
        }

        $sn = 'FC-' . $id . '-' . $this->uid;
        if ($order) {
            if ($module == 'order') {
                $title = L('会员(%s)购物消费，购物订单ID：%s', $this->member['username'], implode(',', $order));
            } elseif ($module == 'app') {
                $sn = 'FC-' . $id . '-' . $this->uid . '-' . strtoupper($module) . '-' . $order['id'];
                $title = $order['title'];
            } else {
                $sn = 'FC-' . $id . '-' . $this->uid . '-' . strtoupper($module) . '-' . (string)$order;
                $title = L('会员(%s)购物消费，购物订单ID：%s', $this->member['username'], strtoupper($module) . '-' . (string)$order);
            }
        } else {
            $title = L('会员充值(%s)', $this->member['username']);
        }

        $result = NULL;
        require_once WEBPATH . 'api/pay/' . $pay . '/pay.php';

        return $result;
    }

    // 在线付款
    public function pay_for_online($id)
    {

        if (!$id) {
            return NULL;
        }

        // 查询支付记录
        $data = $this->db
            ->where('id', $id)
            ->where('uid', $this->uid)
            ->where('status', 0)
            ->select('value,type,order,module')
            ->limit(1)
            ->get('member_paylog')
            ->row_array();
        if (!$data) {
            return NULL;
        }

        if (!$this->uid) {
            $this->member['uid'] = 0;
            $this->member['username'] = L('游客');
        }

        // 判断订单是否支付过，否则作废
        $sn = 'FC-' . $id . '-' . $this->uid;
        if ($data['module']) {
            if ($data['module'] == 'order') {
                $data['order'] = string2array($data['order']);
                $title = L('会员(%s)购物消费，购物订单ID：%s', $this->member['username'], implode(',', $data['order']));
            } elseif ($data['module'] == 'app') {
                $order = string2array($data['order']);
                $sn = 'FC-' . $id . '-' . $this->uid . '-' . strtoupper($data['module']) . '-' . $order['id'];
                $title = $order['title'];
            } else {
                $sn = 'FC-' . $id . '-' . $this->uid . '-' . strtoupper($data['module']) . '-' . $data['order'];
                $title = L('会员(%s)购物消费，购物订单ID：%s', $this->member['username'], strtoupper($data['module']) . '-' . $data['order']);
            }
        } else {
            $title = L('会员充值(%s)', $this->member['username']);
        }

        $money = $data['value'];
        $result = NULL;
        require_once WEBPATH . 'api/pay/' . $data['type'] . '/pay.php';
        return $result;
    }

    /**
     * 支付配置
     *
     * @param    array $set 修改数据
     * @return    array
     */
    public function setting($set = NULL)
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
}
