<?php

$config['auth'][] = array(
	'name' => L('内容管理'),
	'auth' => array(
		'admin/home/index' => L('管理'),
		'admin/home/add' => L('添加'),
		'admin/home/edit' => L('修改'),
		'admin/home/del' => L('删除'),
        'admin/home/content' => L('内容维护'),
		'admin/home/html' => L('生成静态'),
		'admin/home/draft' => L('草稿箱'),
	)
);

$config['auth'][] = array(
    'name' => L('内容维护'),
    'auth' => array(
        'admin/content/index' => L('内容维护'),
        'admin/content/url' => L('更新URL'),
        'admin/content/replace' => L('替换内容'),
    )
);

$config['auth'][] = array(
	'name' => L('栏目管理'),
	'auth' => array(
		'admin/category/index' => L('管理'),
		'admin/category/add' => L('添加'),
		'admin/category/edit' => L('修改'),
		'admin/category/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'name' => L('Tag标签'),
	'auth' => array(
		'admin/tag/index' => L('管理'),
		'admin/tag/add' => L('添加'),
		'admin/tag/edit' => L('修改'),
		'admin/tag/del' => L('删除'),
	)
);