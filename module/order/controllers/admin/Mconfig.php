<?php

/* v3.1.0  */

class Mconfig extends M_Controller {
	
	private $setting;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $mod = $this->db->where('dirname', 'order')->get('module')->row_array();
        $this->setting = string2array($mod['setting']);
		$this->setting[SITE_ID] = isset($this->setting[SITE_ID]) ? $this->setting[SITE_ID] : array(); // 当前站点的订单配置
    }
	
    /**
     * 订单配置
     */
    public function index() {

        if (IS_POST) {
            $this->setting[SITE_ID]['config'] = $this->input->post('data', true);
            $this->db->where('dirname', 'order')->update('module', array(
                'setting' => array2string($this->setting)
            ));
            $this->admin_msg(L('操作成功'), dr_url('order/mconfig/index', array('page'=>$_POST['page'])), 1);
        }
		
        $this->template->assign(array(
            'page' => (int)$_GET['page'],
            'data' => $this->setting[SITE_ID]['config'],
			'menu' => $this->get_menu_v3(array(
                lang('订单配置') => array('order/admin/mconfig/index', 'cog'),
                L('更新缓存') => array('admin/module/cache/dir/order', 'refresh')
            ))
        ));
        $this->template->display('mconfig_index.html');
    }
	
    /**
     * 模块配置
     */
    public function module() {

        if (IS_POST) {
            $this->setting[SITE_ID]['module'] = $this->input->post('data');
            $this->db->where('dirname', 'order')->update('module', array(
                'setting' => array2string($this->setting)
            ));
            $this->admin_msg(L('操作成功'), dr_url('order/mconfig/'.$this->router->method, array('page'=>$_POST['page'])), 1);
        }
		
		$result = $this->get_module(SITE_ID);
		if ($result) {
			foreach ($result as $t) {
				if ($t['is_system'] && $t['dirname'] != 'store') {
					$module[] = array(
						'icon' => $t['icon'],
						'name' => $t['name'],
						'dirname' => $t['dirname'],
						'fieldurl' => dr_url('field/index', array('rname' => 'module', 'rid' => $t['id'])),
						'is_price' => isset($t['field']['order_price']) && $t['field']['order_price']['ismain'] == 1 ? 1 : 0, // 是否存在价格字段
						'is_volume' => isset($t['field']['order_volume']) && $t['field']['order_volume']['ismain'] == 1 ? 1 : 0, // 是否存在销量统计字段
						'is_quantity' => isset($t['field']['order_quantity']) && $t['field']['order_quantity']['ismain'] == 1 ? 1 : 0, // 是否存在库存数量字段
					);
				}
			}
		} else {
			$module = array();
		}
		
        $this->template->assign(array(
            'page' => (int)$_GET['page'],
            'data' => $this->setting[SITE_ID]['module'],
			'menu' => $this->get_menu_v3(array(
                L('模块配置') => array('order/admin/mconfig/'.$this->router->method, 'cogs'),
                L('更新缓存') => array('admin/module/cache/dir/order', 'refresh')
            )),
            'module' => $module,
        ));
        $this->template->display('mconfig_'.$this->router->method.'.html');
    }


    /**
     * 权限配置
     */
    public function permission() {

        if (IS_POST) {
            $this->setting[SITE_ID][$this->router->method] = $this->input->post('data');
            $this->db->where('dirname', 'order')->update('module', array(
                'setting' => array2string($this->setting)
            ));
            $this->admin_msg(L('操作成功'), dr_url('order/mconfig/'.$this->router->method, array('page'=>$_POST['page'])), 1);
        }
		
		$group = $this->get_cache('member', 'group');
		
        $this->template->assign(array(
            'page' => (int)$_GET['page'],
            'data' => $this->setting[SITE_ID][$this->router->method],
			'menu' => $this->get_menu_v3(array(
                L('权限配置') => array('order/admin/mconfig/'.$this->router->method, 'user'),
                L('更新缓存') => array('admin/module/cache/dir/order', 'refresh'),
            )),
			'group' => $group,
        ));
        $this->template->display('mconfig_'.$this->router->method.'.html');
    }

    /**
     * 付款方式
     */
    public function paytype() {

        if (IS_POST) {
            $this->setting[SITE_ID][$this->router->method] = $this->input->post('data');
            $this->db->where('dirname', 'order')->update('module', array(
                'setting' => array2string($this->setting)
            ));
            $this->admin_msg(L('操作成功'), dr_url('order/mconfig/'.$this->router->method, array('page'=>$_POST['page'])), 1);
        }

        $this->template->assign(array(
            'page' => (int)$_GET['page'],
            'data' => $this->setting[SITE_ID][$this->router->method],
			'menu' => $this->get_menu_v3(array(
                L('付款方式') => array('order/admin/mconfig/'.$this->router->method, 'rmb'),
                L('更新缓存') => array('admin/module/cache/dir/order', 'refresh'),
            )),
        ));
        $this->template->display('mconfig_'.$this->router->method.'.html');
    }

    /**
     * 提醒配置
     */
    public function notice() {

        if (IS_POST) {
            $this->setting[SITE_ID][$this->router->method] = $this->input->post('data');
            $this->db->where('dirname', 'order')->update('module', array(
                'setting' => array2string($this->setting)
            ));
            $this->admin_msg(L('操作成功'), dr_url('order/mconfig/'.$this->router->method, array('page'=>$_POST['page'])), 1);
        }

        $list = array(
            '1' => '买家下单时',
            '2' => '买家付款时',
            '3' => '交易完成时',
            '4' => '商家改价时',
            '5' => '商家发货时',
            //'6' => '退款申请时',
           // '7' => '退款成功时',
            //'6' => '退货申请时',
            //'7' => '退货成功时',
            //'8' => '售后申请时',
        );

        $this->template->assign(array(
            'note' => '标签介绍：手机号码{phone}，订单号{sn}，订单id{id}，时间{time}，订单价格{price}',
            'list' => $list,
            'page' => (int)$_GET['page'],
            'data' => $this->setting[SITE_ID][$this->router->method],
			'menu' => $this->get_menu_v3(array(
                L('订单通知') => array('order/admin/mconfig/'.$this->router->method, 'volume-off'),
                L('更新缓存') => array('admin/module/cache/dir/order', 'refresh'),
            )),
        ));
        $this->template->display('mconfig_'.$this->router->method.'.html');
    }


}