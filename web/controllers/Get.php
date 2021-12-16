<?php

class Get extends M_Controller {
    
    public function index(){
        
        $param = $this->input->get(NULL, TRUE);
        
        $system = array(
            'limit' => '', // 显示数量
            'site' => $param['site'] ? $param['site'] : SITE_ID, // 站点id
            'flag' => '', // 推荐位id
            'more' => '', // 是否显示栏目附加表
            'catid' => '', // 栏目id，支持多id
            'field' => '', // 显示字段
            'order' => '', // 排序
            'table' => '', // 表名变量
            'join' => '', // 关联表名
            'on' => '', // 关联表条件
            'cache' => (int)SYS_CACHE_LIST, // 默认缓存时间
            'action' => '', // 动作标识
            'module' => MOD_DIR, // 模块名称
            'modelid' => '', // 模型id
            'keyword' => '', // 关键字
            'page' => '', // 是否分页
            'pagesize' => SITE_PAGESIZE, // 自定义分页数量
            'block' => '', // 资料块
        );
        
        $sysadj = array('IN', 'BEWTEEN', 'BETWEEN', 'LIKE', 'NOTIN', 'NOT', 'BW');
        
        foreach ($param as $var=>$val) {
            $val = defined($val) ? constant($val) : $val;
            if ($var == 'fid' && !$val) {
                continue;
            }
            if (isset($system[$var])) { // 系统参数，只能出现一次，不能添加修饰符
                $system[$var] = safe_replace($val);
            } else {
                if (preg_match('/^([A-Z_]+)(.+)/', $var, $match)) { // 筛选修饰符参数
                    $_pre = explode('_', $match[1]);
                    $_adj = '';
                    foreach ($_pre as $p) {
                        in_array($p, $sysadj) && $_adj = $p;
                    }
                    $where[] = array(
                        'adj' => $_adj,
                        'name' => $match[2],
                        'value' => $val
                    );
                } else {
                    $where[] = array(
                        'adj' => '',
                        'name' => $var,
                        'value' => $val
                    );
                }
                $param[$var] = $val; // 用于特殊action
            }
        }            
        
        // 替换order中的非法字符
        isset($system['order']) && $system['order'] && $system['order'] = str_ireplace(
            array('"', "'", ')', '(', ';', 'select', 'insert', '`'),
            '',
            $system['order']
        );

        switch ($system['action']) {

            case 'content': // 模块文档内容

                if (!isset($param['id'])) {
                    return $this->_return(L('参数不存在'));
                }
                
                $param['id'] = intval($param['id']);
                $dirname = $system['module'] ? $system['module'] : MOD_DIR;
                if (!$dirname) {
                    return $this->_return(L('参数不存在'));
                }

                $module = get_module($dirname, $system['site']);
                if (!$module) {
                    return $this->_return(L('模块不存在'));
                }
                
                $data = $this->models('module/content')->set($module['dirname'])->get($param['id']);
                
                $page = max(1, (int)$_GET['page']);
                $name = md5(array2string($param));
                $cache = $this->get_cache_data($name);
                if (!$cache && is_array($data)) {
                    $fields = $module['field'];
                    $fields = $module['category'][$data['catid']]['field'] ? array_merge($fields, $module['category'][$data['catid']]['field']) : $fields;
                    
                    $data = $this->field_format_value($fields, $data, $page, $module['dirname']);
                    
                    if ($system['field'] && $data) {
                        $_field = explode(',', $system['field']);
                        foreach ($data as $i => $t) {
                            if (strpos($i, '_') !== 0 && !in_array($i, $_field)) {
                                unset($data[$i]);
                            }
                        }
                    }
                    
                    $cache = $system['cache'] ? $this->set_cache_data($name, $data, $system['cache']) : $data;
                }

                return $this->_return($cache);
                break;

            case 'category': // 栏目

                $dirname = $system['module'] ? $system['module'] : MOD_DIR;
                if (!$dirname) {
                    return $this->_return('参数不存在');
                }

                $module = get_module($dirname, $system['site']);
                if (!$module || count($module['category']) == 0) {
                    return $this->_return("模块不存在");
                }

                $i = 0;
                $show = isset($param['show']) ? 1 : 0; // 有show参数表示显示隐藏栏目
                $return = array();
                foreach ($module['category'] as $t) {
                    if ($system['limit'] && $i >= $system['limit']) {
                        break;
                    } elseif (!$t['show'] && !$show) {
                        continue;
                    } elseif (isset($param['pid']) && $t['pid'] != (int)$param['pid']) {
                        continue;
                    } elseif (isset($param['mid']) && $t['mid'] != $param['mid']) {
                        continue;
                    } elseif (isset($param['tid']) && $t['tid'] != $param['tid']) {
                        continue;
                    } elseif (isset($param['child']) && $t['child'] != (int)$param['child']) {
                        continue;
                    } elseif (isset($param['letter']) && $t['letter'] != $param['letter']) {
                        continue;
                    } elseif (isset($param['id']) && !in_array($t['id'], explode(',', $param['id']))) {
                        continue;
                    } elseif (isset($system['more']) && !$system['more']) {
                        unset($t['field'], $t['setting']);
                    }
                    $return[] = $t;
                    $i ++;
                }

                if (!$return) {
                    return $this->_return(L(L('没有数据')));
                }

                return $this->_return($return, '');
                break;

            case 'linkage': // 联动菜单

                $linkage = $this->get_cache('linkage-'.$system['site'].'-'.$param['code']);
                if (!$linkage) {
                    return $this->_return(L('数据不存在'));
                }

                // 通过别名找id
                $ids = @array_flip($this->get_cache('linkage-'.$system['site'].'-'.$param['code'].'-id'));
                if (isset($param['pid'])) {
                    if (is_numeric($param['pid'])) {
                        $pid = intval($param['pid']);
                    } else {
                        $pid = isset($ids[$param['pid']]) ? $ids[$param['pid']] : 0;
                        !$pid && is_numeric($param['pid']) && $this->get_cache('linkage-'.$system['site'].'-'.$param['code'].'-id', $param['pid']) && $pid = intval($param['pid']);
                    }
                }

                $i = 0;
                $return = array();
                foreach ($linkage as $t) {
                    if ($system['limit'] && $i >= $system['limit']) {
                        break;
                    } elseif (isset($param['pid']) && $t['pid'] != $pid) {
                        continue;
                    } elseif (isset($param['id']) && !in_array($t['id'], explode(',', $param['id']))) {
                        continue;
                    }
                    $return[] = $t;
                    $i ++;
                }

                if (!$return && isset($param['pid'])) {
                    $rpid = isset($param['fid']) ? (int)$ids[$param['fid']] : (int)$linkage[$param['pid']]['pid'];
                    foreach ($linkage as $t) {
                        if ($t['pid'] == $rpid) {
                            if ($system['limit'] && $i >= $system['limit']) {
                                break;
                            }
                            if (isset($param['id']) && !in_array($t['id'], explode(',', $param['id']))) {
                                continue;
                            }
                            $return[] = $t;
                            $i ++;
                        }
                    }
                    if (!$return) {
                        return $this->_return(L('没有数据'));
                    }
                }

                return $this->_return(isset($param['call']) && $param['call'] ? @array_reverse($return) : $return, '');
                break;

            case 'page': // 单页调用

                $name = 'index';
                $data = $this->get_cache('page-'.$system['site'], 'data', $name); // 单页缓存
                if (!$data) {
                    return $this->_return(L('没有数据'));
                }

                $i = 0;
                $show = isset($param['show']) ? 1 : 0; // 有show参数表示显示隐藏栏目
                $field = $this->dcache->get('page-field-'.$system['site']);
                $return = array();
                foreach ($data as $id => $t) {
                    if (!is_numeric($id)) {
                        continue;
                    } elseif ($system['limit'] && $i >= $system['limit']) {
                        break;
                    } elseif (!$t['show'] && !$show) {
                        continue;
                    } elseif (isset($param['pid']) && $t['pid'] != (int) $param['pid']) {
                        continue;
                    } elseif (isset($param['id']) && !in_array($t['id'], explode(',', $param['id']))) {
                        continue;
                    }
                    $t['setting'] = string2array($t['setting']);
                    $return[] = $this->field_format_value($field, $t, 1);
                    $i ++;
                }

                if (!$return) {
                    return $this->_return(L('没有数据'));
                }

                return $this->_return($return);
                break;

            case 'tag': // 调用tag

                $module = get_module($system['module'] ? $system['module'] : MOD_DIR, $system['site']);
                if (!$module) {
                    // 没有模块数据时返回空
                    return $this->_return('模块不存在');
                }

                $system['order'] = safe_replace($system['order'] ? ($system['order'] == 'rand' ? 'RAND()' : $system['order']) : 'hits desc');

                $table = $this->db->dbprefix($system['site'].'_'.$module['dirname'].'_tag'); // tag表
                $sql = "SELECT id,name,code,hits FROM {$table} ORDER BY ".$system['order']." LIMIT ".($system['limit'] ? $system['limit'] : 10);
                $data = $this->_query($sql, $system['site'], $system['cache']);

                if (!$data) {
                    return $this->_return(L('没有数据'));
                }

                // 缓存查询结果
                $name = md5($sql);
                $cache = $this->get_cache_data($name);
                if (!$cache) {
                    foreach ($data as $i => $t) {
                        $data[$i]['url'] = dr_tag_url($module, $t['name']);
                    }
                    $cache = $system['cache'] ? $this->set_cache_data($name, $data, $system['cache']) : $data;
                }

                return $this->_return($cache, $sql);
                break;

            case 'tags': // 调用全局tag

                $system['order'] = safe_replace($system['order'] ? ($system['order'] == 'rand' ? 'RAND()' : $system['order']) : 'hits desc');

                $table = $this->db->dbprefix($system['site'].'_tag'); // tags表
                $where = '';
                if (isset($param['pid']) && strlen($param['pid'])) {
                    $where = 'WHERE `pid`='.intval($param['pid']);
                }

                $sql = "SELECT * FROM {$table} {$where} ORDER BY ".$system['order']." LIMIT ".($system['limit'] ? $system['limit'] : 10);
                $data = $this->_query($sql, $system['site'], $system['cache']);

                if (!$data) {
                    return $this->_return(L('没有数据'));
                }

                // 缓存查询结果
                $name = md5($sql);
                $cache = $this->get_cache_data($name);
                if (!$cache) {
                    foreach ($data as $i => $t) {
                        $data[$i]['url'] = dr_tags_url($t['code']);
                    }
                    $cache = $system['cache'] ? $this->set_cache_data($name, $data, $system['cache']) : $data;
                }

                return $this->_return($cache, $sql);
                break;

            case 'comment': // 评论查询

                // 判断评论类型的主表名称
                if (isset($system['module']) && $system['module']) {
                    $table = $this->db->dbprefix($system['site'].'_'.$system['module'].'_comment_data_0');
                } else {
                    return $this->_return(L('参数不存在'));
                }

                $tableinfo = $this->get_cache('table');
                if (!$tableinfo) {
                    $tableinfo = $this->models('system')->cache(); // 表结构缓存
                }

                if (!isset($tableinfo[$table]['field'])) {
                    return $this->_return('数据不存在');
                }

                $where[] = array(
                    'adj' => '',
                    'name' => 'status',
                    'value' => 1
                );

                // 按内容id查询
                if (isset($param['cid']) && $param['cid']) {
                    $where[] = array(
                        'adj' => '',
                        'name' => 'cid',
                        'value' => (int)$param['cid']
                    );
                    unset($param['cid']);
                }

                // 是否查询回复
                if (isset($param['all']) && $param['all']) {
                    unset($param['all']);
                } else {
                    $where[] = array(
                        'adj' => '',
                        'name' => 'reply',
                        'value' => 0
                    );
                }

                if (!$system['order']) {
                    $system['order'] = 'inputtime_desc';
                }

                $where = $this->_set_where_field_prefix($where, $tableinfo[$table]['field'], $table); // 给条件字段加上表前缀
                $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo[$table]['field'], $table); // 给显示字段加上表前缀
                $system['order'] = $this->_set_order_field_prefix($system['order'], $tableinfo[$table]['field'], $table); // 给排序字段加上表前缀

                $total = 0;
                $sql_from = $table; // sql的from子句
                $sql_limit = $pages = '';
                $sql_where = $this->_get_where($where); // sql的where子句

                if ($system['page'] && $system['urlrule']) {
                    $page = max(1, (int)$_GET['page']);
                    $pagesize = (int) $system['pagesize'];
                    $pagesize = $pagesize ? $pagesize : 10;
                    $sql = "SELECT count(*) as c FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ORDER BY NULL";
                    $row = $this->_query($sql, $system['site'], $system['cache'], FALSE);
                    $total = (int)$row['c'];
                    if (!$total) {
                        return $this->_return(L(L('没有数据')), $sql, 0);
                    }
                    $sql_limit = 'LIMIT '.$pagesize * ($page - 1).','.$pagesize;
                    $pages = $this->_get_pagination($system['urlrule'], $pagesize, $total);
                } elseif ($system['limit']) {
                    $sql_limit = "LIMIT {$system['limit']}";
                }

                $sql = "SELECT ".($system['field'] ? $system['field'] : "*")." FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ".($system['order'] ? "ORDER BY {$system['order']}" : "")." $sql_limit";
                $data = $this->_query($sql, $system['site'], $system['cache']);

                // 缓存查询结果
                $name = md5($sql);
                $cache = $this->get_cache_data($name);
                if (!$cache && is_array($data)) {
                    if (1 == $system['more']) {
                        foreach ($data as $i => $t) {
                            $data[$i]['replys'] = $t['in_reply'] ? $this->_query("SELECT ".($system['field'] ? $system['field'] : "*")." FROM $sql_from WHERE `reply`=".$t['id']." ORDER BY `inputtime` DESC", $system['site'], $system['cache']) : array();
                        }
                    }
                    $cache = $system['cache'] ? $this->set_cache_data($name, $data, $system['cache']) : $data;
                }

                return $this->_return($cache, $sql, $total, $pages, $pagesize);
                break;

            case 'module': // 模块数据

                $system['module'] = $dirname = $system['module'] ? $system['module'] : MOD_DIR;
                if (!$dirname) {
                    return $this->_return(L('参数不存在'));
                }

                $module = get_module($dirname, $system['site']);
                if (!$module) {
                    return $this->_return(L('模块不存在'));
                }

                $tableinfo = $this->get_cache('table');
                if (!$tableinfo) {
                    $tableinfo = $this->models('system')->cache(); // 表结构缓存
                }

                if (!$tableinfo) {
                    return $this->_return('数据不存在');
                }

                $table = $this->db->dbprefix($system['site'].'_'.$module['dirname']); // 模块主表
                if (!isset($tableinfo[$table]['field'])) {
                    return $this->_return('数据不存在');
                }

                // 排序操作
                if (!$system['order']
                    && $where[0]['adj'] == 'IN'
                    && $where[0]['name'] == 'id') {
                    // 按id序列来排序
                    $system['order'] = strlen($where[0]['value']) < 10000 && $where[0]['value'] ? 'instr("'.$where[0]['value'].'", `'.$table.'`.`id`)' : 'NULL';
                } else {
                    !$system['order'] && $system['order'] = $system['flag'] ? 'updatetime_desc' : $system['action'] == 'hits' ? 'hits' : 'updatetime'; // 默认排序参数
                }

                // 栏目筛选
                if ($system['catid']) {
                    if (strpos($system['catid'], ',') !== FALSE) {
                        $temp = @explode(',', $system['catid']);
                        if ($temp) {
                            $catids = array();
                            foreach ($temp as $i) {
                                $catids = $module['category'][$i]['child'] ? array_merge($catids, $module['category'][$i]['catids']) : array_merge($catids, array($i));
                            }
                            $catids && $where[] = array(
                                'adj' => 'IN',
                                'name' => 'catid',
                                'value' => implode(',', $catids),
                            );
                            unset($catids);
                        }
                        unset($temp);
                    } elseif ($module['category'][$system['catid']]['child']) {
                        $where[] = array(
                            'adj' => 'IN',
                            'name' => 'catid',
                            'value' => $module['category'][$system['catid']]['childids']
                        );
                    } else {
                        $where[] = array(
                            'adj' => '',
                            'name' => 'catid',
                            'value' => (int)$system['catid']
                        );
                    }
                }

                $fields = $module['field']; // 主表的字段
                $where[] = array( 'adj' => '', 'name' => 'status', 'value' => 9);
                $where = $this->_set_where_field_prefix($where, $tableinfo[$table]['field'], $table, $fields); // 给条件字段加上表前缀
                $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo[$table]['field'], $table); // 给显示字段加上表前缀
                $system['order'] = $this->_set_order_field_prefix($system['order'], $tableinfo[$table]['field'], $table); // 给排序字段加上表前缀

