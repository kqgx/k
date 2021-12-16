<?php

class Form extends M_controller {
    
    public $form;
    
    public function __construct() {
        parent::__construct();
        $form = str_replace('form_', '', $this->input->get('form'));
        if($form){
            $this->form = is_numeric($form) ? $this->get_cache('form-'.SITE_ID, $form) : $this->get_cache('form-name-'.SITE_ID, $form);
            if (!$this->form) {
    			$this->msg(0, L('表单不存在'));
    		}
        }
    }
    
    public function index(){
		!$this->form['setting']['post'] && (IS_POST ? $this->msg(0, L('此表单没有开启前端提交功能')) : $this->msg(0, L('此表单没有开启前端提交功能')));
		if (IS_POST) {
			$this->add();
		} else {
            $tpl = dr_tpl_path('form_'.$this->form['table'].'.html');
			$this->render(array(
				'form' => $this->form,
				'code' => $this->form['setting']['code'],
				'myfield' => $this->new_field_input($this->form['field']),
				'meta_title' => $this->form['name'].SITE_SEOJOIN.SITE_NAME
			), is_file($tpl) ? basename($tpl) : 'form.html');
		}            
    }

    public function show() {
        $id = (int)$this->input->get('id');
        $table = $this->models('site/form')->prefix.'_'.$this->form['table'];
        // 获取表单数据
        $data = $this->models('site/form')->get_data($id, $table);

        !$data && $this->msg(L('表单内容不存在'));
        $tpl = dr_tpl_path('form_'.$this->form['table'].'_show.html');
        $this->render(array_merge($data, array(
            'form' => $this->form,
            'meta_title' => $this->form['name'].SITE_SEOJOIN.SITE_NAME
        )), is_file($tpl) ? basename($tpl) : 'form_show.html');
	}
	
    public function add(){
		$this->form['setting']['code'] && !$this->check_captcha('code') && exit($this->msg(0, L('验证码不正确')));
		$data = $this->validate_filter($this->form['field']);
		// 验证出错信息
		isset($data['error']) && exit($this->msg($data['msg']));
        $data[1]['uid'] =$data[0]['uid'] = $this->uid;
		$data[1]['author'] = $this->uid ? $this->member['username'] : 'guest';
		$data[1]['inputip'] = $this->input->ip_address();
		$data[1]['inputtime'] = SYS_TIME;
		$data[1]['displayorder'] = 0;
		$data[1]['id'] = $id = $this->models('site/form')->new_addc($this->form['table'], $data);
		$this->msg(1, L('操作成功'));
    }
}