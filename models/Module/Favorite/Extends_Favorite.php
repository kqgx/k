<?php

class Extends_Favorite extends M_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function add() {

        if (!$this->uid) {
            // 未登录
            if (isset($_GET['jsonp']) && $_GET['jsonp']) {
                echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('code' => '1')).')';exit;
            } else {
                exit('1');
            }
        }

        $is_delete = (int)$this->input->get('delete');

        $id = (int)$this->input->get('id');
        $cid = (int)$this->input->get('cid');
        $mid = $cid ? $cid : $id; // 内容表id
        $eid = $cid ? $id : 0; // 扩展表id
        $data = $this->db->where('id', $mid)->select('url,title')->get(SITE_ID.'_'.APP_DIR)->row_array();
        if (!$data) {
            if (isset($_GET['jsonp']) && $_GET['jsonp']) {
                echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('code' => '2')).')';exit;
            } else {
                exit('2');
            }
        }

        $table = SITE_ID.'_'.APP_DIR.'_favorite';
        $favorite = $this->db->where('cid', $mid)->where('uid', $this->uid)->where('eid', $eid)->select('id')->get($table)->row_array();
        if ($eid) {
            // 收藏扩展表
            $data2 = $this->db->where('cid', $mid)->get(SITE_ID.'_'.APP_DIR.'_extend')->row_array();
            if ($favorite) {
                if ($is_delete) {
                    $this->db->where('id', $favorite['id'])->delete($table);
                } else {
                    $this->db->where('id', $favorite['id'])->update($table, array(
                        'url' => $data2['url'],
                        'title' => $data['title'].' - '.$data2['name']
                    ));
                }

                // 更新成功
                if (isset($_GET['jsonp']) && $_GET['jsonp']) {
                    echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('code' => '3')).')';exit;
                } else {
                    exit('3');
                }
            } else {
                // 添加成功
                $this->db->insert($table, array(
                    'eid' => $eid,
                    'cid' => $mid,
                    'uid' => $this->uid,
                    'url' => $data2['url'],
                    'title' => $data['title'].' - '.$data2['name'],
                    'inputtime' => SYS_TIME,
                ));
                // 更新数量
                $c = $this->db->where('eid', $eid)->count_all_results($table);
                $this->db->where('id', $eid)->set('favorites', $c)->update(SITE_ID.'_'.APP_DIR.'_extend');
                if (isset($_GET['jsonp']) && $_GET['jsonp']) {
                    echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('code' => '4')).')';exit;
                } else {
                    exit('4');
                }
            }
        } else {
            // 收藏主表
            if ($favorite) {
                if ($is_delete) {
                    $this->db->where('id', $favorite['id'])->delete($table);
                } else {
                    $this->db->where('id', $favorite['id'])->update($table, array(
                        'url' => $data['url'],
                        'title' => $data['title']
                    ));
                }

                // 更新成功
                if (isset($_GET['jsonp']) && $_GET['jsonp']) {
                    echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('code' => '3')).')';exit;
                } else {
                    exit('3');
                }
            } else {
                // 添加成功
                $this->db->insert($table, array(
                    'eid' => 0,
                    'cid' => $mid,
                    'uid' => $this->uid,
                    'url' => $data['url'],
                    'title' => $data['title'] ? $data['title'] : '',
                    'inputtime' => SYS_TIME,
                ));
                // 更新数量
                $c = $this->db->where('cid', $mid)->count_all_results($table);
                $this->db->where('id', $mid)->set('favorites', $c)->update(SITE_ID.'_'.APP_DIR);
                if (isset($_GET['jsonp']) && $_GET['jsonp']) {
                    echo safe_replace($this->input->get('callback', TRUE)).'('.json_encode(array('code' => '4')).')';exit;
                } else {
                    exit('4');
                }
            }
        }
    }
    
    public function status(){
        $this->models('Module/favorite');
    }
    
    public function index(){
        
        $result = $this->models('Module/favorite')->get($this->uid);

        $this->render(array(
            'list' => $result['list'],
            'pages'	=> $this->models('Module/favorite')->pages(url_build("member/notice/index/type/{$type}"), $result['total']),
            'metas' => [],
        ), 'module_favorite_index.html');
    }
    
    
}