                // sql的from子句
                if ($system['action'] == 'hits') {
                    $sql_from = '`'.$table.'` LEFT JOIN `'.$table.'_hits` ON `'.$table.'`.`id`=`'.$table.'_hits`.`id`';
                    $table_more = $table.'_hits'; // hits表
                    $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo[$table_more]['field'], $table_more); // 给显示字段加上表前缀
                    $system['order'] = $this->_set_order_field_prefix($system['order'], $tableinfo[$table_more]['field'], $table_more); // 给排序字段加上表前缀
                } else {
                    $sql_from = '`'.$table.'`';
                }

                
                if (isset($module['category'][$system['catid']]['field'])
                    && $module['category'][$_catid]['field']) {
                    $fields = array_merge($fields, $module['category'][$_catid]['field']);
                    $table_more = $table.'_category_data'; // 栏目附加表
                    $where = $this->_set_where_field_prefix($where, $tableinfo[$table_more]['field'], $table_more, $fields); // 给条件字段加上表前缀
                    $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo[$table_more]['field'], $table_more); // 给显示字段加上表前缀
                    $system['order'] = $this->_set_order_field_prefix($system['order'], $tableinfo[$table_more]['field'], $table_more); // 给排序字段加上表前缀
                    $sql_from.= " LEFT JOIN $table_more ON `$table_more`.`id`=`$table`.`id`"; // sql的from子句
                }

                // 关联表
                if ($system['join'] && $system['on']) {
                    $table_more = $this->db->dbprefix($system['join']); // 关联表
                    if (!$tableinfo[$table_more]) {
                        return $this->_return('数据不存在');
                    }
                    list($a, $b) = explode(',', $system['on']);
                    $b = $b ? $b : $a;
                    $where = $this->_set_where_field_prefix($where, $tableinfo[$table_more]['field'], $table_more); // 给条件字段加上表前缀
                    $system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo[$table_more]['field'], $table_more); // 给显示字段加上表前缀
                    $system['order'] = $this->_set_order_field_prefix($system['order'], $tableinfo[$table_more]['field'], $table_more); // 给排序字段加上表前缀
                    $sql_from.= ' LEFT JOIN `'.$table_more.'` ON `'.$table.'`.`'.$a.'`=`'.$table_more.'`.`'.$b.'`';
                }

                $total = 0;
                $sql_limit = $pages = '';
                $sql_where = $this->_get_where($where, $fields); // sql的where子句

                // 推荐位调用
                if ($system['flag']) {
                    $_w = strpos($system['flag'], ',') ? '`flag` IN ('.$system['flag'].')' : '`flag`='.(int)$system['flag'];
                    $_i = $this->_query("select id from {$table}_flag where ".$_w, $system['site'], $system['cache']);
                    $in = array();
                    foreach ($_i as $t) {
                        $in[] = $t['id'];
                    }
                    if (!$in) {
                        return $this->_return(L('没有数据'));
                    }
                    $sql_where = ($sql_where ? $sql_where.' AND' : '')."`$table`.`id` IN (".implode(',', $in).")";
                    unset($_w, $_i, $in);
                }

                if ($system['page']) {
                    $page = max(1, (int)$_GET['page']);
                    if (is_numeric($system['catid'])) {
                        $urlrule = dr_category_url($module, $module['category'][$system['catid']], '{page}');
                        if ($this->_tname == 'mobile') {
                            $pagesize = $system['pagesize'] ? (int)$system['pagesize'] : (int)$module['category'][$system['catid']]['setting']['template']['mpagesize'];
                        } else {
                            $pagesize = $system['pagesize'] ? (int)$system['pagesize'] : (int)$module['category'][$system['catid']]['setting']['template']['pagesize'];
                        }
                    }
                    $pagesize = $pagesize ? $pagesize : 10;
                    $sql = "SELECT count(*) as c FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ORDER BY NULL";
                    $row = $this->_query($sql, $system['site'], $system['cache'], FALSE);
                    $total = (int)$row['c'];
                    if (!$total) {
                        return $this->_return(L(L('没有数据')), $sql, 0);
                    }
                    $sql_limit = 'LIMIT '.$pagesize * ($page - 1).','.$pagesize;
                    $pages = $this->_get_pagination($urlrule, $pagesize, $total);
                } elseif ($system['limit']) {
                    $sql_limit = "LIMIT {$system['limit']}";
                }

                $sql = "SELECT ".($system['field'] ? $system['field'] : '*')." FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "").($system['order'] == "null" || !$system['order'] ? "" : " ORDER BY {$system['order']}")." $sql_limit";
                
                $data = $this->_query($sql, $system['site'], $system['cache']);

                // 缓存查询结果
                $name = md5($sql);
                $cache = $this->get_cache_data($name);
                if (!$cache && is_array($data)) {
                    foreach ($data as $i => $t) {
                        $data[$i] = $this->field_format_value($fields, $t, 1, $module['dirname']);
                    }
                    $cache = $system['cache'] ? $this->set_cache_data($name, $data, $system['cache']) : $data;
                }

                return $this->_return($cache ? $cache : $data, $sql, $total, $pages, $pagesize);
                break;
            
            case 'block':
                
                return $this->_return(array(block_get($system['block'])));
                break;
                
            default :
                return $this->_return(L('参数错误'));
                break;
        }
    }
    
    private function _return($data = [], $sql = '', $total = 0, $pages = [], $pagesize = 0) {

        $msg = '';
        
        if (!is_array($data)) {
            $msg = $data;
            $data = [];
        }

        $total = isset($total) ? $total : count($data);
        $code = $total ? $total : 0;
        $page = max(1, (int)$_GET['page']);
        
        $result['page'] = $page;
        $result['pages'] = $pages;
        $result['pagesize'] = $pagesize;
        $result['total'] = $total;
        $result['list'] = $data;
        
        if(SYS_DEBUG){
            $result['sql'] = $sql;
        }
        
        $this->json($code, $result, $msg);
    }
    
    // 给条件字段加上表前缀
    private function _set_where_field_prefix($where, $field, $prefix, $myfield = array()) {
        if ($where) {
            foreach ($where as $i => $t) {
                if (isset($field[$t['name']])) {
                    $where[$i]['use'] = 1;
                    $where[$i]['name'] = "`$prefix`.`{$t['name']}`";
                    if ($myfield && $myfield[$t['name']]['fieldtype'] == 'Linkage') {
                        $data = dr_linkage($myfield[$t['name']]['setting']['option']['linkage'], $t['value']);
                        if ($data) {
                            if ($data['child']) {
                                $where[$i]['adj'] = 'IN';
                                $where[$i]['value'] = $data['childids'];
                            } else {
                                $where[$i]['value'] = intval($data['ii']);
                            }
                        }
                    }
                } else {
                    $where[$i]['use'] = $t['use'] ? 1 : 0;
                }
            }
        }
        return $where;
    }

    // 给显示字段加上表前缀
    private function _set_select_field_prefix($select, $field, $prefix) {

        $select = str_replace('DISTINCT_', 'DISTINCT ', $select);

        if ($select) {
            $array = explode(',', $select);
            foreach ($array as $i => $t) {
                isset($field[$t]) && $array[$i] = "`$prefix`.`$t`";
            }
            return implode(',', $array);
        }

        return $select;
    }

    // 给排序字段加上表前缀
    private function _set_order_field_prefix($order, $field, $prefix) {

        if ($order) {
            if (strpos(($order), 'instr("') === 0) {
                return $order;
            }
            if (in_array(strtoupper($order), array('RAND()', 'RAND'))) {
                return 'RAND()';
            } else {
                // 字段排序
                $my = array();
                $array = explode(',', $order);
                foreach ($array as $i => $t) {
                    if (strpos($t, '`') !== false) {
                        $my[$i] = $t;
                        continue;
                    }
                    $a = explode('_', $t);
                    $b = end($a);
                    if (in_array(strtolower($b), array('desc', 'asc'))) {
                        $a = str_replace('_'.$b, '', $t);
                    } else {
                        $a = $t;
                        $b = '';
                    }
                    $b = strtoupper($b);
                    if (isset($field[$a])) {
                        $my[$i] = "`$prefix`.`$a` ".($b ? $b : "DESC");
                    } elseif (isset($field[$a.'_lat']) && isset($field[$a.'_lng'])) {
                        if ($this->my_position) {
                            $this->pos_order = $a;
                            $my[$i] = $a.' ASC';
                        } else {
                            $this->msg('没有定位到您的坐标');
                        }
                    }
                }
                return $my ? implode(',', $my) : NULL;
            }
        }

        return NULL;
    }
    
    // 条件子句格式化
    private function _get_where($where) {

        if ($where) {
            $string = '';
            foreach ($where as $i => $t) {
                if (isset($t['use']) && $t['use'] == 0 || !strlen($t['value'])) {
                    continue;
                }
                $join = $string ? ' AND' : '';
                switch ($t['adj']) {
                    case 'LIKE':
                        $string.= $join." {$t['name']} LIKE \"".safe_replace($t['value'])."\"";
                        break;

                    case 'IN':
                        $string.= $join." {$t['name']} IN (".safe_replace($t['value']).")";
                        break;

                    case 'NOTIN':
                        $string.= $join." {$t['name']} NOT IN (".safe_replace($t['value']).")";
                        break;

                    case 'NOT':
                        $string.= $join.(is_numeric($t['value']) ? " {$t['name']} <> ".$t['value'] : " {$t['name']} <> \"".($t['value'] == "''" ? '' : safe_replace($t['value']))."\"");
                        break;

                    case 'BETWEEN':
                        $string.= $join." {$t['name']} BETWEEN ".str_replace(',', ' AND ', $t['value'])."";
                        break;

                    case 'BEWTEEN':
                        $string.= $join." {$t['name']} BETWEEN ".str_replace(',', ' AND ', $t['value'])."";
                        break;

                    case 'BW':
                        $string.= $join." {$t['name']} BETWEEN ".str_replace(',', ' AND ', $t['value'])."";
                        break;

                    default:
                        if (strpos($t['name'], '`thumb`')) {
                            $t['value'] == 1 ? $string.= $join." {$t['name']}<>''" : $string.= $join." {$t['name']}=''";
                        } else {
                            $string.= $join.(is_numeric($t['value']) ? " {$t['name']} = ".$t['value'] : " {$t['name']} = \"".($t['value'] == "''" ? '' : safe_replace($t['value']))."\"");
                        }
                        break;
                }
            }
            return trim($string);
        }

        return 1;
    }
    
    private function _query($sql, $site, $cache, $all = TRUE) {

        // 数据库对象
        $db = $this->db;
        // 缓存存在时读取缓存文件
        if ($cache && $data = $this->get_cache_data($sql)) {
            return $data;
        }

        // 执行SQL
        $db->db_debug = FALSE;
        $query = $db->query($sql);

        if (!$query) {
            return 'SQL查询解析不正确：'.$sql;
        }

        // 查询结果
        $data = $all ? $query->result_array() : $query->row_array();

        // 开启缓存时，重新存储缓存数据
        $cache && $this->set_cache_data($cname, $data, $sql);

        $db->db_debug = TRUE;

        return $data;
    }
    
    public function test(){
        $this->models('module/comment')->get_total(1);
    }
}