<?php

class D_Search extends M_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
        $result = $this->models('Module/search');
        $this->render($result, 'search.html');
    }
    
    public function where(){
        
        $param = $this->_validate_param();
        
        foreach ($param as $key=>$value) 
        {
            // order_by
            if($key=='order_by' && $value)
            {
                $this->link->order_by(str_replace('_', ' ', $value));
            // like
            } else if (strpos('%', $value) === 0) 
            {
                $this->link->like($key, $value);
            // BETWEEN
            } else if (strpos('><', $value) === 0) 
            {
               $array = explode('><', $value);
               $this->link->where($key . ' BETWEEN ' . $array[0] . ' AND ' . $array[1]);
            // <>
            } else if (strpos('<>', $value) === 0) 
            {
               $this->link->where($key.'<>', $value);
            // >
            }else if (strpos('>', $value) === 0) 
            {
               $this->link->where_in($key.'>=', $value);
            // <
            }else if (strpos('<', $value) === 0) 
            {
               $this->link->where($key.'<=', $value);
            // IN
            } else if (strpos('IN', $value) === 0) 
            {
               $this->link->where_in($key.'<', $value);
            // NOT_IN
            } else if (strpos('NOTIN', $value) === 0) 
            {
               $this->link->where_not_in($key, $value);
            // =
            } else {
                $this->link->where($key, $value);
            }
        }
    }
    
    private function _validate_param(){
        $param = $this->input->get(NULL, TRUE);
        $filter = $this->_filed();
        foreach ($param as $k => $v) {
            // 黑名单
            if(in_array($k, $filter)){
                unset($param[$k]);
            }
            // 白名单
            
        }
        return $param;
    }
    
    private function _filed(){
        // 后期改查数据库
        return array('page', 'pagesize', 'total', 'limit');
    }
}