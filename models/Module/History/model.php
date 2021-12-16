<?php

class Module_History_model extends CI_Model {
    
    public $prefix;
    public $tablename;
    
    public $pagesize = 10;
    public $cache = 0;
    
    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix(SITE_ID . '_'. APP_DIR);
        $this->tablename = $this->prefix.'_history';
    }
    
    public function module($dir) {
        $this->prefix = $this->link->dbprefix($dir);
        $this->tablename = $this->prefix.'_history';
    }

    public function get() {
        return $this->result();
    }
    
    public function all(){
        return $this->result();
    }
    
    public function add($array) {
        return $this->replace($array);
    }
    
    public function install() {
        $this->link->query(trim("
            CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
              `cid` int(10) NOT NULL,
              `eid` int(10) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `name` varchar(255) NOT NULL,
              `thumb` varchar(255) NOT NULL,
              `uid` int(10) NOT NULL,
              `_inputtime` int(10) NOT NULL,
              `_updatetime` int(10) DEFAULT NULL,
              UNIQUE KEY `eid` (`eid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		"));		
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}