<?php

class Ueditor extends M_Controller
{
    public $result;
    public $config; 
    
    public function __construct(){
        parent::__construct();
        if (!$this->uid) {
            x_echo(json_encode(array('state' => L('会话超时，请重新登录'))));
        }
        if (!$this->member['adminid'] && !$this->member_rule['is_upload']) {
            x_echo(json_encode(array('state' => L('您的会员组无权上传附件'))));
        }
        if (!$this->member['adminid'] && $this->member_rule['attachsize']) {
            $data = $this->db->select_sum('filesize')->where('uid', $this->uid)->get('attachment')->row_array();
            $filesize = (int) $data['filesize'];
            if ($filesize > $this->member_rule['attachsize'] * 1024 * 1024) {
                x_echo(json_encode(array('state' => dr_lang('附件空间不足！您的附件总空间%s，现有附件%s', $this->member_rule['attachsize'] . 'MB', dr_format_file_size($filesize)))));
            }
        }
        define('DR_UE_PATH', SYS_UPLOAD_PATH);
        if (!is_dir(SYS_UPLOAD_PATH)) {
            file_mkdirs(SYS_UPLOAD_PATH);
        }
        $this->config = json_decode(preg_replace("/\\/\\*[\\s\\S]+?\\*\\//", "", file_get_contents(LIBRARIES . "Ueditor/config.json")), true);
        $this->result = array('state' => '网络错误');
    }
    
    public function __destruct(){
        $this->response();
    }
    
    public function index(){
        switch ($_GET['action']) {
            case 'config':
                $this->result = $this->config;
                break;
            case 'uploadimage':
            case 'uploadscrawl':
            case 'uploadvideo':
            case 'uploadfile':
                $this->result = (include LIBRARIES . "Ueditor/action_upload.php");
                break;
            case 'listimage':
                $this->result = (include LIBRARIES . "Ueditor/action_list.php");
                break;
            case 'listfile':
                $this->result = (include LIBRARIES . "Ueditor/action_list.php");
                break;
            case 'catchimage':
                break;
            default:
                $this->result = array('state' => '请求地址出错');
                break;
        }
    }
    
    public function response(){
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\\w_]+\$/", $_GET["callback"])) {
                x_echo(htmlspecialchars($_GET["callback"]) . '(' . $this->result . ')');
            } else {
                x_echo(json_encode(array('state' => 'callback参数不合法')));
            }
        } else {
            x_echo(json_encode($this->result));
        }
    }
}