<?php

class Module_Buy_model extends CI_Model {

    public $link;
    public $prefix;
    public $tablename;

    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix(SITE_ID.'_'.APP_DIR);
        $this->tablename = $this->prefix.'_buy';
    }

    public function module($APP, $SITE_ID = SITE_ID) {
        $this->prefix = $this->link->dbprefix($SITE_ID.'_'.$APP);
        $this->tablename = $this->prefix.'_buy';
    }

    public function get() {
        return $this->rows();
    }
    
    public function add($array) {
        return $this->insert($array);
    }
    
    public function install() {
        
        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
              `cid` int(10) unsigned NOT NULL COMMENT '内容id',
              `uid` mediumint(8) unsigned NOT NULL COMMENT 'uid',
              `title` varchar(255) NOT NULL COMMENT '标题',
              `thumb` varchar(255) NOT NULL COMMENT '缩略图',
              `url` varchar(255) NOT NULL COMMENT 'URL地址',
              `status` int(10) unsigned NOT NULL COMMENT '状态',
              `_inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
              `_updatetime` int(10) unsigned NOT NULL COMMENT '更新时间',
              PRIMARY KEY (`id`),
              KEY `cid` (`cid`,`uid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容购买记录表';
		"));
		
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}