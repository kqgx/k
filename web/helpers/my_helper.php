<?php
function L($string)
{
    $param = func_get_args();
    if (empty($param)) {
        return null;
    }

    // 取第一个作为语言名称
    $string = $param[0];
    unset($param[0]);

    $CI =& get_instance();

    // 调用语言包内容
    $lang = $CI->lang->line($string);
    $string = $lang ? $lang : $string;

    // 替换
    $string = $CI->replace_lang($string);

    $string = $param ? vsprintf($string, $param) : $string;

    return $string;
}

function echo_br($string)
{
    echo $string . '<br>';
}

function x_echo($string)
{
    exit($string);
}

function x_json($code, $data = [], $msg = '')
{
    ob_clean();
    header('Content-type: application/json');
    if (is_array($code)) {
        $msg = $data;
        $data = $code;
        $code = 1;
    } else {
        if (is_string($data)) {
            $msg = $data;
            $data = [];
        }
    }
    x_echo(json_encode(array('code' => $code, 'data' => $data, 'msg' => $msg)));
}

function is_valid($type, $value)
{
    switch ($type) {
        case 'email':
            $preg = '/\\w[-\\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\\.)+[A-Za-z]{2,14}/';
            break;
        case 'phone':
            $preg = '/0?(13|14|15|17|18|19)[0-9]{9}/';
            break;
        case 'qq':
            $preg = '/[1-9]([0-9]{5,11})/';
            break;
        default:
            return false;
            break;
    }
    return preg_match($preg, $value);
}

function is_mobile()
{
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false && (strpos($_SERVER['HTTP_ACCEPT'],
                    'text/html') === false || strpos($_SERVER['HTTP_ACCEPT'],
                    'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))) {
            return true;
        }
    }
    return false;
}

function debug_log($text)
{
    $path = WEBPATH . 'cache/debug/';
    $file = $path . date('y-m-d', SYS_TIME) . '.log';
    if (!is_dir($path)) {
        file_mkdirs($path);
    }
    file_put_contents($file, PHP_EOL . '#' . dr_date(SYS_TIME, 'y-m-d h:i:s') . ' ' . $text, FILE_APPEND);
}

function server_ip()
{
    if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
        return $_SERVER['SERVER_ADDR'];
    }
    return gethostbyname($_SERVER['HTTP_HOST']);
}

function file_mkdirs($dir)
{
    if (!$dir) {
        return false;
    }
    if (!is_dir($dir)) {
        file_mkdirs(dirname($dir));
        if (!is_dir($dir)) {
            mkdir($dir, 0777);
        }
    }
}

function file_mapdirs($source_dir, $directory_depth = 0, $hidden = false)
{
    if ($fp = @opendir($source_dir)) {
        $filedata = array();
        $new_depth = $directory_depth - 1;
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        while (false !== ($file = readdir($fp))) {
            if ($file === '.' or $file === '..' or $hidden === false && $file[0] === '.' or !@is_dir($source_dir . $file)) {
                continue;
            }
            if (($directory_depth < 1 or $new_depth > 0) && @is_dir($source_dir . $file)) {
                $filedata[$file] = dr_dir_map($source_dir . DIRECTORY_SEPARATOR . $file, $new_depth, $hidden);
            } else {
                $filedata[] = $file;
            }
        }
        closedir($fp);
        return $filedata;
    }
    return false;
}

function ignore_timeout()
{
    @ignore_user_abort(true);
    @set_time_limit(24 * 60 * 60);
    @ini_set('memory_limit', '2028M');
}

function file_delete($fullpath)
{
    if (!@unlink($fullpath)) {
        @chmod($fullpath, 0755);
        if (!@unlink($fullpath)) {
            return false;
        }
    } else {
        return true;
    }
}

