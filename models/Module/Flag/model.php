<?php

class Module_Flag_model extends CI_Model {

    public $link;
    public $prefix;
    public $tablename;

    public function __construct() {
        parent::__construct();
        $this->prefix = $this->db->dbprefix($siteid.'_'.APP_DIR);
        $this->tablename = $this->prefix.'_flag';
    }

    public function module($dir, $siteid = SITE_ID) {
        $this->link = $this->db;
        $this->prefix = $this->db->dbprefix($siteid.'_'.$dir);
        $this->tablename = $this->prefix.'_flag';
    }
    
    public function install() {
        
        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
			  `flag` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '文档标记id',
			  `id` int(10) unsigned NOT NULL COMMENT '文档内容id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
			  `catid` tinyint(3) unsigned NOT NULL COMMENT '分类id',
			  KEY `flag` (`flag`,`id`,`uid`),
			  KEY `catid` (`catid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='标记表';
		"));		
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}