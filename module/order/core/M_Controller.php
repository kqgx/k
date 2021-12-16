<?php

require_once FCPATH.'core/D_Common.php';

class M_Controller extends D_Common {

    public $dir;
    public $link; // 当前模块数据库对象
    public $mconfig; // 当前模块的配置信息
    public $groupid; // 会员组id
    public $agent; // 客户端认证码

    /**
     * 模块初始化
     */
    public function __construct() {
        parent::__construct();
        // 检查模块
        $module = $this->get_cache('module-'.SITE_ID.'-'.APP_DIR);
        $this->dir = APP_DIR;
		// 模块缓存不存在时
        if (!$module) {
            $this->admin_msg(L('模块【'.$this->dir.'】不存在'));
        }
		
        $this->lang->load('module');
        $this->load->helper('order');

        // 模块常量
        define('MOD_DIR', APP_DIR);
        define('MODULE_ID', $module['id']);
        define('MODULE_URL', $this->mobile ? SITE_URL.'index.php?s='.$this->dir : $module['url']);
        define('MODULE_NAME', $module['name']);
        define('MODULE_TITLE', $module['site'][SITE_ID]['module_title']);
        define('MODULE_TEMPLATE', $module['template']);
        define('MODULE_THEME_PATH', strpos($module['theme'], 'http') === 0 ? trim($module['theme'], '/').'/' : THEME_PATH.($module['theme'] ? $module['theme'] : 'default').'/');

        // 设置模块模板
        $this->template->module($module['template']);

        $this->groupid = $this->uid ? $this->member['groupid'] : 0;
        $this->mconfig = isset($module['setting'][SITE_ID]) ? $module['setting'][SITE_ID] : $module['setting'];

        $this->agent = md5($this->input->ip_address().$this->input->user_agent());

    }

    // msg
    public function order_msg($status, $msg) {

        if (IS_AJAX) {
            exit(dr_json($status, $msg));
        } else {
            $this->msg($msg);
        }
    }

    // 格式化商品
    protected function _format_info($list) {

        if ($list) {
            foreach ($list as $i => $t) {
                $list[$i]['goods'] = $this->db->where('oid', $t['id'])->get(SITE_ID.'_order_goods')->result_array();
                $list[$i]['goods_num'] = count($list[$i]['goods']);
                // 判断过期订单
                if ($this->models('Module/order')->clear($t)) {
                    $list[$i]['order_status'] = 0;
                }
            }
        }

        return $list;
    }

    // 获取快递信息
    public function _kd_info($name, $sn) {
        $url = 'http://api.kuaidi100.com/applyurl?key='.$this->mconfig['config']['id'].'&com='.$name.'&nu='.$sn.'&show=2&muti=1&order=desc';
        $curl = dr_catcher_data($url);
        if (!$curl) {
            return '查询超时';
        } else {
            return '<a href="javascript:dr_iframe_show(\''.$curl.'\', \'50%\', 300);">查看物流信息</a>';
        }
    }

    // 订单打印预览
    public function print_info() {

        $kd = require APPPATH.'libraries/Kd.php';
        $paytype = $this->mconfig['paytype'];

        $code = file_get_contents(APPPATH.'config/print.html');
        $file = $this->template->code2php($code);

        $ids = explode(',', $this->input->get('id'));
        foreach ($ids as $id) {

            $order = $this->models('Module/order')->get_info($id);
            if (!$order) {
                $this->member_msg(L('订单不存在'));
            }

            // 权限验证
            if (!IS_ADMIN && $order['sell_uid'] != $this->uid) {
                $this->member_msg(L('您无权限操作此订单'));
            }

            if (is_file($file)) {
                require $file;
            } else {
                $this->msg(L('订单打印模板不存在'));
            }
        }

        exit;
    }

