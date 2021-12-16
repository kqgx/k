<?php

class Download extends M_Controller {

    public function index() {

        $id = (int)$this->input->get('id');
        $info = get_attachment($id);

        !$info && $this->msg(L('附件不存在或者已经被删除'));

        // 是否允许下载附件
        if (!$this->uid && !$this->member_rule['is_download']) {
            $this->msg(L('游客不允许下载附件，请登录'), dr_member_url('login/index'), 0, 3);
        } elseif (!$this->member['adminid'] && !$this->member_rule['is_download']) {
            $this->msg(L('您所在的会员组【%s】无权限下载附件', $this->member['groupname']), dr_member_url('login/index'), 0, 3);
        }

        // 虚拟币与经验值检查
        $mark = 'attachment-'.$id;
        $table = $this->db->dbprefix('member_scorelog');
        if ($this->member_rule['download_score']
            && !$this->db->where('type', 1)->where('mark', $mark)->count_all_results($table)) {
            // 虚拟币不足时，提示错误
            $this->member_rule['download_score'] + $this->member['score'] < 0 && $this->admin_msg(L('下载附件需要%s%s', SITE_SCORE, abs($this->member_rule['download_score'])));
            // 虚拟币扣减
            $this->models('member/score')->edit(1, $this->uid, (int)$this->member_rule['download_score'], $mark, L('附件下载'));
        }
        // 经验值扣减
        $this->member_rule['download_experience']
        && !$this->db->where('type', 0)->where('mark', $mark)->count_all_results($table)
        && $this->models('member/score')->edit(0, $this->uid, (int)$this->member_rule['download_experience'], $mark, L('附件下载'));


        $file = $info['attachment'];
        $this->db->where('id', $id)->set('download', 'download+1', FALSE)->update('attachment');

        if (strpos($file, ':/')) {
            //远程文件
            header("Location: $file");
        } else {
            //本地文件
            $file = SYS_UPLOAD_PATH.'/'.str_replace('..', '', $file);
            $file = str_replace('member/uploadfile/member/uploadfile', 'member/uploadfile', $file);
            $name = urlencode(($info['filename'] ? $info['filename'] : $info['filemd5']).'.'.$info['fileext']);
            $this->load->helper('download');
            force_download($name, file_get_contents($file));
        }
    }
}
