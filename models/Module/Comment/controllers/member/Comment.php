<?php

class Comment extends M_Controller {

    public $_uri;
    public $rname;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    // 设置模块操作评论
    public function module($dir) {
        $this->models('Module/comment')->module($dir);
        $this->_uri = $dir.'/comment/index';
        $this->rname = 'comment-module-'.$dir;
    }

    // 设置模块扩展操作评论
    public function extend($dir) {
        $this->models('Module/comment')->extend($dir);
        $this->_uri = $dir.'/ecomment/index';
        $this->rname = 'comment-module-'.$dir;
    }

    // 条件查询
    private function _where($select, $param) {
        $select->where('uid', $this->uid);
        $param['kw'] && $select->like('title', $param['kw']);
    }

    // 我评论过的信息
    public function index() {

        // 接收参数
        $kw = safe_replace($this->input->get('kw', TRUE));
        $total = (int)$this->input->get('total');
        $order = dr_get_order_string(safe_replace($this->input->get('order', TRUE)), 'id desc');
        
        // 查询结果
        $list = array();
        if (!$total) {
            $select = $this->models('Module/comment')->link->select('count(*) as total');
            $this->_where($select, array('kw' => $kw));
            $data = $select->get($this->models('Module/comment')->prefix.'_comment_my')->row_array();
            $total = (int)$data['total'];
        }

        if ($total) {
            $page = max((int)$this->input->get('page'), 1);
            $select = $this->models('Module/comment')->link->limit($this->pagesize, $this->pagesize * ($page - 1));
            $this->_where($select, array('kw' => $kw));
            $list = $select->order_by($order)->get($this->models('Module/comment')->prefix.'_comment_my')->result_array();
        }

        $this->render(array(
            'list' => $list,
            'pages'	=> $this->get_member_pagination(dr_member_url($this->_uri).'&action=search&kw='.$kw.'&order='.$order.'&total='.$total, $total),
            'meta_name' => L('我评论过的'),
            'page_total' => $total,
        ), is_file(dr_tpl_path($this->rname.'.html')) ? $this->rname.'.html' : 'comment_index.html');
    }
}
