<?php

class Ui extends M_Controller {

    public function index(){
        $this->template->assign(array(
                'color' => ['default', 'primary', 'info', 'success', 'warning', 'danger'],
                'size' => ['xs', 'sm', 'md', 'lg'],
                'num' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
            ));
        $this->template->display('index.html');
    }
	
}