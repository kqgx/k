<?php

class Admin_model extends CI_Model {
    
    public $prefix; // 表头
    public $tablename; // 表

    public $pagesize = 10; // 默认分页条数
    public $cache = 0; // 查询缓存
     
    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix('admin');
        $this->tablename = $this->prefix.'_auth';
    }
    
    public function module($dir) {
        $this->prefix = $this->link->dbprefix('admin');
        $this->tablename = $this->prefix.'_auth';
    }


    /**
     * 管理员权限验证
     *
     * @param	int	$adminid	管理员id
     * @return	bool
     */
    public function is_admin_auth($adminid) {

        $role = $this->dcache->get('role');
        $role = $role ? $role : $this->models('admin/auth')->cache();

        if ($adminid == 1) {
            return TRUE;
        }

        return @in_array(SITE_ID, $role[$adminid]['site']) ? TRUE : FALSE;
    }
    
    public function install(){
        
    }
    
    public function uninstall(){
        
    }
}
