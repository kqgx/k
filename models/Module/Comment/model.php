<?php

class Module_Comment_model extends CI_Model {

    public $link;
    public $name;
    public $prefix;
    private $_tableid;

    public function __construct() {
        parent::__construct();
        $this->prefix = $this->link->dbprefix(SITE_ID.'_'.APP_DIR);
    }

    // 设置模块操作评论
    public function module($dir, $name) {
        $this->prefix = $this->link->dbprefix(SITE_ID.'_'.APP_DIR);
    }
    
    // 获取评论总数
    public function get_total($cid) {
        $data = $this->link->where('cid', $cid)->get($this->prefix.'_comment_index')->row_array();
        return (int)$data['comments'];
    }

    // 获取评论数
    public function total_info($cid) {
        return $this->link->where('cid', $cid)->get($this->prefix.'_comment_index')->row_array();
    }

    // 评论索引获取表名称
    public function get_table($cid, $is_all = 0) {

        // 评论索引不存在时就创建新的评论索引记录
        $row = $this->link->where('cid', $cid)->get($this->prefix.'_comment_index')->row_array();
        if (!$row) {
            // 插入索引表数据
            $this->link->insert($this->prefix.'_comment_index', array(
                'cid' => $cid,
                'oppose' => 0,
                'support' => 0,
                'tableid' => 0,
                'comments' => 0,
            ));
            $id = $this->link->insert_id();
            if ($this->cconfig['value']['fenbiao']['use']) {
                // 以5w左右数据量无限分表
                $tableid = floor($id/50000);
                if (!$this->db->query("SHOW TABLES LIKE '%".$this->prefix.'_comment_data_'.$tableid."%'")->row_array()) {
                    // 附表不存在时创建附表
                    $sql = $this->db->query("SHOW CREATE TABLE `{$this->prefix}_comment_data_0`")->row_array();
                    $this->db->query(str_replace($sql['Table'], $this->prefix.'_comment_data_'.$tableid, $sql['Create Table']));
                }
            } else {
                $tableid = 0;
            }
        } else {
            $tableid = (int)$row['tableid'];
        }

        $this->_tableid = $tableid;

        return $is_all ? array($this->prefix.'_comment_data_'.$tableid, $row) : $this->prefix.'_comment_data_'.$tableid;
    }

    // 获取主数据
    public function get_data($cid) {

        if (!$cid) {
            return;
        }
        
        $data = $this->link->where('id', $cid)->get($this->prefix)->row_array();
                
        return $data;
    }


    // 需要审核的评论
    public function verify($table, $id) {

        if (!$table || !$id) {
            return;
        }

        $row = $this->link->where('id', $id)->get($table)->row_array();
        $cid = (int)$row['cid'];
        $uid = (int)$row['uid'];
        $data = $this->get_data($cid);;
        if (!$row || !$data || $row['status']) {
            return;
        }

        // 变更审核状态
        $this->link->where('id', $id)->update($table, array('status' => 1));
        
        if ($row['reply']) {
            $this->models('member/notice')->add($row['uid'], 2, L('您的评论被人回复，<a href="%s" target="_blank">查看详情</a>', $data['url'].'#comment-'.$id));
        } else {
            $this->models('member/notice')->add($data['uid'], 2, L('您有新的评论，<a href="%s" target="_blank">查看详情</a>', $data['url'].'#comment-'.$id));
        }

        $markrule = $this->models('member')->get_markrule($row['uid']);
        if ($markrule && $row['uid']) {
            // 我的评论
            $my = $this->link->where('cid', $cid)->where('uid', $uid)->get($this->prefix.'_comment_my')->row_array();
            if ($my) {
                // 更新评论数据
                $this->link->where('id', $my['id'])->update($this->prefix.'_comment_my', array(
                    'url' => $data['url'],
                    'title' => $data['title'],
                    'comments' => (int)$my['comments'] + 1
                ));
            } else {
                $this->link->insert($this->prefix.'_comment_my', array(
                    'cid' => $cid,
                    'uid' => $uid,
                    'url' => $data['url'],
                    'title' => $data['title'],
                    'comments' => 1
                ));
            }
        }

        // 更新数量
        $this->link->where('id', $cid)->set('comments', 'comments+1', false)->update($this->prefix);
        $this->link->where('cid', $cid)->set('comments', 'comments+1', false)->update($this->prefix.'_comment_index');

        // 回复评论时，将主题设置为存在回复状态
        $row['reply'] && $this->link->where('id', $row['reply'])->update($table, array(
            'in_reply' => 1,
        ));
    }