function file_deldirs()
{
    if (!file_exists($dir) || !is_dir($dir)) {
        return true;
    }
    if (!($dh = opendir($dir))) {
        return false;
    }
    ignore_timeout();
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
            $fullpath = $dir . '/' . $file;
            if (!is_dir($fullpath)) {
                if (!unlink($fullpath)) {
                    chmod($fullpath, 0755);
                    if (!unlink($fullpath)) {
                        return false;
                    }
                }
            } else {
                if (!file_deldirs($fullpath)) {
                    chmod($fullpath, 0755);
                    if (!file_deldirs($fullpath)) {
                        return false;
                    }
                }
            }
        }
    }
    closedir($dh);
    if (rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}

function file_safename($string)
{
    return str_replace(array('..', "/", '\\', ' ', '<', '>', "{", '}', ';', '[', ']'), '', $string);
}

function file_url($url)
{
    if (!$url || strlen($url) == 1) {
        return null;
    } elseif (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
        return $url;
    } elseif (strpos($url, SITE_PATH) !== false && SITE_PATH != '/') {
        return $url;
    } elseif (substr($url, 0, 1) == '/') {
        return SITE_PC . substr($url, 1);
    }
    return SYS_ATTACHMENT_URL . $url;
}

function file_get($id)
{
    if (!$id) {
        return '';
    }
    if (is_numeric($id)) {
        $info = attachment_get($id);
        $id = $info['attachment'] ? $info['attachment'] : '';
    }
    $file = file_url($id);
    return $file ? $file : '';
}

function url_current()
{
    $pageURL = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
        $pageURL .= 's';
    }
    $pageURL .= '://';
    if (strpos($_SERVER['HTTP_HOST'], ':') !== false) {
        $url = explode(':', $_SERVER['HTTP_HOST']);
        $url[0] ? $pageURL .= $_SERVER['HTTP_HOST'] : ($pageURL .= $url[0]);
    } else {
        $pageURL .= $_SERVER['HTTP_HOST'];
    }
    $pageURL .= $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
    return $pageURL;
}

function url_build($uri, $query = array())
{
    if (substr($uri, 0, 6) == 'member') {
        $self = 'member/';
        $u = substr($uri, 7);
    } else {
        if (substr($uri, 0, 5) == 'admin') {
            $self = 'admin/';
            $u = substr($uri, 6);
        } else {
            $self = '';
            $u = $url;
        }
    }
    $array = explode('/', $u);
    $s = array_shift($array);
    if (is_dir(WEBPATH . 'module/' . $s) || is_dir(WEBPATH . 'app/' . $s)) {
        $url = $s . '/' . array_shift($array) . '/' . array_shift($array);
    } else {
        $url = $s . '/' . array_shift($array);
    }
    if ($array) {
        foreach ($array as $k => $t) {
            $i % 2 == 0 && ($query[$t] = isset($array[$k + 1]) ? $array[$k + 1] : '');
            $i++;
        }
    }
    return SITE_URL . $self . $url . ($query ? '?' . http_build_query($query) : '');
}

