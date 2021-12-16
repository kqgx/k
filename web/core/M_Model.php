<?php

class CI_Model {

    public $ci;
    public $db;
    public $link;
    
    public $param = array();
    
    public function __construct()
    {
        $this->ci = & get_instance();
        $this->db = & $this->ci->db;
        $this->link = $this->ci->db;
    }

    public function __get($key)
    {
        return $this->ci->$key;
    }
    
    public function models($model){
        if($model){
            $model = ucfirst($model);
            $path = '';
            if (($last_slash = strrpos($model, '/')) !== FALSE)
            {
                $path = substr($model, 0, ++$last_slash);
                $model = ucfirst(substr($model, $last_slash));
            }
            $name =  ($path ? str_replace('/', '_', $path): '') . $model.'_model';
            $_this =& get_instance();
            if(!(is_array($_this->_ci_models) && in_array($model, $_this->_ci_models))){
                $this->load->model($path.$model);
            }
            return $_this->$name;            
        }
    }
    
    // 条件
    public function where($key, $value = NULL)
    {
        if(is_array($key)){
            foreach ($key as $k=>$v) {
                $this->where($k, $v);
            }
        } else {
            $this->param[$key] = $value;
        }
        return $this;
    }

    // 分页大小
    public function pagesize($pagesize)
    {
        $this->pagesize = $pagesize;
    }

    // 第几页
    private function page($page)
    {
        $this->page = $page;
    }

    // 分页
    public function pages($url, $total)
    {
        return _pages_build($url, $total, $this->pagesize);
    }

    /* 
        结果 
    */ 
    public function count()
    {
        return $this->db->count_all_results($this->tablename);
    }

    public function filed(){
        
    }

    public function result()
    {
        $result = array();
        
        $page = (int)max(1, $this->input->get('page'));
        $total = (int)$this->input->get('total');
        $pagesize = (int)$this->input->get('pagesize');
        if($pagesize>0 && $pagesize<10000){
            $pagesize && $this->pagesize = $pagesize;
        }
        
        if (!$total) {
            $this->param && $this->_where($this->param);
            $total = $this->db->count_all_results($this->tablename);
        }
        
        $result['total'] = $total;
        
        $this->db->limit($this->pagesize, $this->pagesize*($page-1));
        $this->param && $this->_where($this->param);
        $result['list'] = $this->db->get($this->tablename)->result_array();
        
        return $result;
    }

    public function rows(){

        $this->param && $this->_where($this->param);
        $result = $this->db->get($this->tablename)->result_array();
        
        return $result;
    }

    public function one(){

        $this->param && $this->_where($this->param);
        $result = $this->db->get($this->tablename)->row_array();
        
        return $result;
    }

    /* 
        执行
    */    
    public function update($array)
    {
        if(!isset($data['_updatetime'])){
            $array['_updatetime'] = SYS_TIME;
        }
        $this->db->update($this->tablename, $array);    
    }

    public function insert($array)
    {
        if(!isset($array['_inputtime'])){
            $array['_inputtime'] = SYS_TIME;
        }
        $this->db->insert($this->tablename, $array); 
    }

    public function replace($array){
        if(!isset($data['_inputtime'])){
            $array['_inputtime'] = SYS_TIME;
        }
        $this->db->replace($this->tablename, $array);        
    }

    // public function delete(){
        
    // }

    /* 
        过程 
    */
    private function _where($key, $value = NULL){
        $this->db->where($key, $value);
    }

    private function _order_by($key, $value = 'DESC')
    {
        $this->db->order_by($key, $value);
    }

    private function _where_in($key = NULL, $values = NULL, $escape = NULL)
    {
        $this->db->where_in($key, $values, $escape);
    }

    private function _where_not_in()
    {
        
    }
}
