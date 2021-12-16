<?php

class Comment extends M_Controller {

    public $_uri; //
    public $curl;
    public $data;
    public $cconfig;
    public $permission;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    // 设置模块操作评论
    public function module($dir) {

        $name = 'comment-module-'.$dir;
        $this->models('Module/comment')->module($dir, $name);

        $cid = (int)$this->input->get('cid');
        $data = $this->models('Module/comment')->get_data($cid);
        
        if(!$data){
            $this->msg(0, L('数据不存在'));
        }

        $this->data = array(
            'cid' => $cid,
            'dir' => $dir,
            'url' => $data['url'],
            'uid' => $data['uid'],
            'catid' => $data['catid'],
            'title' => $data['title'],
        );
        $this->cconfig = $this->get_cache('comment', $name);
        
        $this->permission = $this->cconfig['value']['permission'][$this->markrule];
        unset($this->cconfig['value']['permission'][$this->markrule]);
    }
    
    public function extend($dir){

        $name = 'comment-extend-'.$dir;
        $this->models('Module/comment')->extend($dir, $name);

        $cid = (int)$this->input->get('cid');
        $data = $this->models('Module/comment')->get_data($cid);
        
        if(!$data){
            $this->msg(0, L('数据不存在'));
        }

        $this->data = array(
            'cid' => $cid,
            'dir' => $dir,
            'url' => $data['url'],
            'uid' => $data['uid'],
            'catid' => $data['catid'],
            'title' => $data['title'],
        );
        $this->cconfig = $this->get_cache('comment', $name);
        $this->permission = $this->cconfig['value']['permission'][$this->markrule];
        unset($this->cconfig['value']['permission'][$this->markrule]);        
    }

    // 评论列表
    public function index() {

        $type = (int)$this->input->get('type');
        $order = 'inputtime desc';
        switch ($type) {
            case 1:
                $order = 'inputtime asc';
                break;
            case 2:
                $order = 'support asc';
                break;
            default:
                $_GET['order'] && $order = strtolower(dr_get_order_string($_GET['order'], $order));
                break;
        }

        $page = max(1, (int)$this->input->get('page'));
        list($table, $comment) = $this->models('Module/comment')->get_table($this->data['cid'], 1);

        // 判断字段是否可用
        $temp = trim(str_replace(array(' asc', ' desc'), '', $order));
        $field = $this->get_table_field($table);
        $order = isset($field[$temp]) ? $order : 'inputtime desc';

        $this->cconfig['value']['pagesize'] = max(1, (int)$this->cconfig['value']['pagesize']);
        $data = $this->models('Module/comment')
                    ->link
                    ->where('cid', $this->data['cid'])
                    ->where('reply', 0)
                    ->where('status', 1)
                    ->limit($this->cconfig['value']['pagesize'], $this->cconfig['value']['pagesize'] * ($page - 1))
                    ->order_by($order)
                    ->get($table)->result_array();
        if ($data) {
            foreach ($data as $i => $t) {
                $data[$i]['rlist'] = $t['in_reply'] ? $this->models('Module/comment')->link->where('cid', $this->data['cid'])->where('reply', $t['id'])->where('status', 1)->limit(5)->order_by('inputtime desc')->get($table)->result_array() : array();
            }
        }

        $pages = $this->get_pagination(dr_url($this->_uri), $total);
        $this->render(array(
            'use' => $this->cconfig['value']['use'],
            'type' => $type,
            'page' => $page,
            'list' => $data,
            'curl' => $this->curl,
            'code' => isset($this->permission['code']) && $this->permission['code'],
            'data' => $this->data,
            'catid' => $this->data['catid'],
            'pages' => $pages,
            'myfield' => $this->new_field_input($this->cconfig['field'], NULL, 0, '', $this->cconfig['value']['format']),
            'comment' => $comment,
            'is_reply' => $this->cconfig['value']['reply'],
            'meta_title' => $this->data['title'],
        ), 'comment.html');
    }