function _pages_build($url, $total, $pagesize = SITE_ADMIN_PAGESIZE, $offset = 3)
{
    $url .= strpos($url, '?') === false ? '?' : '&';
    $nums = ceil($total / 10);
    $page = max(1, $_GET['page']);
    $pages['total'] = $total;
    $pages['nums'] = $nums;
    if ($page > $nums) {
        return $pages;
    }
    $page > $offset && ($pages['first'] = _pages_build_link($url, $total, 1));
    $nums - $page > $offset && ($pages['last'] = _pages_build_link($url, $total, $nums));
    isset($page) && $page != 1 && ($pages['prev'] = _pages_build_link($url, $total, $page - 1));
    isset($page) && $page != $nums && ($pages['next'] = _pages_build_link($url, $total, $page + 1));
    if ($page < $offset) {
        $start = 1;
        $end = min($nums, $offset * 2);
    } else {
        if ($page > $offset && $page < $nums - $offset) {
            $start = $page - $offset;
            $end = min($nums, $page + $offset);
        } else {
            $start = max(1, $nums - $offset * 2);
            $end = $nums;
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        $pages['page'][$i] = _pages_build_link($url, $total, $i);
    }
    return $pages;
}

function _pages_build_link($url, $total, $page)
{
    return $url . "total={$total}&page={$page}";
}

function thumb_get($image, $width = null, $height = null, $water = 0, $size = 0)
{
    if (!$image) {
        return THEME_PATH . 'admin/images/nopic.gif';
    }
    if (is_numeric($image)) {
        // 表示附件id
        $thumb = trim(SYS_THUMB_DIR,
                '/') . '/' . md5($image) . '/' . dr_safe_filename("{$width}-{$height}-{$water}-{$size}") . '.jpg';
        if (is_file(MTBASE . $thumb)) {
            return SITE_URL . $thumb;
        }
        $CI =& get_instance();
        return $CI->html_thumb("{$image}-{$width}-{$height}-{$water}-{$size}");
    }
    $image = file_url($image);
    return $image ? $image : THEME_PATH . 'admin/images/nopic.gif';
}

function string2array($data)
{
    if (is_array($data)) {
        return $data;
    } elseif (!$data) {
        return array();
    } elseif (strpos($data, 'a:') === 0) {
        return unserialize(stripslashes($data));
    } else {
        return @json_decode($data, true);
    }
}

function array2string($data)
{
    return $data ? json_encode($data) : '';
}

function member_avatar($uid, $size = '180')
{
    if ($uid) {
        $size = $size > 100 ? 180 : $size;
        foreach (array('png', 'jpg', 'gif', 'jpeg') as $ext) {
            if (is_file(SYS_UPLOAD_PATH . '/member/' . $uid . '/' . $size . 'x' . $size . '.' . $ext)) {
                return SYS_ATTACHMENT_URL . 'member/' . $uid . '/' . $size . 'x' . $size . '.' . $ext;
            }
        }
    }
    return SITE_URL . 'statics/avatar/default.jpg';
}

function qrcode_get($text, $uid, $level, $size, $margin)
{
    if ($text) {
        $name = md5($text);
        $file = FILEPATH . "qrcode/{$name}.png";
        if (!is_file($file)) {
            require_once LIBRARIES . 'phpqrcode.php';
            QRcode::png($text, $outfile = false, $level = $level, $size = $size, $margin = $margin, $saveandprint=true);
        }
        return SITE_PC . "file/qrcode/{$name}.png";
    }
}

function attachment_get($id)
{
    if (!$id) {
        return null;
    }
    $CI =& get_instance();
    $info = $CI->get_cache_data("attachment-{$id}");
    if ($info) {
        return $info;
    }
    $data = $CI->db->where('id', (int)$id)->get('attachment')->row_array();
    if (!$data) {
        return null;
    }
    $info = $CI->db->where('id', (int)$id)->get('attachment_use')->row_array();
    if (!$info) {
        $info = $CI->db->where('id', (int)$id)->get('attachment_unused')->row_array();
    }
    if (!$info) {
        return null;
    }
    $info = $data + $info;
    $info['_attachment'] = trim($info['attachment'], '/');
    $url = $info['remote'] ? $CI->get_cache('attachment', $data['siteid'], 'data', $info['remote'], 'url') : '';
    $info['attachment'] = $url ? $url . '/' . $info['_attachment'] : attachment_ueditor($info['_attachment']);
    $attachinfo = string2array($info['attachinfo']);
    if (in_array($info['fileext'],
            array('jpg', 'gif', 'png')) && (!isset($attachinfo['width']) || !$attachinfo['width'])) {
        list($attachinfo['width'], $attachinfo['height']) = @getimagesize(file_get($info['attachment']));
    }
    unset($info['attachinfo']);
    $info = $attachinfo ? $info + $attachinfo : $info;
    $CI->set_cache_data("attachment-{$id}", $info, SYS_CACHE_ATTACH);
    return $info;
}

function attachment_ueditor($file)
{
    if (!SYS_UPLOAD_DIR) {
        return $file;
    } elseif (strpos($file, SYS_UPLOAD_DIR) === 0) {
        return trim(str_replace(SYS_UPLOAD_DIR, '', $file), '/');
    } elseif (strpos($file, 'member/uploadfile/') === 0) {
        return trim(str_replace('member/uploadfile/', '', $file), '/');
    } else {
        return $file;
    }
}

function is_memcache()
{
    if (defined('SYS_MEMCACHE') && SYS_MEMCACHE) {
        if (class_exists('Memcached', false)) {
            return true;
        } elseif (class_exists('Memcache', false)) {
            return true;
        }
    }
    return false;
}

function block_get($id, $type = 0, $site = SITE_ID)
{
    $CI = &get_instance();
    return $CI->get_cache('block-' . $site, $id, $type);
}

function safe_replace($string)
{
    $string = str_replace('%20', '', $string);
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);
    $string = str_replace('*', '', $string);
    $string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    return $string;
}

