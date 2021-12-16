<?php

class Module_Hits_model extends CI_Model {

    public $link;
    public $prefix;
    public $tablename;

    public function __construct() {
        parent::__construct();
    }

    public function module($dir, $siteid = SITE_ID) {
        $this->link = $this->db;
        $this->prefix = $this->db->dbprefix($siteid.'_'.$dir);
        $this->tablename = $this->prefix.'_hits';
    }
    
	public function add() {
	}
	
    public function install() {
        $this->link->query(trim("
            CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
			  `id` int(10) unsigned NOT NULL COMMENT '文章id',
			  `hits` int(10) unsigned NOT NULL COMMENT '总点击数',
			  `day_hits` int(10) unsigned NOT NULL COMMENT '本日点击',
			  `week_hits` int(10) unsigned NOT NULL COMMENT '本周点击',
			  `month_hits` int(10) unsigned NOT NULL COMMENT '本月点击',
			  `year_hits` int(10) unsigned NOT NULL COMMENT '年点击量',
			  UNIQUE KEY `id` (`id`),
			  KEY `day_hits` (`day_hits`),
			  KEY `week_hits` (`week_hits`),
			  KEY `month_hits` (`month_hits`),
			  KEY `year_hits` (`year_hits`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='时段点击量统计';
		"));		
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}