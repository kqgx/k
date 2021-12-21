<?php

return array(

	// 后台菜单部分
	
	'admin' => array(
		array(
			'name' => '{name}管理',
			'menu' => array(
				array(
					'name' => '{name}管理',
					'uri' => 'admin/content/index'
				),
				array(
					'name' => '草稿箱',
					'uri' => 'admin/content/draft'
				),
                array(
                    'name' => '回收站',
                    'uri' => 'admin/content/recycle'
                ),
				array(
					'name' => '栏目分类',
					'uri' => 'admin/category/index'
				),
				array(
					'name' => '{name}标签',
					'uri' => 'admin/tag/index'
				)
			),
		),

        array(
            'name' => '评论管理',
            'menu' => array(
                array(
                    'name' => '评论设置',
                    'uri' => 'admin/comment/config'
                ),
                array(
                    'name' => '评论管理',
                    'icon' => 'icon-comments',
                    'uri' => 'admin/comment/index'
                )
            ),
        )
	)
	
);