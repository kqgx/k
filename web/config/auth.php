<?php

/**
 *  后台权限控制
 *
 */

$config['auth'][] = array(
	'auth' => array(
		'admin/html/index' => L('生成静态'),
		'admin/notice/index' => L('系统提醒'),
		'admin/cache/index' => L('清理缓存'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/system/oplog' => L('日志'),
        'admin/db/sql' => L('执行SQL'),
		'admin/system/index' => L('系统配置'),
		'admin/system/file' => L('分离存储'),
        'admin/db/index' => L('数据结构'),
		'admin/upgrade/index' => L('内核升级'),
		'admin/upgrade/branch' => L('程序升级'),
        'admin/check/index' => L('系统体检'),
        'admin/route/index' => L('生成伪静态'),
		'admin/cron/index' => L('任务队列'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/mail/index' => L('邮件系统'),
		'admin/mail/add' => L('添加'),
		'admin/mail/edit' => L('修改'),
		'admin/mail/del' => L('删除'),
		'admin/mail/send' => L('发送邮件'),
		'admin/mail/log' => L('日志'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/sms/index' => L('短信系统'),
		'admin/sms/send' => L('发送短信'),
		'admin/sms/log' => L('日志'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'menu/admin/index' => L('后台菜单'),
		'menu/admin/add' => L('添加'),
		'menu/admin/edit' => L('修改'),
		'menu/admin/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'adminsysvarindex' => L('全局变量'),
		'adminsysvaradd' => L('添加'),
		'adminsysvaredit' => L('修改'),
		'adminsysvardel' => L('删除'),
	)
);
$config['auth'][] = array(
	'auth' => array(
		'admin/syscontroller/index' => L('自定义控制器'),
		'admin/syscontroller/add' => L('添加'),
		'admin/syscontroller/edit' => L('修改'),
		'admin/syscontroller/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/attachment2/index' => L('远程附件'),
		'admin/attachment2/add' => L('添加'),
		'admin/attachment2/edit' => L('修改'),
		'admin/attachment2/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/role/index' => L('角色管理'),
		'admin/role/auth' => L('权限划分'),
		'admin/role/add' => L('添加'),
		'admin/role/edit' => L('修改'),
		'admin/role/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/root/index' => L('管理员管理'),
		'admin/root/log' => L('登录日志'),
		'admin/root/add' => L('添加'),
		'admin/root/edit' => L('修改'),
		'admin/root/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/verify/index' => L('审核流程'),
		'admin/verify/add' => L('添加'),
		'admin/verify/edit' => L('修改'),
		'admin/verify/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/site/index' => L('网站管理'),
		'admin/site/add' => L('添加'),
		'admin/site/config' => L('配置'),
		'admin/site/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/field/index' => L('字段管理'),
		'admin/field/add' => L('添加'),
		'admin/field/edit' => L('修改'),
		'admin/field/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/application/index' => L('应用管理'),
		'admin/application/store' => L('商店'),
		'admin/application/config' => L('配置'),
		'admin/application/install' => L('安装'),
		'admin/application/uninstall' => L('卸载'),
		'admin/application/delete' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/module/index' => L('模块管理'),
		'admin/module/store' => L('商店'),
		'admin/module/install' => L('安装'),
		'admin/module/uninstall' => L('卸载'),
		'admin/module/delete' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/attachment/index' => L('附件管理'),
		'admin/attachment/unused' => L('未使用的附件'),
		'admin/attachment/del' => L('删除'),
	)
);


$config['auth'][] = array(
	'auth' => array(
		'admin/page/index' => L('单页'),
		'admin/page/add' => L('添加'),
		'admin/page/edit' => L('修改'),
		'admin/page/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/tag/index' => L('关键词库'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/linkage/index' => L('联动菜单'),
		'admin/linkage/add' => L('添加'),
		'admin/linkage/edit' => L('修改'),
		'admin/linkage/data' => L('子菜单'),
		'admin/linkage/adds' => L('添加子菜单'),
		'admin/linkage/edits' => L('修改子菜单'),
		'admin/linkage/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/block/index' => L('资料块'),
		'admin/block/add' => L('添加'),
		'admin/block/edit' => L('修改'),
		'admin/block/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/navigator/index' => L('链接'),
		'admin/navigator/add' => L('添加'),
		'admin/navigator/edit' => L('修改'),
		'admin/navigator/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/form/index' => L('表单管理'),
		'admin/form/add' => L('添加'),
		'admin/form/edit' => L('修改'),
		'admin/form/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/downservers/index' => L('下载镜像'),
		'admin/downservers/add' => L('添加'),
		'admin/downservers/edit' => L('修改'),
		'admin/downservers/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/tpl/index' => L('模板管理'),
		'admin/tpl/mobile' => L('移动端模板'),
		'admin/tpl/add' => L('添加'),
		'admin/tpl/edit' => L('修改'),
		'admin/tpl/del' => L('删除'),
		'admin/tpl/tag' => L('标签向导'),
	)
);

$config['auth'][] = array(
	'name' => L('风格管理'),
	'auth' => array(
		'admin/theme/index' => L('风格管理'),
		'admin/theme/add' => L('添加'),
		'admin/theme/edit' => L('修改'),
		'admin/theme/del' => L('删除'),
	)
);

$config['auth'][] = array(
    'name' => L('URL规则'),
    'auth' => array(
        'admin/urlrule/index' => L('URL规则'),
        'admin/urlrule/add' => L('添加'),
        'admin/urlrule/edit' => L('修改'),
        'admin/urlrule/del' => L('删除'),
    )
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/home/index' => L('会员管理'),
		'member/admin/home/add' => L('添加'),
		'member/admin/home/edit' => L('修改'),
		'member/admin/home/del' => L('删除'),
        'member/admin/home/score' => SITE_SCORE,
		'member/admin/home/experience' => SITE_EXPERIENCE,
	)
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/group/index' => L('会员组模型'),
		'member/admin/group/add' => L('添加'),
		'member/admin/group/edit' => L('修改'),
		'member/admin/group/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/level/index' => L('等级管理'),
		'member/admin/level/add' => L('添加'),
		'member/admin/level/edit' => L('修改'),
		'member/admin/level/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/setting/oauth' => 'OAuth2',
		'member/admin/setting/index' => L('功能配置'),
		'member/admin/setting/permission' => L('权限划分'),
		'member/admin/setting/pay' => L('网银配置'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'menu/member/admin/index' => L('会员菜单'),
		'menu/member/admin/add' => L('添加'),
		'menu/member/admin/edit' => L('修改'),
		'menu/member/admin/del' => L('删除'),
	)
);


$config['auth'][] = array(
	'auth' => array(
		'member/admin/tpl/index' => L('模板管理'),
        'member/admin/tpl/mobile' => L('移动端模板'),
		'member/admin/tpl/add' => L('添加'),
		'member/admin/tpl/edit' => L('修改'),
		'member/admin/tpl/del' => L('删除'),
		'member/admin/tpl/tag' => L('标签向导'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/theme/index' => L('风格管理'),
		'member/admin/theme/add' => L('添加'),
		'member/admin/theme/edit' => L('修改'),
		'member/admin/theme/del' => L('删除'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/pay/index' => L('财务流水'),
		'member/admin/pay/add' => L('充值'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'member/admin/member/index' => L('用户管理'),
		'member/admin/member/setting' => L('用户设置'),
		'member/admin/member/permission' => L('用户权限'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'admin/admin/index' => L('管理员管理'),
	)
);

$config['auth'][] = array(
	'auth' => array(
		'order/admin/home/index' => L('全部'),
		'order/admin/home/fk' => L('待完成'),
		'order/admin/home/wc' => L('交易完成'),
		'order/admin/home/close' => L('交易关闭'),
	)
);