    // 发布评论
    public function add($uid, $data, $my = array()) {

        $cid = (int)$data['cid'];
        if (!$cid) {
            return 0;
        }
        $rid = (int)$data['rid'];
        $table = $this->get_table($cid);
        $m = $this->uid == $uid ? $this->member : dr_member_info($uid);
        
        if ($rid && $row = $this->link->where('id', $rid)->get($table)->row_array()) {
            $row['reply'] && $rid = $row['reply'];
            // 提醒被回复者
            !$data['verify'] && $this->models('member/notice')->add($row['uid'], 2, L('您的评论被人回复，<a href="%s" target="_blank">查看详情</a>', $data['url']));
        } else {
            // 提醒作者被评论
            !$data['verify'] && $this->models('member/notice')->add($data['uid'], 2, L('您有新的评论，<a href="%s" target="_blank">查看详情</a>', $data['url']));
        }

        $insert = array();
        $insert['cid'] = $cid;
        $insert['url'] = $data['url'];
        $insert['title'] = $data['title'];
        $insert['uid'] = $uid;
        $insert['reply'] = $rid;
        $insert['status'] = $data['verify'] ? 0 : 1;
        $insert['author'] = $m ? $m['username'] : '游客';
        $insert['content'] = $data['content'];
        $insert['support'] = $insert['oppose'];
        $insert['inputip'] = $this->input->ip_address();
        $insert['inputtime'] = SYS_TIME;

        // 自定义字段入库
        isset($my[1]) && count($my[1]) && $insert = array_merge($insert, $my[1]);

        // 数据插入评论表
        $this->link->insert($table, $insert);
        $insert['id'] = $rid = $this->link->insert_id();

        // 需要审核时直接返回
        if (!$insert['status']) {
            $this->models('member')->admin_notice('content', '新评论审核', $this->uri.'show/tid/'.$this->_tableid.'/id/'.$insert['id']);
            return $rid;
        }

        // 回复评论时，将主题设置为存在回复状态
        $insert['reply'] && $this->link->where('id', $insert['reply'])->update($table, array(
            'in_reply' => 1,
        ));

        // 我的评论
        if ($this->uid) {
            $my = $this->link->where('cid', $cid)->where('uid', $uid)->get($this->prefix.'_comment_my')->row_array();
            if ($my) {
                // 更新评论数据
                $this->link->where('id', $my['id'])->update($this->prefix.'_comment_my', array(
                    'url' => $data['url'],
                    'title' => $data['title'],
                    'comments' => (int)$my['comments'] + 1
                ));
            } else {
                $this->link->insert($this->prefix.'_comment_my', array(
                    'cid' => $cid,
                    'uid' => $uid,
                    'url' => $data['url'],
                    'title' => $data['title'],
                    'comments' => 1
                ));
            }
        }

        // 更新数量
        $this->link->where('id', $cid)->set('comments', 'comments+1', false)->update($this->prefix);
        $this->link->where('cid', $cid)->set('comments', 'comments+1', false)->update($this->prefix.'_comment_index');

        return $rid;
    }

    // 删除评论
    public function del($rid, $cid, $index = array()) {

        if (!$rid) {
            return;
        }

        if (!$index) {
            $index = $this->link->where('cid', $cid)->get($this->prefix.'_comment_index')->row_array();
            if (!$index) {
                return;
            }
        }

        $table = $this->prefix.'_comment_data_'.intval($index['tableid']);
        $data = $this->link->where('id', $rid)->get($table)->row_array();
        if (!$data) {
            return;
        }

        // 删除评论数据
        $this->link->where('id', $rid)->delete($table);
        // 删除表对应的附件
        $this->models('system/attachment')->delete_for_table($table.'-'.$rid);
        $this->link->where('reply', $rid)->delete($table);

        // 统计评论总数
        $comments = $this->link->where('cid', $cid)->where('status', 1)->count_all_results($table);
        $this->link->where('id', $index['id'])->update($this->prefix.'_comment_index', array(
            'comments' => $comments
        ));
        $this->link->where('id', (int)$data['cid'])->update($this->prefix, array(
            'comments' => $comments,
        ));

        // 更新我的评论
        if ($data['uid']) {
            $my = $this->link->where('cid', $cid)->where('uid', $data['uid'])->get($this->prefix.'_comment_my')->row_array();
            if ($my) {
                // 更新评论数据
                $comments = $this->link->where('cid', $cid)->where('uid', $data['uid'])->where('status', 1)->count_all_results($table);
                $this->link->where('id', $my['id'])->update($this->prefix.'_comment_my', array(
                    'comments' => $comments
                ));
            }
        }
    }
    
