<?php

class Fnyxn_Coordinate_model extends CI_Model {
    
    public $prefix;
    public $tablename;
    
    public $pagesize = 10;
    public $cache = 0;
    
    public $offset = 10000;
    public $space = 0.0005;
    
    public function __construct() 
    {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('fnyxn');
        $this->tablename = $this->prefix.'_coordinate';
    }
    
    // 根据订单坐标获取送水工
    public function get($lng, $lat)
    {
        return $this->db->where('store', $this->getStore($lng, $lat))->get($this->tablename)->result_array();
    }   
    
    // 送水工设置自己当前位置
    public function set($uid, $lng, $lat)
    {
        $this->db->where('uid', $uid)->delete($this->tablename);
        $store = $this->getAllStore($this->getX($lng), $this->getY($lat));
        foreach ($store as $value) {
            $this->db->insert($this->tablename, array(
                    'uid' => $uid,
                    'store' => $value,
                    '_updatetime' => SYS_TIME
                ));
        }
        return $this->db->insert_id();
    }  
    
    // 获取水站ID:X
    public function getX($lng)
    {
        return ceil($lng/$this->_space);
    }
    
    // 获取水站ID:Y
    public function getY($lat)
    {
        return ceil($lat/$this->_space);
    }
    
    public function getStore($lng, $lat){
        return $this->getX($lng)*$this->offset + $this->getY($lat);
    }
    
    // 获取当前及周边八个水站的位置
    public function getAllStore($x, $y)
    {
        $array[0] = $x*$this->offset + $y; // 当前位置
        $array[1] = ($x+1)*$this->offset + $y; // 上
        $array[2] = ($x+1)*$this->offset +($y+1); // 左上
        $array[3] = $x*$this->offset +($y+1); // 左
        $array[4] = ($x-1)*$this->offset +$y; // 下
        $array[5] = ($x-1)*$this->offset +($y-1); // 右下
        $array[6] = $x*$this->offset +($y-1); // 右
        $array[7] = ($x+1)*$this->offset +($y-1); // 左下
        $array[8] = ($x-1)*$this->offset +($y+1); // 右上
        return $array;
    }
    
    public function install() {
        $this->link->query(trim("
			DROP TABLE IF EXISTS `{$this->tablename}`;
            CREATE TABLE `imt_fnyxn_coordinate` (
              `uid` int(11) NOT NULL COMMENT '用户ID',
              `store` int(13) NOT NULL COMMENT '水站ID',
              `_updatetime` int(10) NOT NULL COMMENT '更新时间'
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		"));
    }
    
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->tablename}`");
    }
}