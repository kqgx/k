<?php

class Score extends M_Controller {
    
    private $userinfo;
    
    public function __construct(){
        parent::__construct();
        $this->template->assign(array(
            'menu' => $this->get_menu_v3(array(
                L('返回') =>  array($this->_get_back_url('member/home/index'), 'reply'),
                SITE_SCORE =>  array('member/score/index/uid/'.$uid, 'star'),
                L('添加') =>  array('member/score/add/uid/'.$uid.'_js', 'plus')
            )),
        ));
    }

    /**
     * 首页
     */
    public function index() {
        $uid = (int)$this->input->get('uid');
        
        if($uid){
            // 根据参数筛选结果
            $param = array('uid' => $uid);
            $this->input->get('search') && $param['search'] = 1;
        }

        // 数据库中分页查询
        list($data, $param)	= $this->models('member/score')->limit_page($param, max((int)$this->input->get('page'), 1), (int)$this->input->get('total'));
        
        $param['uid'] = $uid;

        $_param = $this->input->get('search') ? $this->cache->file->get($this->models('member/score')->cache_file) : $this->input->post('data');
        $_param = $_param ? $param + $_param : $param;

        $this->template->assign(array(
            'list' => $data,
            'param'	=> $_param,
            'pages'	=> $this->get_pagination(dr_url('member/score', $param), $param['total']),
        ));
        $this->template->display('score_index.html');
    }
    
    public function add() {

        if (IS_POST) {
            $data = $this->input->post('data');
            $value = intval($data['value']);
            !$value && $this->msg(0, L('请填写变动数量值'), 'value');
            $member = $this->models('member')->get_member($data['uid']);
            $this->models('member/score')->edit(1, $member['uid'], $value, '', $data['note']);
            $this->models('member/notice')->add($member['uid'], 1, L('%s变动：%s；本次操作人：%s', SITE_SCORE, $value, $this->member['username']));
            $this->system_log('会员【'.$member['username'].'】充值'.SITE_SCORE); // 记录日志
            $this->json(1, L('操作成功，正在刷新...'));
        }

        $this->template->display('score_add.html');
    }
}