    // 安装评论模块
    public function install() {

        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->prefix}_comment_my` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
			  `uid` mediumint(8) unsigned NOT NULL COMMENT 'uid',
			  `title` varchar(250) DEFAULT NULL COMMENT '内容标题',
			  `url` varchar(250) DEFAULT NULL COMMENT 'URL地址',
			  `comments` int(10) unsigned DEFAULT '0' COMMENT '评论数量',
			  PRIMARY KEY (`id`),
			  KEY `cid` (`cid`),
			  KEY `uid` (`uid`),
			  KEY `comments` (`comments`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '我的评论表';
		"));

        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->prefix}_comment_index` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
			  `cid` int(10) unsigned NOT NULL COMMENT '内容id',
			  `support` int(10) unsigned DEFAULT '0' COMMENT '支持数',
			  `oppose` int(10) unsigned DEFAULT '0' COMMENT '反对数',
			  `comments` int(10) unsigned DEFAULT '0' COMMENT '评论数',
			  `tableid` smallint(5) unsigned DEFAULT '0' COMMENT '附表id',
			  PRIMARY KEY (`id`),
			  KEY `cid` (`cid`),
			  KEY `support` (`support`),
			  KEY `oppose` (`oppose`),
			  KEY `comments` (`comments`),
			  KEY `tableid` (`tableid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '评论索引表';
		"));

        $this->link->query(trim("
			CREATE TABLE IF NOT EXISTS `{$this->prefix}_comment_data_0` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
			  `cid` int(10) unsigned NOT NULL COMMENT '关联id',
			  `uid` mediumint(8) unsigned DEFAULT '0' COMMENT '会员ID',
			  `url` varchar(250) DEFAULT NULL COMMENT '主题地址',
			  `title` varchar(250) DEFAULT NULL COMMENT '主题名称',
			  `author` varchar(250) DEFAULT NULL COMMENT '评论者',
			  `content` text COMMENT '评论内容',
			  `support` int(10) unsigned DEFAULT '0' COMMENT '支持数',
			  `oppose` int(10) unsigned DEFAULT '0' COMMENT '反对数',
			  `reply` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复id',
			  `in_reply` TINYINT(1) UNSIGNED DEFAULT '0' COMMENT '是否存在回复',
			  `status` smallint(1) unsigned DEFAULT '0' COMMENT '审核状态',
			  `inputip` varchar(50) DEFAULT NULL COMMENT '录入者ip',
			  `inputtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '录入时间',
			  PRIMARY KEY (`id`),
			  KEY `uid` (`uid`),
			  KEY `cid` (`cid`),
			  KEY `reply` (`reply`),
			  KEY `support` (`support`),
			  KEY `oppose` (`oppose`),
			  KEY `status` (`status`),
			  KEY `inputtime` (`inputtime`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '评论内容表';
		"));
    }
    
    // 卸载评论模块
    public function uninstall() {
        $this->link->query("DROP TABLE IF EXISTS `{$this->prefix}_comment_my`");
        $this->link->query("DROP TABLE IF EXISTS `{$this->prefix}_comment_index`");
        for ($i = 0; $i < 100; $i ++) {
            if (!$this->link->query("SHOW TABLES LIKE '".$this->prefix.'_comment_data_'.$i."'")->row_array()) {
                break;
            }
            $this->link->query('DROP TABLE IF EXISTS '.$this->prefix.'_comment_data_'.$i);
        }
    }
}