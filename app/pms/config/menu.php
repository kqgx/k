<?php

/**
 * 管理后台菜单分布
 *
 * array(
 *		'name' => '分组菜单名称',
 *		'menu' => array(
 *			array(
 *				'name' => '链接菜单名称',
 *				'uri' => '链接菜单的uri'
 *			)
 *			......
 *		)
 * )
 * .......
 */

return array(

	// 后台菜单部分
	
	'admin' => array(
		'name' => '',
		'menu' => array(
		),
	),
	
	//  会员菜单部分
	
	'member' => array(
		'name' => '',
		'menu' => array(
			array(
				'name' => '站内消息',
				'uri' => 'home/index',
				'icon' => 'fa fa-envelope'
			)
		)
	),
	
);