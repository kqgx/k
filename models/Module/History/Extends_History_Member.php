<?php

class Extends_History_Member extends M_controller {

    public function __construct() {
        parent::__construct();
    }
    
    public function add(){
        if(IS_POST){
            $post = $this->input->post();
            $array = array(
                    'uid' => $this->uid,
                    'cid' => $post['cid'],
                    'eid' => $post['eid'],
                    'title' => $post['title'],
                    'name' => $post['name'],
                    'thumb' => $post['thumb']
                );
            $this->models('Module/history')->replace($array);            
        }
    }
    
    public function get(){
        $result = $this->models('Module/history')->where('uid', $this->uid)->get();
        $this->render(array(
            'list' => $result['list'],
            'pages'	=> $this->models('Module/history')->pages(url_build("member/extend/history/index"), $result['total']),
        ), 'history_index.html');
    }
	
	public function clear(){
	    
	}
	
	public function remove(){
	    
	}
}