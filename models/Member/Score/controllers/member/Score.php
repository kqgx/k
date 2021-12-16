<?php

class Score extends M_Controller {
    
    
    public function __construct(){
        parent::__construct();
    }
    
    public function log(){
		$this->_log($type = (int)$this->input->get('type'));
    }
    
    public function _log($type){

        $name = array(
            0 => L(SITE_EXPERIENCE.'记录'),
            1 => L(SITE_SCORE.'记录'),
        );
        
        $result = $this->models('member/score')->get($this->uid, $type);
        
		$this->render(array(
            'list' => $result['list'],
            'pages'	=> $this->models('member/score')->pages(url_build("member/score/log/type/{$type}"), $result['total']),			
            'meta_name' => $name[$type]
		), 'score.html');
    }
}