    /**
     * 会员订单管理
     */
    protected function _list($is_sell = 0, $status = -1) {

        if (IS_POST && $_POST['action'] == 'print') {
            $ids = $this->input->post('ids');
            if (!$ids) {
                $this->member_msg(L('您还没有选择呢'));
            }
            $url = dr_member_url('order/sell/print_info', array('id' => @implode(',', $ids)));
            if (strpos($url, 'http') === 0) {
                redirect($url, 'location', '301');exit;
            }
            $this->member_msg(L('正在执行中...'), $url, 2, 2);
            exit;
        }

        if ($is_sell) {
            $this->db->where('sell_uid', $this->uid);
        } else {
            $this->db->where('buy_uid', $this->uid);
        }

        if ($status >= 0) {
            $this->db->where('order_status', (int)$status);
        }

        $field = array(
            'sn' => L('订单号'),
            'title' => L('商品名'),
            'shipping_name' => L('收货人'),
        );

        $param = array(
            'kw' => safe_replace($this->input->get('kw')),
            'field' => $this->input->get('field'),
        );

        // 可用模块
        $module = array();
        $result = $this->get_module(SITE_ID);
        if ($result) {
            foreach ($result as $t) {
                if (isset($this->mconfig['module'][$t['dirname']])
                    && $this->mconfig['module'][$t['dirname']]['use']) {
                    $module[$t['dirname']] = $t;
                }
            }
        }

        // 搜索部分
        if (isset($field[$param['field']]) && $param['kw']) {
            if ($param['field'] == 'title') {
                $this->db->where('`id` IN (select oid from `'.$this->db->dbprefix(SITE_ID.'_order_goods').'` where `title` LIKE "%'.$param['kw'].'%")');
            } else {
                $this->db->where($param['field'], $param['kw']);
            }
        }

        $this->db->order_by('order_time desc');

        if ($this->input->get('action') == 'search') {
            // ajax搜索数据
            $page = max(1, (int)$this->input->get('page'));
            $list = $this->db->limit($this->pagesize, $this->pagesize * ($page - 1))->get(SITE_ID.'_order')->result_array();
            if (!$list) {
                exit('null');
            }
            $this->template->assign(array(
                'list' => $this->_format_info($list),
            ));
            $this->template->display($is_sell ? 'sell_data.html' : 'order_data.html');
        } else {
            $page = (int)$this->input->get('page');
            $list = $this->db->limit($page ? $page * $this->pagesize : $this->pagesize)->get(SITE_ID.'_order')->result_array();
            $this->template->assign(array(
                'page' => max(2, $page + 1),
                'list' => $this->_format_info($list),
                'field' => $field,
                'param' => $param,
                'module' => $module,
                'paytype' => $this->mconfig['paytype'],
                'moreurl' => 'index.php?s=member&mod=order&c='.$this->router->class.'&m='.$this->router->method.'&action=search&'.@http_build_query($param),
            ));
            $this->template->display($is_sell ? 'sell_index.html' : 'order_index.html');
        }
    }

    // 后台搜索时
    protected function _admin_list($name, $status = 0, $transfer = 0) {

        if (IS_POST) {
            $_GET['page'] = $_GET['total'] = 0; // 重置页数和统计
            if ($_POST['action'] == 'print') {
                $ids = $this->input->post('ids');
                if (!$ids) {
                    $this->admin_msg(L('您还没有选择呢'));
                }
                $this->admin_msg(L('正在执行中...'), dr_url('order/home/print_info', array('id' => @implode(',', $ids))), 2, 2);
            } elseif ($_POST['action'] == 'del') {
                $ids = $this->input->post('ids');
                if ($ids) {
                    foreach ($ids as $id) {
                        $this->db->where('id', $id)->delete(SITE_ID.'_order');
                        $this->db->where('id', $id)->delete(SITE_ID.'_order_data_0');
                        $this->db->where('oid', $id)->delete(SITE_ID.'_order_buy');
                        $this->db->where('oid', $id)->delete(SITE_ID.'_order_goods');
                        $this->db->where('oid', $id)->delete(SITE_ID.'_order_operate');
                        $this->db->where('oid', $id)->delete(SITE_ID.'_order_transfer');
                    }
                }
                exit(dr_json(1, L('操作成功，正在刷新...')));
            }
        }

        // 接收参数
        $param = $this->input->get(NULL, TRUE);
        unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);

        list($list, $param) = $this->models('Module/order')->limit_page($param, $status, $transfer); // 分页查询

        // 存储当前页URL
        $this->_set_back_url('order/home/index', $param, 'order/home/'.$this->router->method);

        $menu = array(
            $name => array('order/admin/'.$this->router->class.'/'.$this->router->method, 'shopping-cart')
        );

        $this->field = array(
            'sn' => array(
                'name' => L('订单号'),
                'fieldname' => 'sn',
            ),
            'title' => array(
                'name' => L('商品名'),
                'fieldname' => 'title',
            ),
            'cid' => array(
                'name' => L('商品Id'),
                'fieldname' => 'cid',
            ),
            'buy_username' => array(
                'name' => L('购买者'),
                'fieldname' => 'buy_username',
            ),
            'sell_username' => array(
                'name' => L('出售者'),
                'fieldname' => 'sell_username',
            ),
            'shipping_name' => array(
                'name' => L('收货人'),
                'fieldname' => 'shipping_name',
            ),
            'shipping_phone' => array(
                'name' => L('收货电话'),
                'fieldname' => 'shipping_phone',
            ),
            'id' => array(
                'name' => L('储存Id'),
                'fieldname' => 'id',
            ),
        );

        // 可用模块
        $module = array();
        $result = $this->get_module(SITE_ID);
        if ($result) {
            foreach ($result as $t) {
                if (isset($this->mconfig['module'][$t['dirname']])
                    && $this->mconfig['module'][$t['dirname']]['use']) {
                    $module[$t['dirname']] = $t;
                }
            }
        }

        $this->template->assign(array(
            'list' => $this->_format_info($list),
            'menu' => $this->get_menu_v3($menu),
            'param' => $param,
            'field' => $this->field,
            'pages'	=> $this->get_pagination(dr_url('order/'.$this->router->class.'/'.$this->router->method, $param), $param['total']),
            'module' => $module,
            'paytype' => $this->mconfig['paytype'],
            'is_transfer' => $transfer,
        ));
        $this->template->display('order_index.html');
    }
}

// 兼容判断
if (!function_exists('dr_store_url')) {
    function dr_store_url($uid) {
        return dr_space_url($uid);
    }
}