    // 发布评论
    public function add() {

        $buy = array();
        $rid =(int)$this->input->get('rid');
        $name = md5($this->uid.$this->curl.'sb');
        $table = $this->models('Module/comment')->get_table($this->data['cid']); // 评论附表

        if (!$this->cconfig['value']['use']) {
            $this->msg(0, L('系统关闭了评论功能'));
        } else if ($this->cconfig['value']['my'] && $this->data['uid'] == $this->uid) {
            $this->msg(0, L('系统禁止对自己评论'));
        } else if ($rid) {
            $row = $this->models('Module/comment')->link->where('cid', $this->data['cid'])->where('id', $rid)->get($table)->row_array();
            if (!$row) {
                $this->msg(0, L('您回复的评论主体不存在'));
            } elseif (!$this->cconfig['value']['reply']) {
                $this->msg(0, L('系统禁止回复功能'));
            } elseif ($this->cconfig['value']['reply'] == 2) {
                if ($this->member['uid'] == $row['uid'] && $row['uid'] == $this->data['uid']) {
                } elseif ($this->member['adminid']) {
                } else {
                    $this->msg(0, L('您无权限回复'));
                }
            }
        } else if (isset($this->permission['disabled']) && $this->permission['disabled']) {
            $this->msg(0, L('您无权限评论'));
        } else if (isset($this->permission['time']) && $this->permission['time'] && $this->session->userdata($name)) {
            $this->msg(0, L('您动作太快了！'));
        } else if ($this->cconfig['value']['buy']) {
            $buy = $this->db
                        ->where('uid', $this->uid)
                        ->where('cid', $this->data['cid'])
                        ->get(SITE_ID.'_'.APP_DIR.'_buy')
                        ->row_array();
            if (!$buy) {
                $this->msg(0, L('您还没有购买，不能评论'));
            } elseif ($buy['comment']) {
                $this->msg(0, L('您已经评论过了，不允许重复评论'));
            }
        } else if ($this->cconfig['value']['num']) {
            if ($this->models('Module/comment')->link->where('cid', $this->data['cid'])->where('uid', $this->uid)->count_all_results($this->models('Module/comment')->prefix.'_comment_my')) {
                $this->msg(0, L('您已经评论过了，请勿再次评论'));
            } elseif ($this->models('Module/comment')->link->where('cid', $this->data['cid'])->where('uid', $this->uid)->count_all_results($table)) {
                $this->msg(0, L('您已经评论过了，请勿再次评论'));
            }
        }

        if (IS_POST) {
            isset($this->permission['code']) && $this->permission['code'] && !$this->check_captcha('code') && $this->msg(0, L('验证码不正确'));
            $my = array();
            if ($this->cconfig['field']) {
                $my = $this->validate_filter($this->cconfig['field']);
                isset($my['error']) && $this->msg(0, $my['msg']);
            }
            $this->data['rid'] = $rid;
            $this->data['content'] = safe_replace($this->input->post('content'));
            empty($this->data['content']) && $this->msg(0, L('请填写评论内容'));
            $this->data['verify'] = $this->member['adminid'] ? 0 : $this->cconfig['value']['verify'];
            $id = $this->models('Module/comment')->add($this->uid, $this->data, $my);
            !$id && $this->msg(0, '评论失败，数据异常');
            isset($this->permission['time']) && $this->permission['time'] && $this->session->set_tempdata($name, 1, $this->permission['time']);
            if ($this->uid) {
                $this->cconfig['field']['content'] = array(
                    'ismain' => 1,
                    'fieldtype' => 'Ueditor',
                    'fieldname' => 'content',
                    'setting' => array(
                        'option' => array(
                            'mode' => 1,
                            'height' => 300,
                            'width' => '100%'
                        )
                    )
                ); 
                $this->data[1]['content'] = $this->data['content']; // 伪装content字段
                $this->attachment_handle(
                    $this->uid,
                    $this->models('Module/comment')->get_table($this->data['cid']).'-'.$id,
                    $this->cconfig['field'],
                    $my
                );
            }
            if ($this->data['verify']) {
                // 需要审核
                $this->msg(1, L('评论成功，需要管理员审核之后才能显示'));
            } else {
                $this->msg(1, L('评论成功'));
            }
        } else {
            $this->msg(0, '数据异常');
        }
    }

    public function options() {

        !$this->cconfig['value']['use'] && $this->msg(0, L('评论功能已关闭'));

        $option = $this->input->get('option');
        $id = (int)$this->input->get('id');
        
        $index = $this->models('Module/comment')
                        ->link
                        ->where('cid', $this->data['cid'])
                        ->get($this->models('Module/comment')->prefix.'_comment_index')
                        ->row_array();
        !$index && $this->msg(0, L('数据异常'));
        
        $table = $this->models('Module/comment')->prefix.'_comment_data_'.intval($index['tableid']);
        
        if ($option == 'delete') {
            !$this->member['adminid'] && $this->msg(0, L('无权限操作'));
            $this->models('Module/comment')->del($id, $this->data['cid'], $index);
            $this->msg(1);
        }
        
        $name = $option.$id.$this->uid; // 验证识别
        
        // 验证操作间隔
        $this->session->userdata($name) && $this->msg(0, L('稍后再试'));
        $data = $this->models('Module/comment')->link->where('id', $id)->get($table)->row_array();
        !$data && $this->msg(0, '数据异常');

        switch ($option) {
            case 'support':
                $num = (int)$data['support'] + 1;
                $this->models('Module/comment')->link->where('id', $id)->set('support', $num)->update($table);
                $this->models('Module/comment')->link->where('id', $index['id'])->set('support', 'support+1', false)->update($this->models('Module/comment')->prefix.'_comment_index');
                $this->session->set_tempdata($name, 1, 3600);
                $this->msg(1, $num);
                break;
            case 'oppose':
                $num = (int)$data['oppose'] + 1;
                $this->models('Module/comment')->link->where('id', $id)->set('oppose', $num)->update($table);
                $this->models('Module/comment')->link->where('id', $index['id'])->set('oppose', 'oppose+1', false)->update($this->models('Module/comment')->prefix.'_comment_index');
                $this->session->set_tempdata($name, 1, 3600);
                $this->msg(1, $num);
                break;
        }
        $this->msg(0, L('数据异常'));
    }
}
