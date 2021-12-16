<?php

class Module_Favorite_model extends CI_Model {

    public $prefix;
    public $tablename;

    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix(SITE_ID.'_'.APP_DIR);
        $this->tablename = $this->prefix.'_favorite';
    }

    public function module($dir, $siteid = SITE_ID) {
        $this->prefix = $this->link->dbprefix($siteid.'_'.$dir);
        $this->tablename = $this->prefix.'_favorite';
    }

    public function get($uid) {
        return $this->where('uid', $uid)->result();
    } 
    
    public function status($id){
        return ;
    }
    
    public function add($uid, $id){
        
    }
    
    public function edit(){
        
    }
    
    public function del(){
        
    }
    
    public function install() {
        
        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
              `cid` int(10) unsigned NOT NULL COMMENT '文档id',
              `eid` int(10) unsigned DEFAULT NULL COMMENT '扩展id',
              `uid` mediumint(8) unsigned NOT NULL COMMENT 'uid',
              `url` varchar(255) NOT NULL COMMENT 'URL地址',
              `title` varchar(255) NOT NULL COMMENT '标题',
              `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
              PRIMARY KEY (`id`),
              KEY `uid` (`uid`),
              KEY `cid` (`cid`),
              KEY `eid` (`eid`),
              KEY `inputtime` (`inputtime`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='收藏夹表';
		"));
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}