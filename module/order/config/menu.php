<?php

return array(

	'icon' => 'icon-shopping-cart',
	// 后台菜单部分
	
	'admin' => array(
		array(
			'name' => '订单管理',
			'icon' => 'fa fa-shopping-cart',
			'menu' => array(
				array(
					'name' => '全部',
					'uri' => 'admin/home/index',
					'icon' => 'icon-shopping-cart',
				),
				array(
					'name' => '待付款',
					'uri' => 'admin/home/fk',
					'icon' => 'icon-jpy',
				),
				array(
					'name' => '待发货',
					'uri' => 'admin/home/fh',
					'icon' => 'fa fa-truck',
				),
                array(
                    'name' => '交易完成',
                    'uri' => 'admin/home/wc',
                    'icon' => 'fa fa-check-circle-o',
                ),
                array(
                    'name' => '交易关闭',
                    'uri' => 'admin/home/close',
                    'icon' => 'fa fa-frown-o',
                ),
			),
		),
		array(
			'name' => '资金管理',
			'icon' => 'icon-jpy',
			'menu' => array(
				array(
					'name' => '转账受理',
					'uri' => 'admin/transfer/index',
					'icon' => 'icon-jpy',
				),
			),
		),
		array(
			'name' => '订单配置',
			'icon' => 'icon-cog',
			'menu' => array(
				array(
					'name' => '订单配置',
					'uri' => 'admin/mconfig/index',
					'icon' => 'icon-certificate',
				),
				array(
					'name' => '付款方式',
					'uri' => 'admin/mconfig/paytype',
					'icon' => 'icon-jpy',
				),
				array(
					'name' => '通知提醒',
					'uri' => 'admin/mconfig/notice',
					'icon' => 'icon-bullhorn',
				),
				array(
					'name' => '模块配置',
					'uri' => 'admin/mconfig/module',
					'icon' => 'icon-cog',
				),
				array(
					'name' => '权限配置',
					'uri' => 'admin/mconfig/permission',
					'icon' => 'icon-group',
				),
				array(
					'name' => '自定义字段',
					'uri' => 'admin/field/index/rname/module/rid/{id}',
					'icon' => 'icon-plus-sign-alt',
				),
			),
		),

	),

    //  会员菜单部分

    'member' => array(
        array(
            'name' => '订单管理',
			'icon' => 'fa fa-shopping-cart',
            'menu' => array(
                array(
                    'name' => '我的订单',
                    'uri' => 'home/index',
                    'icon' => 'fa fa-shopping-cart',
                ),
            )
        ),
		array(
			'name' => '商家订单',
			'icon' => 'fa fa-cart-plus',
			'menu' => array(
				array(
					'name' => '全部',
					'uri' => 'sell/index',
					'icon' => 'fa fa-shopping-cart',
				),
				array(
					'name' => '待付款',
					'uri' => 'sell/fk',
					'icon' => 'fa fa-paypal',
				),
				array(
					'name' => '待发货',
					'uri' => 'sell/fh',
					'icon' => 'fa fa-truck',
				),
				array(
					'name' => '交易完成',
					'uri' => 'sell/wc',
					'icon' => 'fa fa-check-circle-o',
				),
				array(
					'name' => '交易关闭',
					'uri' => 'sell/close',
					'icon' => 'fa fa-frown-o',
				),
			)
		),
    ),

);