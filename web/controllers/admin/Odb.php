<?php
/**
 * 数据优化操作类
 */

class Odb extends M_Controller {

    public function __construct() {
        parent::__construct();

        $menu = array(
            L('缓存优化') => array('admin/odb/index', 'refresh'),
            L('查询优化') => array('admin/odb/sql', 'database'),
        );

        $this->template->assign(array(
            'menu' => $this->get_menu_v3($menu),
        ));
    }

    public function index() {


        if (IS_POST) {
            $system = require CONFPATH.'system.php'; // 加载网站系统配置文件
            $post = $this->input->post('sys', true);

            $this->load->library('dconfig');
            $system['SYS_MEMCACHE'] =  $post['SYS_MEMCACHE'];
            $system['SYS_AUTO_CACHE'] =  $post['SYS_AUTO_CACHE'];

            $this->dconfig->file(CONFPATH.'system.php')->note('系统配置文件')->space(32)->to_require_one($this->models('system')->config, $system);

            function_exists('opcache_reset') && opcache_reset();
            $this->admin_msg(L('操作成功'), dr_url('odb/index'), 1);
        }

        $this->template->assign(array(
            'is_opcache' => function_exists('opcache_reset'),
            'is_memcached' => is_memcache(),
            'memcached' => '<?php
if (!defined(\'BASEPATH\')) exit(\'No direct script access allowed\');

$config = array(
	\'default\' => array(
		\'hostname\' => \'填写主机名，例如127.0.0.1\',
		\'port\'     => \'填写端口号，例如11211\',
		\'weight\'   => \'1\',
	),
);'
        ));
        $this->template->display('odb_index.html');
    }

    public function sql() {

        $sql = "";
        $explain = array();

        if (IS_POST) {
            $sql = $this->input->post('code', true);
            if (preg_match('/select(.*)into outfile(.*)/i', $sql)) {
                $this->admin_msg(L('存在非法select'));
            } elseif (preg_match('/select(.*)into dumpfile(.*)/i', $sql)) {
                $this->admin_msg(L('存在非法select'));
            }
            $jiexi = $this->db->query('explain '.$sql)->row_array();
            if (!$jiexi) {
                $this->admin_msg(L('无法识别的SQL语句'));
            }
            $explain[] = array(
                'name' => '查询方式',
                'result' => $jiexi['select_type'],
                'fangan' => $this->_select_type($jiexi['select_type']),
            );
            $explain[] = array(
                'name' => '查询类型',
                'result' => $jiexi['type'],
                'fangan' => $this->_type($jiexi['type']),
            );
            $explain[] = array(
                'name' => '组合索引字段',
                'result' => $jiexi['possible_keys'],
                'fangan' => !$jiexi['possible_keys'] ? "<font color='red'>不合理</font>" : "",
            );
            $explain[] = array(
                'name' => '查询索引字段',
                'result' => $jiexi['key'],
                'fangan' => !$jiexi['key'] ? "<font color='red'>不合理</font>" : "",
            );
            $explain[] = array(
                'name' => '索引占用资源',
                'result' => $jiexi['key_len'],
                'fangan' => "不损失精确性的情况下，长度越短越好",
            );
            $explain[] = array(
                'name' => '性能分析',
                'result' => str_replace(';', '<br>', $jiexi['Extra']),
                'fangan' => "using index ：使用覆盖索引的时候就会出现<br>
using where：在查找使用索引的情况下，需要回表去查询所需的数据<br>

using index condition：查找使用了索引，但是需要回表查询数据<br>

using index & using where：查找使用了索引，但是需要的数据都在索引列中能找到，所以不需要回表查询数据<br>",
            );


            $explain[] = array(
                'name' => '查询索引创建提示',
                'result' => "参与搜索的字段",
                'fangan' => "<font color='green'>凡是出现在以上SQL语句中的字段，都建议合理的创建索引（通过phpmyadmin工具可创建索引）</font>",
            );

        }


        $this->template->assign(array(
            'sql' =>$sql,
            'explain' =>$explain,
        ));
        $this->template->display('odb_sql.html');
    }


    private function _select_type($name) {
        switch ($name) {
            case 'SIMPLE':
                return '标准查询';
                break;
            case 'PRIMARY':
                return '主键查询';
                break;
            case 'UNION':
                return '组合查询（不推荐）';
                break;
            case 'SUBQUERY':
                return '子查询（不推荐）';
                break;
            default:
                return '未知结果';
                break;
        }
    }
    private function _type($name) {
        $name = strtolower($name);
        switch ($name) {
            case 'all':
                return '查询全表（最差）';
                break;
            case 'index':
                return '按索引查询（差）';
                break;
            case 'range':
                return '范围查询（较差）';
                break;
            case 'ref':
                return '索引范围查询（一般）';
                break;
            case 'eq_ref':
                return '索引范围查询（一般）';
                break;
            case 'const':
                return '优化查询（好）';
                break;
            case 'system':
                return '优化查询（好）';
                break;
            case 'null':
                return '优化查询（最好）';
                break;
            default:
                return '未知结果';
                break;
        }
    }
    private function _extra($name) {
        $name = strtolower($name);
        switch ($name) {
            case 'Using where':
                return '查询全表（最差）';
                break;
            case 'index':
                return '按索引查询（差）';
                break;
            default:
                return '未知结果';
                break;
        }
    }

}