<?php

class Account extends M_Controller {

    /**
     * 基本资料
     */
    public function index() {

        $MEMBER = $this->get_cache('member');
        $error = NULL;
        $field = array(
            'name' => array(
                'name' => L('姓名'),
                'ismain' => 0,
                'ismember' => 1,
                'fieldname' => 'name',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                    'validate' => array(
                        'xss' => 1,
                        'required' => 1,
                    )
                )
            ),
            'phone' => array(
                'name' => L('手机号码'),
                'ismain' => 0,
                'ismember' => 1,
                'fieldname' => 'phone',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                    'validate' => array(
                        'xss' => 1,
                        'check' => '_check_phone',
                        'required' => 1,
                        'isedit' => @in_array('phone', $MEMBER['setting']['regfield']) ? 1 : 0,
                    )
                )
            ),
        );

        // 可用字段
        if ($MEMBER['field'] && $MEMBER['group'][$this->member['groupid']]['allowfield']) {
            foreach ($MEMBER['field'] as $t) {
                in_array($t['fieldname'], $MEMBER['group'][$this->member['groupid']]['allowfield']) && $field[] = $t;
            }
        }

        if (IS_POST) {
            // 快捷登录组完善资料
            if (!isset($data['error']) && $this->member['groupid'] == 2) {
                $post = $this->input->post('member');
                $data['email'] = $post['email'];
                $data['username'] = $post['username'];
                $code = $this->models('member/register')->add($post, NULL, $this->uid);
                if ($code > 0) {
				} else {
				    $error = $this->models('member/register')->error_msg($code);
				}
                if (isset($error)) {
                    $this->member_msg($error, dr_member_url('account/index'));
                } else {
                    $this->member_msg(L('完善资料成功'), dr_member_url('account/index'), 1);
                }
            } else {
                $data = $this->validate_filter($field, $this->member);
                // 邮箱验证
                $email = safe_replace($this->input->post('email', TRUE));
                if ($email) {
                    if (!preg_match('/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/', $email)) {
                        $data = array('error' => 'email', 'msg' => L('邮箱格式不正确'));
                    } elseif ($this->db->where('email', $email)->count_all_results('member')) {
                        $data = array('error' => 'email', 'msg' => L('该邮箱【%s】已经被注册', $email));
                    } else {
                        $data[0]['email'] = $email;
                    }
                }
                if (isset($data['error'])) {
                    $error = $data;
                    $data = $this->input->post('data', TRUE);
                    unset($data['uid']);
                    $input = $this->input->post('member', TRUE);
                } else {
                    $result = $this->models('member')->edit($data[0], $data[1]);
                    $this->attachment_handle($this->uid, $this->db->dbprefix('member').'-'.$this->uid, $field, $this->member);
                    $this->member_msg(L('操作成功，正在刷新...'), dr_member_url('account/index'), 1);
                }
            }
            $data['phone'] = $this->member['phone'];
        } else {
            $data = $this->member;
        }

        unset($data['password'], $data['salt']);

        $this->render(array_merge($data, array(
            'field' => $field,
            'input' => $input,
            'myfield' => $this->field_input($field, $data, FALSE, 'uid'),
            'regfield' => $MEMBER['setting']['regfield'],
            'result_error' => $error
        )), 'account_index.html');
    }

    // 手机号码修改
    public function phone() {

        $error = NULL;
        $field = array(

            'phone' => array(
                'name' => L('新手机号码'),
                'ismain' => 0,
                'ismember' => 1,
                'fieldname' => 'phone',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                    'validate' => array(
                        'xss' => 1,
                        'check' => '_check_phone',
                        'required' => 1,
                        'isedit' => 0,
                    )
                )
            ),
        );

        $field['check'] = array(
            'name' => L('短信验证码'),
            'ismain' => 0,
            'ismember' => 1,
            'fieldname' => 'check',
            'fieldtype' => 'Text',
            'setting' => array(
                'option' => array(
                    'width' => 117,
                ),
                'validate' => array(
                    'xss' => 1,
                )
            )
        );

        $field['phone']['setting']['validate']['append'] = '<label style="padding-left:10px"><a class="btn btn-xs blue" onclick="dr_send_sms()"> <i class="fa fa-send"></i> '.L('短信验证码').'</a></label>';



        if (IS_POST) {

            // 快捷登录组完善资料

                $data = $this->validate_filter($field, $this->member);
                $cache = $this->session->userdata('send_msg_phone');
                if (!$cache) {
                    $data = array('error' => 'check', 'msg' => L('短信验证码过期'));
                } elseif ($cache != $data[0]['phone']) {
                    $data = array('error' => 'check', 'msg' => L('手机号码和验证码不匹配'));
                } else if ($this->db->where('uid<>', $this->uid)->where('phone', $data[0]['phone'])->count_all_results('member')) {
                    $data = array('error' => 'phone', 'msg' => L('该手机号码已经注册'));
                } elseif ($data[0]['check'] && $data[0]['check'] != $this->member['randcode']) {
                    $data = array('error' => 'check', 'msg' => L('短信验证码不正确'));
                } elseif (!$data[0]['check']) {
                    $data = array('error' => 'check', 'msg' => L('短信验证码未填写'));
                }

                if (isset($data['error'])) {
                    $error = $data;
                    (IS_AJAX || IS_API_AUTH) && $this->msg(0, $error['msg'], $error['error']);
                    $data = array(
                        'phone' => $data[0]['phone'],
                    );
                } else {
                    $data = array(
                        'phone' => $data[0]['phone'],
                    );
                    $this->db->where('uid', $this->uid)->update('member', array(
                        'phone' => $data['phone'],
                        'randcode' => 0,
                    ));
                    $this->member_msg(L('操作成功，正在刷新...'), dr_member_url('account/phone'), 1);
                }
        } else {
            $data = array();
        }

        $this->render(array(
            'myfield' => $this->field_input($field, $data, FALSE, 'uid'),
            'result_error' => $error
        ), 'account_phone.html');
    }

    /**
     * 登录记录
     */
    public function login() {
        $this->load->library('dip');
        $this->template->display('account_login.html');
    }

    /**
     * OAuth
     */
    public function oauth() {
        $this->template->assign(array(
            'list' => $this->member['Oauth'],
        ));
        $this->template->display('account_oauth.html');
    }

    /**
     * 修改密码
     */
    public function password() {

        $error = 0;

        if (IS_POST) {

            $password = safe_replace($this->input->post('password'));
            $password1 = safe_replace($this->input->post('password1'));
            $password2 = safe_replace($this->input->post('password2'));

            if (!$password1 || $password1 != $password2) {
                $error = L('两次密码输入不一致');
            } elseif ($password == $password2) {
                $error = L('不能与原密码相同');
            } elseif (md5(md5($password).$this->member['salt'].md5($password)) != $this->member['password']) {
                $error = L('当前密码不正确');
            }

            if ($error === 0) {
                $this->db->where('uid', $this->uid)->update('member', array(
                    'password' => md5(md5($password1).$this->member['salt'].md5($password1))
                ));
                $this->hooks->call_hook('member_edit_password', array('member' => $this->member, 'password' => $password1));
                $this->member_msg(L('密码修改成功'), dr_member_url('account/password'), 1);
            }

            (IS_AJAX || IS_API_AUTH) && $this->msg(0, $error);
        }

        $this->render(array(
            'result_error' => $error
        ), 'account_password.html');
    }

    /**
     * 密码校验
     */
    public function cpassword() {
        $password = safe_replace($this->input->post('password'));
        echo md5(md5($password).$this->member['salt'].md5($password)) == $this->member['password'] ? '' : L('旧密码不正确');
    }

    /**
     * 上传头像
     */
    public function avatar() {
        $this->template->display('account_avatar.html');
    }

    // 头像返回处理
    private function _avatar_return($msg, $swf = 0) {
        @header('Content-Type: text/html; charset=utf-8');
        exit($msg);
    }

    /**
     *  上传头像处理
     *  传入头像压缩包，解压到指定文件夹后删除非图片文件
     */
    public function upload() {

        $post = file_get_contents('php://input');

        // 创建图片存储文件夹
        $dir = dr_upload_temp_path().'member/'.$this->uid.'/';
        @dr_dir_delete($dir);
        !is_dir($dir) && file_mkdirs($dir);

        $swf = 0;
        !$post && $this->_avatar_return('环境php://input不支持');
        $tx = $this->input->post('tx');
        if ($tx) {
            $file = str_replace(' ', '+', $tx);
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $file, $result)){
                $new_file = $dir.'0x0.'.$result[2];
                if (!in_array(strtolower($result[2]), array('jpg', 'jpeg', 'png', 'gif'))) {
                    exit($this->_avatar_return('目录权限不足'));
                }
                if (!@file_put_contents($new_file, base64_decode(str_replace($result[1], '', $file)))) {
                    exit($this->_avatar_return('目录权限不足或磁盘已满'));
                } else {
                    list($width, $height, $type, $attr) = getimagesize($new_file);
                    if (!$type) {
                        @unlink($new_file);
                        exit($this->_avatar_return('图片字符串不规范'));
                    }
                    $this->load->library('image_lib');
                    $config['create_thumb'] = TRUE;
                    $config['thumb_marker'] = '';
                    $config['maintain_ratio'] = FALSE;
                    $config['source_image'] = $new_file;
                    foreach (array(30, 45, 90, 180) as $a) {
                        $config['width'] = $config['height'] = $a;
                        $config['new_image'] = $dir.$a.'x'.$a.'.'.$result[2];
                        $this->image_lib->initialize($config);
                        if (!$this->image_lib->resize()) {
                            exit($this->_avatar_return($this->image_lib->display_errors()));
                            break;
                        }
                    }
                }
            } else {
                exit($this->_avatar_return('图片字符串不规范'));
            }
        } else {
            exit($this->_avatar_return('图片不存在'));
        }

        $my = SYS_UPLOAD_PATH.'/member/'.$this->uid.'/';
        @dr_dir_delete($my);
        !is_dir($my) && file_mkdirs($my);

        $c = 0;
        if ($fp = @opendir($dir)) {
            while (FALSE !== ($file = readdir($fp))) {
                $ext = substr(strrchr($file, '.'), 1);
                if (in_array(strtolower($ext), array('jpg', 'jpeg', 'png', 'gif'))) {
                    if (copy($dir.$file, $my.$file)) {
                        $c++;
                    }
                }
            }
            closedir($fp);
        }
        if (!$c) {
            exit(iconv('UTF-8', 'GBK', L('未找到目录中的图片')));
        }

        // 更新头像
        $this->db->where('uid', $this->uid)->update('member', array('avatar' => $this->uid));
        exit('1');
    }
}
