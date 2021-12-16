<?php
 
class Api extends M_Controller {

    /**
     * 后台站点切换
     */
    public function aslogin() {

        $code = dr_authcode(str_replace(' ', '+', $this->input->get('code')));
        !$code && exit('解密失败');

        list($uid, $password) = explode('-', $code);

        $admin = $this->cache->file->get('admin_login_site_select');
        !$admin && exit('缓存失败');

        $admin = string2array($admin);
        !$admin && exit('admin结构不正确');

        $mycode = md5($admin['uid'].$admin['password']);
        $password != $mycode && exit('验证失败');

        // 存储状态
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

        // 保存会话
        $this->session->set_userdata('uid', $uid);
        $this->session->set_userdata('admin', $uid);
        $this->input->set_cookie('member_uid', $uid, 86400);
        $this->input->set_cookie('member_cookie', substr(md5(SYS_KEY . $admin['password']), 5, 20), 86400);

        exit;
    }


    /**
     * 二维码
     */
    public function qrcode() {

        // 输出图片
        ob_start();
        ob_clean();
        header("Content-type: image/png");
        ImagePng(qrcode_get($this->input->get('text', true), (int)$this->input->get('uid', true), $this->input->get('level', true), $this->input->get('size', true), $this->input->get('margin', true)));
        exit;
    }

