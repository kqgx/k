<?php

class Buy extends M_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function add(){
        if(IS_POST){
            $get = $this->input->get();
            $post = $this->input->post();
            $param = array(
                    'cid' => $get['id']
                );
            if(!$this->models('Module/buy')->where($this->_build_param($param))->one()){
                $staus = 1;
                $array = array(
                        'uid' => $this->uid,
                        'cid' => $get['id'],
                        'title' => $post['title'],
                        'thumb' => $post['thumb'],
                        'status' => $staus,
                        'token' => md5($get['id'].$this->uid.$staus)
                    );
                $this->models('Module/buy')->add($array);
                $this->msg(1, '报名成功');
            } else {
                $this->msg(0, '已报名');
            }
        }
    }
    
    public function get(){
        $get = $this->input->get();
        if($get['status']){
            $param['status'] = $get['status'];
        }
        $result = $this->models('Module/buy')->where($this->_build_param($param))->result();
        $this->render(array(
            'list' => $result['list'],
            'pages'	=> $this->models('Module/buy')->pages(url_build("member/activity/buy/index"), $result['total']),
        ), 'buy_index.html');
    }
    
    public function status(){
        $get = $this->input->get();
        $param = array(
                'cid' => $get['id']
            );
        $this->json($this->models('Module/buy')->where($this->_build_param($param))->one());
    }
    
    public function edit(){
        
    }
    
    public function remove(){
        
    }
    
    public function clear(){
        
    }
    
    public function options(){
        
    }
    
    private function _build_param($array = array()){
        if(!isset($array['uid'])){
            $array['uid'] = $this->uid;
        }
        return $array;
    }
}