<?php
class Events extends M_Controller
{
    private $hooks_path;
    private $eve_path;
    private $config_path;
    public function __construct()
    {
        parent::__construct();
        $this->hooks_path = FCPATH.'hooks/';
        $this->eve_path = CONFPATH.'eve/';
        $this->config_path = CONFPATH;
        
        if(!is_dir($this->hooks_path)) {
            mkdir($this->hooks_path,'0777',true);
        }
        if(!is_dir($this->eve_path)) {
            mkdir($this->eve_path,'0777',true);
        }
        if(!is_dir($this->config_path)) {
            mkdir($this->config_path,'0777',true);
        }
        $this->template->assign('menu', $this->get_menu_v3(array(
            L('事件') => array('admin/events/index', 'rocket'),
            L('添加') => array('admin/events/add_js', 'plus'),
        )));
    }

    /** 管理页 index **/
    public function index()
    {
        if (IS_POST) {
            $ids = $this->input->post('ids', TRUE);
            if (!$ids) {
                $this->msg(0, L('您还没有选择呢'));
            } elseif (!$this->is_auth('admin/sysvar/del')) {
                $this->msg(0, L('您无权限操作'));
            }
            $data = $this->db->where_in('id',$ids)->get('hook')->result_array();
            $this->db->where_in('id', $ids)->delete('hook');
            if($data)
            {
                foreach($data as $key => $val)
                {
                    $this->createHooks($val);
                    $this->del_file($val['filename']);
                }
            }
            $this->upateHooks();
            $this->system_log('删除事件【#'.@implode(',', $ids).'】'); // 记录日志
            $this->msg(1, L('删除事件，操作成功'));
        }
        $page = max(1, (int)$_GET['page']);
        $total = $_GET['total'] ? $_GET['total'] : $this->db->count_all_results('hook');
        $data = $total ? $this->db->order_by('id desc')->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1))->get('hook')->result_array() : array();
        if($data)
        {
            foreach($data as $key => $val) {
                if (is_file(FCPATH . 'hooks/' . $val['filename'] . '.php')) {
                    require_once FCPATH . 'hooks/' . $val['filename'] . '.php';
                    $className = $this->getNeedBetween($val['trubFunc'], 'class', '{');
                    $arr = '';
                    if ($className) {
                        $ref = new ReflectionClass($className);
                        if ($ref) {
                            $methods = $ref->getMethods();   //返回类中所有方法

                            foreach ($methods as $method) {
                                $arr .= '<p>' . $method->getName() . '</p>';
                            }
                        }
                    }
                    $data[$key]['action'] = $arr;
                }
            }
        }

        $this->template->assign(array(
            'list' => $data,
            'total' => $total,
            'pages' => $this->get_pagination(dr_url('eve/index', array('total' => $total)), $total),
        ));
        $this->template->display('events_index.html');
    }

    /** 添加页 add **/
    public function add()
    {
        if (IS_POST) {
            $data = $this->input->post('data');
            $msg = $this->validateData($data,false);
            if($msg)
            {
                $this->msg($msg['code'], $msg['content'], $msg['postName']);
            }
            $this->db->insert('hook', $data);
            $id = $this->db->insert_id();
            $this->makeEve($data['filename']);
            $this->insert_class($data['trubFunc'],$data['filename']);
            $this->createHooks($data);
            $this->upateHooks();
            $this->system_log('添加自定义事件【'.$data['name'].'#'.$id.'】'); // 记录日志
            $this->msg(1, L('添加事件，操作成功'));
        }
        $this->template->display('events_add.html');
    }

    /** 修改页 edit **/
    public function edit()
    {
        $id = (int)$this->input->get('id');
        $alradey = $data= $this->db->where('id', $id)->limit(1)->get('hook')->row_array();
        if (!$data) {
            exit(L('对不起，数据被删除或者查询不存在'));
        }

        if (IS_POST) {
            $data = $this->input->post('data');
            $msg = $this->validateData($data,$id);
            if($msg)
            {
                $this->msg($msg['code'], $msg['content'], $msg['postName']);
            }
            $this->del_file($alradey['filename']);
            $this->makeEve($data['filename']);
            $this->insert_class($data['trubFunc'],$data['filename']);
            $this->db->where('id', $id)->update('hook', $data);
            $this->createHooks($data,$alradey['hooksname']);
            $this->upateHooks();
            $this->system_log('修改事件【'.$data[1]['type'].'#'.$id.'】'); // 记录日志
            $this->msg(1, L('修改事件，操作成功'), '');
        }

        $this->template->assign(array(
            'data' => $data,
        ));
        $this->template->display('events_add.html');
    }

    /**表单数据验证**/
    protected function validateData($data,$id)
    {
        if (!$data['name']) {
            return array('code' => 0, 'content' => L('【%s】不能为空', L('名称')), 'postName' => 'name');
        }
        if (!$data['class']) {
            return array('code' => 0, 'content' => L('事件类名称不能为空'), 'postName' => 'class');
        }
        if (!$data['func']) {
            return array('code' => 0, 'content' => L('事件方法不能为空'), 'postName' => 'func');
        }
        if(!$data['filename'])
        {
            return array('code' => 0, 'content' => L('事件方法体文件名不能为空'), 'postName' => 'filename');
        }
        if(!$data['hooksname'])
        {
            return array('code' => 0, 'content' => L('事件模型类文件不能为空'), 'postName' => 'hooksname');
        }
        if (!$data['trubFunc'])
        {
            return array('code' => 0, 'content' => L('事件模型类方法不能为空'), 'postName' => 'trubFunc');
        }

        if($id)
        {
            if ($this->db->where('name', $data['name'])->where('id<>', $id) ->count_all_results('hook')) {
                return array('code' => 0, 'content' => L('事件名称已经存在,不能重复'), 'postName' => '');
            }
            if ($this->db->where('filename', $data['filename'])->where('id<>', $id) ->count_all_results('hook')) {
                return array('code' => 0, 'content' => L('事件文件名已经存在,不能重复'), 'postName' => '');
            }
        } else {
            if ($this->db->where('name', $data['name'])->where('id<>', 0) ->count_all_results('hook'))
            {
                return array('code' => 0, 'content' => L('事件名称已经存在,不能重复'), 'postName' => '');
            }
            if ($this->db->where('filename', $data['filename'])->where('id<>', 0) ->count_all_results('hook'))
            {
                return array('code' => 0, 'content' => L('事件文件名已经存在,不能重复'), 'postName' => '');
            }
        }
    }

    /** 生成事件文件和数据配置信息 **/
    public function makeEve($filename,$filepath = 'hooks')
    {
        $file = $this->get_file($filename,$filepath);
        //判断事件模型文件是否存在,如若不存在则创建并指定模型文件可执行
        if(!file_exists($file))
        {
            touch($file,'0777',true);
        }
    }

    /**文件清除**/
    protected function del_file($filename,$filepath = 'hooks')
    {
        $file = $this->get_file($filename,$filepath);
        if(is_file($file))
        {
            unlink($file);
        }
    }

    /**获取操作的文件**/
    protected function get_file($filename,$filepath)
    {
        switch ($filepath)
        {
            case 'hooks':
                $file = $this->hooks_path.$filename.'.php';
                break;
            case 'eve':
                $file = $this->eve_path.$filename.'_hooks.php';
                break;
            case 'config':
                $file = $this->config_path.$filename.'.php';
                break;
        }
        return $file;
    }

    /**压入php函数体**/
    protected function insert_class($phpcode,$filename)
    {
        $code = <<<INFO
        <?php
        $phpcode
        INFO;
        file_put_contents(FCPATH.'hooks/'.$filename.'.php', $code);
        //write_file();
    }

    /**字符串截取**/
    protected function getNeedBetween($str,$str1,$str2){
        $start =stripos($str,$str1);
        $end =stripos($str,$str2);
        if(($start===false||$end===false)||$start>=$end)
        {
            return false;
        } else {
            return trim(substr($str,($start+5),($end-$start-5)));
        }
    }

    /**事件创建与更新**/
    protected function createHooks($postData = array(),$oldHooksName = '')
    {
        $oldHooksName ? $this->del_file($oldHooksName,'eve') : false; //删除config/eve里的废弃文件
        $file = $this->get_file($postData['hooksname'],'eve');
        $data = $this->db->where('hooksname', $postData['hooksname'])->get('hook')->result_array();
        if($data)
        {
            $this->makeEve($postData['hooksname'],$filepath = 'eve');
            $phpCode = <<<INFO
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
INFO;
            file_put_contents($file, $phpCode);
            foreach ($data as $key => $val) {
                $hook = "$".'hook';
                $code = <<<INFO
                
                /** {$val['descript']} **/               
                {$hook}['{$val['name']}'][] = [
                    'class' => '{$val['class']}',
                    'function' => '{$val['func']}',
                    'filename' => '{$val['filename']}.php',
                    'filepath' => 'hooks'
                ];
                INFO;
                file_put_contents($file, $code,FILE_APPEND);
            }
        } else {
            $this->del_file($postData['filename']);
            $this->del_file($postData['hooksname'],'eve');
        }
    }

    /**创建事件引进文件**/
    protected function upateHooks()
    {
        $sql = "SELECT distinct hooksname FROM imt_hook";
        $data = $this->db->query($sql)->result_array();

        $this->makeEve('hooks','config');
        $file = $this->get_file('hooks','config');
        file_put_contents($file, '');
        $phpCode = <<<INFO
<?php

/**
 * 钩子定义配置
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// 加载当前模块的钩子配置文件
if (is_file(APPPATH.'config/my_hooks.php')) {
    require_once APPPATH.'config/my_hooks.php';
}
INFO;
        file_put_contents($file, $phpCode);//写入
        if($data)
        {
            foreach($data as $key => $val)
            {
                $code = <<<INFO


if (is_file(CONFPATH.'eve/{$val['hooksname']}_hooks.php')) {
    require_once CONFPATH.'eve/{$val['hooksname']}_hooks.php';
}
INFO;
                file_put_contents($file, $code, FILE_APPEND);
            }
        }
    }
}