    /**
     * 会员登录信息JS调用
     */
    public function member() {
        ob_start();
        $this->template->display('member.html');
        $html = ob_get_contents();
        ob_clean();
		$format = $this->input->get('format', true);
		// 页面输出
		if ($format == 'jsonp') {
			$data = $this->callback_json(array('html' => $html));
			echo safe_replace($this->input->get('callback', TRUE)).'('.$data.')';
		} elseif ($format == 'json') {
			echo $this->callback_json(array('html' => $html));
		} else {
			echo 'document.write("'.addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html)).'");';
		}
        exit;
    }

    /**
     * 保存浏览器定位坐标
     */
    public function position() {

        $value = safe_replace($this->input->get('value', true));
        $cookie = get_cookie('my_position');
        if ($cookie != $value) {
            set_cookie('my_position', $value, 999999);
            exit('ok');
        }
        exit('none');
    }

    /**
     * 保存浏览器定位城市
     */
    public function city() {

        $value = safe_replace(str_replace(array('自治区', '自治县', '自治州', '市','县', '州'), '', $this->input->get('value', true)));
        $cookie = get_cookie('my_city');
        if ($cookie != $value) {
            set_cookie('my_city', $value, 999999);
            exit('ok');
        }
        exit('none');
    }

    /**
     * 会员登录信息JS调用
     */
    public function userinfo() {
        ob_start();
        $this->template->display('api.html');
        $html = ob_get_contents();
        ob_clean();
        $html = addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html));
        echo 'document.write("'.$html.'");';
    }

    /**
     * 自定义信息JS调用
     */
    public function template() {
        $this->api_template();
    }

    /**
     * ajax 动态调用
     */
    public function html() {

        ob_start();
        $this->template->cron = 0;
        $_GET['page'] = max(1, (int)$this->input->get('page'));
        $params = string2array(urldecode($this->input->get('params')));
        $params['get'] = @json_decode(urldecode($this->input->get('get')), TRUE);
        $this->template->assign($params);
        $name = str_replace(array('\\', '/', '..', '<', '>'), '', safe_replace($this->input->get('name', TRUE)));
        $this->template->display(strpos($name, '.html') ? $name : $name.'.html');
        $html = ob_get_contents();
        ob_clean();

        // 页面输出
        $format = $this->input->get('format');
        if ($format == 'html') {
            exit($html);
        } elseif ($format == 'json') {
            echo $this->callback_json(array('html' => $html));
        } elseif ($format == 'js') {
            echo 'document.write("'.addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html)).'");';
        } else {
            $data = $this->callback_json(array('html' => $html));
            echo safe_replace($this->input->get('callback', TRUE)).'('.$data.')';
        }
    }

    /**
	 * 更新浏览数
	 */
	public function hits() {
	
	    $id = (int)$this->input->get('id');
	    $dir = safe_replace($this->input->get('module', TRUE));
        $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
        if (!$mod) {
            $data = $this->callback_json(array('html' => 0));
            echo safe_replace($this->input->get('callback', TRUE)).'('.$data.')';exit;
        }

        // 获取主表时间段
        $data = $this->db
                     ->where('id', $id)
                     ->select('hits,updatetime')
                     ->get($this->db->dbprefix(SITE_ID.'_'.$dir))
                     ->row_array();
        $hits = (int)$data['hits'] + 1;

        // 更新主表
		$this->db->where('id', $id)->update(SITE_ID.'_'.$dir, array('hits' => $hits));

        // 获取统计数据
        $total = $this->db->where('id', $id)->get($this->db->dbprefix(SITE_ID.'_'.$dir.'_hits'))->row_array();
        if (!$total) {
            $total['day_hits'] = $total['week_hits'] = $total['month_hits'] = $total['year_hits'] = 1;
        }

        // 更新到统计表
        $this->db->replace($this->db->dbprefix(SITE_ID.'_'.$dir.'_hits'), array(
            'id' => $id,
            'hits' => $hits,
            'day_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', SYS_TIME)) ? $hits : 1,
            'week_hits' => (date('YW', $data['updatetime']) == date('YW', SYS_TIME)) ? ($total['week_hits'] + 1) : 1,
            'month_hits' => (date('Ym', $data['updatetime']) == date('Ym', SYS_TIME)) ? ($total['month_hits'] + 1) : 1,
            'year_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', strtotime('-1 day'))) ? $hits : $total['year_hits'],
        ));

        // 点击时的钩子
        $this->hooks->call_hook('module_hits', array(
            'id' => $id,
            'dir' => $dir,
        ));
        // 输出数据
        echo safe_replace($this->input->get('callback', TRUE)).'('.$this->callback_json(array('html' => $hits)).')';exit;
	}

    /**
	 * 更新扩展的浏览数
	 */
	public function ehits() {

	    $id = (int)$this->input->get('id');
	    $dir = $this->input->get('module', TRUE);
        $mod = $this->get_cache('module-'.SITE_ID.'-'.$dir);
        if (!$mod) {
            $data = $this->callback_json(array('html' => 0));
            echo safe_replace($this->input->get('callback', TRUE)).'('.$data.')';exit;
        }

        $name = 'ehits'.$dir.SITE_ID.$id;
        $hits = (int)$this->get_cache_data($name);
		if (!$hits) {
			$data = $this->db->where('id', $id)->select('hits')->get(SITE_ID.'_'.$dir.'_extend')->row_array();
			$hits = (int)$data['hits'];
		}

		$hits++;
		$this->set_cache_data($name, $hits, (int)SYS_CACHE_MSHOW);

		$this->db->where('id', $id)->update(SITE_ID.'_'.$dir.'_extend', array('hits' => $hits));
        if ($mod['share']) {
            $this->db->where('id', $id)->update(SITE_ID.'_'.$dir.'_extend', array('hits' => $hits));
        }
        // 点击时的钩子
        $this->hooks->call_hook('extend_hits', array(
            'id' => $id,
            'dir' => $dir,
        ));
        $data = $this->callback_json(array('html' => $hits));
        echo safe_replace($this->input->get('callback', TRUE)).'('.$data.')';exit;
	}
	
	/**
	 * 发送桌面快捷方式
	 */
	public function desktop() {
		
		$site = (int)$this->input->get('site');
		$module = $this->input->get('module', true);
		
		if ($site && !$module) {
			$url = $this->site_info[$site]['SITE_URL'];
			$name = $this->site_info[$site]['SITE_NAME'].'.url';
		} elseif ($site && $module) {
			$mod = $this->get_cache('module-'.$site.'-'.$module);
			$url = $mod['url'];
			$name = $mod['name'].'.url';
		}  else {
			$url = $this->site_info[SITE_ID]['SITE_URL'];
			$name = $this->site_info[SITE_ID]['SITE_NAME'].'.url';
		}
		
		$data = "
		[InternetShortcut]
		URL={$url}
		IconFile={$url}favicon.ico
		Prop3=19,2
		IconIndex=1
		";
		$mime = 'application/octet-stream';
		
		header('Content-Type: "' . $mime . '"');
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
		header('Pragma: no-cache');
		header("Content-Length: " . strlen($data));
		echo $data;
	}
	
	/**
	 * 伪静态测试
	 */
	public function test() {
		header('Content-Type: text/html; charset=utf-8');
		echo '服务器支持伪静态';
	}
	
	/**
	 * 自定义数据调用
	 */
	public function data2() {

        //$data = array('msg' => '接口关闭，请参考新方法', 'code' => 0);


        // 安全码认证
        $auth = $this->input->get('auth', true);


        // 解析数据
        $param = $this->input->post_get('param');

        switch ($param) {

            case 'login':
                // 移动端登录认证
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $code = $this->models('member/login')->login(
                    $this->input->get('username', true),
                    $this->input->get('password', true),
                    0, 1);
                if (is_array($code)) {
                    $data = array(
                        'msg' => 'ok',
                        'code' => 1,
                        'return' => $this->models('member')->get_member($code['uid'])
                    );
                } elseif ($code == -1) {
                    $data = array('msg' => L('会员不存在'), 'code' => 0);
                } elseif ($code == -2) {
                    $data = array('msg' => L('密码不正确'), 'code' => 0);
                } elseif ($code == -3) {
                    $data = array('msg' => L('Ucenter注册失败'), 'code' => 0);
                } else {
                    $data = array('msg' => L('未知错误'), 'code' => 0);
                }
                break;

            case 'send_sms_code':
                // 发送短信验证码
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $post = $_REQUEST['data'];
                if (!$post) {
                    $data = array('msg' => L('没有获取到data数组'), 'code' => 0);
                } elseif (!$post['mobile']) {
                    $data = array('msg' => L('没有获取到手机号码'), 'code' => 0);
                } else {
                    $code = dr_randcode();
                    $rt = $this->models('system/sms')->send($post['mobile'], L('尊敬的用户，您的本次验证码是：%s', $code));
                    if ($rt['status']) {
                        $post['uid'] && $this->db->where('uid', (int)$post['uid'])->update('member', array('randcode' => $code));
                        $data = array('msg' => $rt['msg'], 'code' => 1, 'data' => $code);
                    } else {
                        $data = array('msg' => L('发送失败'), 'code' => 0);
                    }
                }
                break;

            case 'send_sms':
                //发送短信
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $post = $_REQUEST['data'];
                if (!$post) {
                    $data = array('msg' => L('没有获取到data数组'), 'code' => 0);
                } elseif (!$post['mobile']) {
                    $data = array('msg' => L('没有获取到手机号码'), 'code' => 0);
                } elseif (!$post['content']) {
                    $data = array('msg' => L('没有获取到短信内容'), 'code' => 0);
                } else {
                    $rt = $this->models('system/sms')->send($post['mobile'], $post['content']);
                    if ($rt) {
                        $data = array('msg' => $rt['msg'], 'code' => $rt['status']);
                    } else {
                        $data = array('msg' => L('发送失败'), 'code' => 0);
                    }
                }
                break;

            case 'send_email':
                // 发送邮件
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $post = $_REQUEST['data'];
                if (!$post) {
                    $data = array('msg' => L('没有获取到data数组'), 'code' => 0);
                } elseif (!$post['email']) {
                    $data = array('msg' => L('没有获取到邮件地址'), 'code' => 0);
                } elseif (!$post['title']) {
                    $data = array('msg' => L('没有获取到邮件标题'), 'code' => 0);
                } elseif (!$post['content']) {
                    $data = array('msg' => L('没有获取到邮件内容'), 'code' => 0);
                } else {
                    $rt = $this->models('system/email')->send($post['email'], $post['title'], $post['content']);
                    if ($rt) {
                        $data = array('msg' => L('发送成功'), 'code' => 1);
                    } else {
                        $data = array('msg' => L('发送失败'), 'code' => 0);
                    }
                }
                break;

            case 'update_avatar':
                //更新头像
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $uid = (int)$_REQUEST['uid'];
                $file = $_REQUEST['file'];
                //
                // 创建图片存储文件夹
                $dir = SYS_UPLOAD_PATH.'/member/'.$uid.'/';
                @dr_dir_delete($dir);
                if (!is_dir($dir)) {
                    file_mkdirs($dir);
                }
                $file = str_replace(' ', '+', $file);
                if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $file, $result)){
                    $new_file = $dir.'0x0.'.$result[2];
                    if (in_array(strtolower($result[2]), array('jpg', 'jpeg', 'png'))) {
                        if (!@file_put_contents($new_file, base64_decode(str_replace($result[1], '', $file)))) {
                            $data = array(
                                'msg' => '目录权限不足或磁盘已满',
                                'code' => 0
                            );
                        } else {
                            list($width, $height, $type, $attr) = getimagesize($new_file);
                            if (!$type) {
                                $data = array(
                                    'msg' => '错误的文件格式，请传输图片的字符',
                                    'code' => 0
                                );
                                unlink($new_file);
                            } else {
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
                                        $data = array(
                                            'msg' => $this->image_lib->display_errors(),
                                            'code' => 0
                                        );
                                        unlink($new_file);
                                        unlink($config['new_image']);
                                        break;
                                    }
                                }
                            }
                        }
                    } else {
                        $data = array(
                            'msg' => '图片格式不规范，请使用标准扩展名',
                            'code' => 0
                        );
                    }
                } else {
                    $data = array(
                        'msg' => '图片字符串不规范，请使用base64格式',
                        'code' => 0
                    );
                }

                // 更新头像
                if (!isset($data['code'])){
                    $data = array(
                        'code' => 1,
                        'msg' => '更新成功'
                    );
                }
                break;

            case 'upload':
                // 文件上传接口
                $this->_api_nologin_auth($auth, 1); // 安全验证
                if (!isset($_FILES['file']['name'])) {
                    if (!$_FILES) {
                        $data = array('msg' => '不是正确的文件上传请求，请检查请求参数是否正确', 'code' => 0);
                    } else {
                        $data = array('msg' => 'file值不存在，请检查请求参数是否正确', 'code' => 0);
                    }
                } else {
                    $uid = (int)$this->input->get('uid');
                    $member = $this->models('member')->get_base_member($uid);
                    if ($member) {
                        $ext = strtolower($this->input->get('ext'));
                        if (!$ext) {
                            $data = array('msg' => '文件扩展名不存在', 'code' => 0);
                        } elseif (in_array($ext, array('php', 'asp', 'aspx'))) {
                            $data = array('msg' => '文件扩展名不合法', 'code' => 0);
                        } else {
                            // 开始上传处理
                            $dir = SYS_UPLOAD_PATH.'/';
                            $path = 'app/'.date('Ym', SYS_TIME);
                            $name = substr(md5('app'.$uid.SYS_TIME.rand(0, 9999)), rand(0, 10), 12);
                            if (!is_dir($dir.$path.'/')) {
                                mkdir($dir.$path.'/');
                            }
                            if (!is_dir($dir.$path.'/')) {
                                $data = array('msg' => '服务器无法创建上传目录：'.$dir.$path.'/', 'code' => 0);
                            } else {
                                $path = $path.'/'.$name.'.'.$ext;
                                if (!move_uploaded_file($_FILES['file']['tmp_name'], $dir.$path)) {
                                    $data = array('msg' => '文件'.$dir.$path.'创建失败', 'code' => 0);
                                } else {
                                    // 判断是否是正确的图片
                                    if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
                                        $img = getimagesize($dir.$path);
                                        if (!$img) {
                                            $data = array('msg' => '上传文件不是一个正确的图片（'.$ext.'）', 'code' => 0);
                                            @unlink($dir.$path);
                                        }
                                    }
                                    if (!$data) {
                                        // 入库附件
                                        $file = file_get_contents($dir.$path);
                                        $this->db->replace('attachment', array(
                                            'uid' => $uid,
                                            'siteid' => 1,
                                            'author' => $member['username'],
                                            'tableid' => 0,
                                            'related' => '',
                                            'fileext' => $ext,
                                            'filemd5' => $file ? md5($file) : 0,
                                            'download' => 0,
                                            'filesize' => strlen($file),
                                        ));
                                        $id = $this->db->insert_id();
                                        // 增加至未使用附件表
                                        $this->db->replace('attachment_unused', array(
                                            'id' => $id,
                                            'uid' => $uid,
                                            'siteid' => 1,
                                            'author' => $member['username'],
                                            'remote' => 0,
                                            'fileext' => $ext,
                                            'filename' => $name,
                                            'filesize' => strlen($file),
                                            'inputtime' => SYS_TIME,
                                            'attachment' => $path,
                                            'attachinfo' => '',
                                        ));
                                        $data = array(
                                            'id' => $id,
                                            'url' => SYS_ATTACHMENT_URL.$path,
                                            'code' => 1,
                                            'msg' => '上传成功',
                                        );
                                    } else {
                                        $data = array('msg' => '入库失败', 'code' => 0);
                                        @unlink($dir.$path);
                                    }
                                }
                            }
                        }
                    } else {
                        $data = array('msg' => '会员不存在', 'code' => 0);
                    }
                }
                break;

            case 'function':
                // 执行函数
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $name = $this->input->get('name', true);
                if (strpos($name, 'dr_') !== 0) {
                    $data = array('msg' => '函数名必须以dr_开头的自定义函数', 'code' => 0);
                } elseif (function_exists($name)) {
                    $_param = array();
                    $_getall = $this->input->get(null, true);
                    if ($_getall) {
                        for ($i=1; $i<=10; $i++) {
                            if (isset($_getall['p'.$i])) {
                                $_param[] = $_getall['p'.$i];
                            } else {
                                break;
                            }
                        }
                    }
                    $data = array('msg' => '', 'code' => 1, 'result' => call_user_func_array($name, $_param));
                } else {
                    $data = array('msg' => '函数 （'.$name.'）不存在', 'code' => 0);
                }
                break;

            case 'get_file':
                // 获取文件地址
                $this->_api_nologin_auth($auth, 1); // 安全验证
                $info = get_attachment((int)$this->input->get('id'));
                if (!$info) {
                    $data = array('msg' => L('附件不存在'), 'code' => 0, 'url' => '');
                } else {
                    $data = array('msg' => '', 'code' => 1, 'url' => dr_get_file($info['attachment']));
                }
                break;

            default:
                // 普通通用查询接口
                $this->_api_nologin_auth($auth, 0); // 安全验证
                $param = dr_safe_list_tag(urldecode($param));
                $data = $this->template->list_tag($param);
                if ($data['error']) {
                    $data['code'] = 0;
                    $data['msg'] = dr_clearhtml($data['error']);
                } else {
                    $data['code'] = 1;
                    $data['msg'] = '';
                }
                unset($data['sql'], $data['debug'], $data['pages'], $data['error']);
                break;


        }

        $this->_api_nologin_call($data);
	}

    public function run_remote_cron() {

        $file = WEBPATH.'cache/file/'.md5($_GET['id']).'.auth';
        if (!is_file($file)) {
            #log_message('error', '线程任务auth文件'.$file.'不存在：'.dr_now_url());
            exit('线程任务auth文件不存在'.$file);
        }

        $value = string2array(file_get_contents($file));
        @unlink($file);
        $time = intval($value['time']);
        if (SYS_TIME - $time > 500) {
            // 500秒外无效
            log_message('error', '线程任务auth过期：'.dr_now_url());
            exit('线程任务auth过期');
        }

        $config = $value['param']['config'];
        if (!is_array($config)) {

            log_message('error', '解析数组config失败 '.dr_now_url());
            exit('解析数组失败');
        }

        $local = ($value['param']['local']);
        $_file = ($value['param']['_file']);

        if ($config['type'] == 1) {
            // ftp附件模式
            $this->load->library('ftp');
            if ($this->ftp->connect(array(
                'port' => $config['value']['port'],
                'debug' => FALSE,
                'passive' => $config['value']['pasv'],
                'hostname' => $config['value']['host'],
                'username' => $config['value']['username'],
                'password' => $config['value']['password'],
            ))) {
                // 连接ftp成功
                $dir = basename(dirname($file)).'/';
                $path = $config['value']['path'].'/'.$dir;
                $file = basename($file);
                $this->ftp->mkdir($path);

                if ($this->ftp->upload($local, $path.$_file, $config['value']['mode'], 0775)) {
                    unlink($local);
                }
                $this->ftp->close();
            } else {
                log_message('error', '远程附件ftp模式：ftp连接失败');
            }
        } elseif ($config['type'] == 2) {
            // 百度云存储模式
            require_once LIBRARIES.'Remote/BaiduBCS/bcs.class.php';
            $bcs = new BaiduBCS($config['value']['ak'], $config['value']['sk'], $config['value']['host']);
            $opt = array();
            $opt['acl'] = BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_WRITE;
            $opt['curlopts'] = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 1800);

            $response = $bcs->create_object($config['value']['bucket'], '/' . $_file, $local, $opt);
            if ($response->status == 200) {
                unlink($local);
            } else {
                log_message('error', '远程附件百度云存储失败');
            }
        } elseif ($config['type'] == 3) {
            // 阿里云存储模式
            require_once LIBRARIES.'Remote/AliyunOSS/sdk.class.php';
            $oss = new ALIOSS($config['value']['id'], $config['value']['secret'], $config['value']['host']);
            $oss->set_debug_mode(FALSE);

            $response = $oss->upload_file_by_file($config['value']['bucket'], $_file, $local);
            if ($response->status == 200) {
                unlink($local);
            } else {
                log_message('error', '远程附件阿里云存储模式：' . $response->body);
            }
        } elseif ($config['type'] == 4) {
            // 腾讯云存储模式

            require_once LIBRARIES.'Remote/QcloudCOS/Qcloud.php';
            Conf::init(
                $config['value']['qcloud_app'],
                $config['value']['qcloud_id'],
                $config['value']['qcloud_key']
            );
            Cosapi::setRegion($config['value']['qcloud_region']);

            $result = Cosapi::upload($config['value']['qcloud_bucket'], $local, '/' . $_file);
            if ($result['code'] == 0) {
                unlink($local);
            } else {
                log_message('error', '远程附件腾讯云存储模式：' . $result['message']);
            }

        } else {
            log_message('error', '远程附件类别（#'.(int)$config['type'].'）未定义');
        }



        log_message('error', '远程附件异步存储成功：' . (int)$_GET['id']);
    }

    /**
     * 站点间的同步登录
     */
    public function synlogin() {
        $this->api_synlogin();
    }

    /**
     * 站点间的同步退出
     */
    public function synlogout() {
        $this->api_synlogout();
    }
    
    /**
     * 验证码
     */
    public function captcha() {
        $this->load->library('captcha');
        $this->captcha->width = $this->input->get('width') ? $this->input->get('width') : 80;
        $this->captcha->height = $this->input->get('height') ? $this->input->get('height') : 30;
        $this->session->unset_userdata('captcha');
        $this->session->set_userdata('captcha', $this->captcha->get_code());
        $this->captcha->doimage();
    }
}