function flag($id, $mod = 'news')
{
    $ci = &get_instance();
    $flag = $ci->get_cache("module-1-{$mod}", 'setting', 'flag');
    return $flag[$id];
}

function flagChapter($flag, $mod = 'news', $nums = 7, $where = [])
{
    $ci = &get_instance();
    return $ci->models('module')->flagChapter($flag, $mod, $nums, $where);
}

function getListCategory($module = 'resources', $nums = 5)
{
    $ci = &get_instance();
    return $ci->models('module')->getListCategory($module, $nums);
}

function getPartnerList()
{
    $ci = &get_instance();
    return $ci->db->where(['status' => 9])->get(SITE_ID . '_partner')->result_array();
}

/**
 * @param int $code
 * @param string $msg
 * @param array $data
 * @param string $redirect_url
 * @return false|string
 */
function apiSuccess($code = 200, $msg = "操作成功", $data = [], $redirect_url = '')
{
    header('Content-Type:application/json');
    $response = ["code" => $code, "msg" => $msg];
    if ($data) {
        $response['data'] = $data;
    }
    if ($redirect_url) {
        $response['redirect'] = $redirect_url;
    }
    exit(json_encode($response, JSON_UNESCAPED_UNICODE));
}

/**
 * PHP 验证表单数据
 */
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * 邀请码
 */
function create_randcode()
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN0PQRSTUVWXYZ";
    $str = "";
    for ($i = 0; $i < 3; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    $str .= date('s') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 5);
    $ci = &get_instance();
    $uid = $ci->db->select('uid')->where('randcode', $str)->get('member')->row_array()['uid'];
    if ($uid > 0) {
        return create_randcode();
    }
    return $str;
}

/**
 * get_abc 返回Excel 定位
 * $count_field 字段数量
 * $a_z A~Z
 * $abc A~ 
 * @return array
 */
function get_abc($count_field, $abc, $i = 0)
{
    if ($count_field >= count($abc))
    {
        foreach ($abc as $v)
        {
            $abc[] = $abc[$i] . $v;
            if ($count_field < count($abc) || count($abc)%26 == 0)
                break;
        }
        $abc = get_abc($count_field, $abc, $i+1);
    }
    return $abc;
}

//随机串
function dr_noncestr($length = 16)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }

    return $str;
}

//jsapi_ticket是公众号用于调用微信JS接口的临时票据
function jsapi_ticket()
{
    $ci = &get_instance();
    $res = $ci->db->select('jsapi_ticket, inputtime')->where('id', 1)->get('jsapi_ticket')->row_array();
    if (!$res || $res['inputtime'] < SYS_TIME) {
        $access_token = access_token();
        $jt_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=jsapi";
        $jt_data = json_decode(file_get_contents($jt_url), TRUE);
        $jsapi_ticket = $jt_data['ticket'];
        $inputtime = SYS_TIME + $jt_data['expires_in'];
        $ci->db->replace('jsapi_ticket', ['id' => 1, 'jsapi_ticket' => $jsapi_ticket, 'inputtime' => $inputtime]);
    } else {
        $jsapi_ticket = $res['jsapi_ticket'];
    }
    return $jsapi_ticket;
}