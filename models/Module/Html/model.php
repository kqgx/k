<?php

class Module_Html_model extends CI_Model {

    public $link;
    public $rname;
    public $prefix;
    public $tablename;

    /*
     * 评论类
     */
    public function __construct() {
        parent::__construct();
    }

    // 设置模块操作评论
    public function module($dir, $siteid = SITE_ID) {
        $this->link = $this->db;
        $this->prefix = $this->db->dbprefix($siteid.'_'.$dir);
        $this->tablename = $this->prefix.'_html';
    }

    public function install() {
        
        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
			  `id` bigint(18) unsigned NOT NULL AUTO_INCREMENT,
			  `rid` int(10) unsigned NOT NULL COMMENT '相关id',
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `type` tinyint(1) unsigned NOT NULL COMMENT '文件类型',
			  `catid` tinyint(3) unsigned NOT NULL COMMENT '分类id',
			  `filepath` text NOT NULL COMMENT '文件地址',
			  PRIMARY KEY (`id`),
			  KEY `uid` (`uid`),
			  KEY `rid` (`rid`),
			  KEY `cid` (`cid`),
			  KEY `type` (`type`),
			  KEY `catid` (`catid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='html文件存储表';
		"));		
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}