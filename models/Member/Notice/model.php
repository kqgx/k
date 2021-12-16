<?php

class Member_Notice_model extends CI_Model {
    
    public $prefix;
    public $tablename;
    
    public $pagesize = 10;
    public $cache = 0;
    
    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('member');
        $this->tablename = $this->prefix.'_notice';
    }
    
    public function module($dir) {
        $this->prefix = $this->link->dbprefix($dir);
        $this->tablename = $this->prefix.'_notice';
    }

    public function get($uid, $type){
        return $this->where('uid', $uid)->where('type', $type)->result();
    }   
    
    public function set_read($uid, $id){
    }
    
    public function set_read_all($uid, $type){
        // 更新新提醒
        $this->link->where('uid', $uid)->where('type', $type)->update($this->tablename, array('isnew' => 0));
        // 删除新提醒
        $this->link->where('uid', $uid)->delete('member_new_notice');    
    }
    
    
    public function delete($uid, $id){
        $this->link->where('uid', $uid)->where('id', $id)->delete($this->tablename);
    }
    /**
     * 添加一条通知
     *
     * @param	string	$uid
     * @param	intval	$type 1系统，2互动，3模块，4应用
     * @param	string	$note
     * @return	null
     */
    public function add($uid, $type, $note) {
        if (!$uid || !$note) {
            return NULL;
        }
        $uids = is_array($uid) ? $uid : explode(',', $uid);
        foreach ($uids as $uid) {
            $this->link->insert('member_notice', array(
                'uid' => $uid,
                'type' => $type,
                'isnew' => 1,
                'content' => $note,
                'inputtime' => SYS_TIME,
            ));
            $this->link->replace('member_new_notice', array('uid' => $uid));
        }
        return NULL;
    }

    /**
     * 后台提醒
     *
     * @param	type    system系统  content内容相关  member会员相关 app应用相关
     * @param	msg     提醒内容
     * @param	uri     后台对应的链接
     * @param	to      通知对象 留空表示全部对象
     * array(
     *      to_uid 指定人
     *      to_rid 指定角色组
     * )
     */
    public function add_admin($type, $msg, $uri, $to = array()) {
        $this->link->insert('admin_notice', array(
            'type' => $type,
            'msg' => $msg,
            'uri' => $uri,
            'to_rid' => intval($to['to_rid']),
            'to_uid' => intval($to['to_uid']),
            'status' => 0,
            'uid' => 0,
            'username' => '',
            'updatetime' => 0,
            'inputtime' => SYS_TIME,
        ));
    }
    
    public function install(){
        
    }
    
    public function uninstall(){
    
        
    }
}