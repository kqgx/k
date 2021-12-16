<?php

class Extends_Page extends M_Controller {
 
    private $_id;
	private $field;
	private $nocache;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		
    }

    //
    protected function _get_page($id, $dir, $pid) {

        !$id && !$dir && $this->goto_404_page(L('参数不完整，id与dir必须有一个'));

        // 页面缓存
        $PAGE = $this->get_cache('page-'.SITE_ID);
        $page = APP_DIR ? $PAGE['data'][APP_DIR] : $PAGE['data']['index'];

        // 获取页面ID
        $id = !$id && $dir ? $PAGE['dir'][$dir] : $id;

        // 无法通过目录找到栏目时，尝试多及目录
        if (!$id && $dir && $page) {
            foreach ($page as $t) {
                if ($t['urlrule']) {
                    $rule = $this->get_cache('urlrule', $t['urlrule']);
                    if ($rule['value']['catjoin'] && strpos($dir, $rule['value']['catjoin'])) {
                        $dir = trim(strchr($dir, $rule['value']['catjoin']), $rule['value']['catjoin']);
                        if (isset($PAGE['dir'][$dir])) {
                            $id = $PAGE['dir'][$dir];
                            break;
                        }
                    }
                }
            }
        }
        unset($PAGE);

        // 当前页面的数据
        $data = $page[$id];
        !$data && $this->goto_404_page(L('页面（%s）不存在', $id));
        
        // 页面验证是否存在子栏目，是否将下级第一个页面作为当前页
        if ($data['child'] && $data['getchild']) {
            $temp = explode(',', $data['childids']);
            if ($temp) {
                foreach ($temp as $i) {
                    if ($page[$i]['id'] != $id && $page[$i]['show'] && !$page[$i]['child']) {
                        $id = $i;
                        $data = $page[$i];
                        break;
                    }
                }
            }
        }

        $my = $this->get_cache('page-field-'.SITE_ID);
        $my = $my ? array_merge($this->field, $my) : $this->field;
        $data = $this->field_format_value($my, $data, $pid); // 格式化输出自定义字段

        // 定向URL
        $data['url'] && dr_is_redirect(1, dr_url_prefix($data['url']));

        $join = SITE_SEOJOIN ? SITE_SEOJOIN : '_';
        $title = $data['title'] ? $data['title'] : dr_get_page_pname($id, $join);
        isset($data['content_title']) && $data['content_title'] && $title = $data['content_title'].$join.$title;

        // 栏目下级或者同级栏目
        $related = $parent = array();
        if ($data['pid']) {
            foreach ($page as $t) {
                if (!$t['show']) {
                    continue;
                }
                if ($t['pid'] == $data['pid']) {
                    $related[] = $t;
                    $parent = $data['child'] ? $data : $page[$t['pid']];
                }
            }
        } elseif ($data['child']) {
            $parent = $data;
            foreach ($page as $t) {
                if (!$t['show']) {
                    continue;
                }
                $t['pid'] == $data['id'] && $related[] = $t;
            }
        } else {
            $parent = $data;
            if ($page) {
                foreach ($page as $t) {
                    if (!$t['show']) {
                        continue;
                    }
                    $related[] = $t;
                }
            }
        }

        // 格式化配置
        $data['setting'] = string2array($data['setting']);

        // 存储id和缓存参数
        $this->_id = $data['id'];
        $this->nocache = (int)$data['setting']['nocache'];

        $this->render(array_merge($data, array(
            'pageid' => $id,
            'parent' => $parent,
            'related' => $related,
            'urlrule' => $this->mobile ? dr_mobile_page_url($data['module'], $data['id'], '{page}') : dr_page_url($data, '{page}'),
            'meta_title' => $title,
            'meta_keywords' => trim($data['keywords'].','.SITE_KEYWORDS, ','),
            'meta_description' => $data['description']
        )), $data['template'] ? $data['template'] : 'page.html');
    }
	
	public function index(){
        ob_start();
        $this->_get_page(
            (int)$this->input->get('id'),
            $this->input->get('dir'),
            max(1, (int)$this->input->get('page'))
        );
        $html = ob_get_clean();

        // 不被缓存
        $this->nocache && exit($html);

        // 生成缓存
        defined('SYS_AUTO_CACHE') && SYS_AUTO_CACHE && file_put_contents(WEBPATH.'cache/page/'.md5(PAGE_CACHE_URL.max(intval($_GET['page']), 1)).'.html', $html, LOCK_EX);

        exit($html);  
	}
}