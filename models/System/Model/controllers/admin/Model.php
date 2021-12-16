<?php

class Model extends M_Controller {
    
    public function __construct(){
        parent::__construct();
    }
    
    public function index(){
        $this->template->display();
    }
    
    public function add(){
        $field = '';
        $this->template->assign(array(
                'field' => $this->new_field_input($field)
            ));
        $this->template->display();
    }
    
    public function install(){
        
    }
    
    public function uninstall(){
        
    }
    
    public function cache(){
        
    }
}