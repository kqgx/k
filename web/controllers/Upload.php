<?php

class Upload extends M_Controller {

    public function index() {
        
        // 游客不允许上传
        // !$this->member && $this->json(0, L('请先登录'));
        
        $path = SYS_UPLOAD_PATH.'/'.date('Ym', SYS_TIME).'/';
        
        if(!is_dir($path)){
            file_mkdirs($path);
        }
        
        $this->load->library('upload', array(
            'max_size' => (int)10 * 1024,
            'overwrite' => FALSE,
            'file_name' => substr(md5(time()), rand(0, 20), 10),
            'upload_path' => $path,
            'allowed_types' => 'jpg|jpeg|gif|png',
            'file_ext_tolower' => TRUE,
        ));

        if ($this->upload->do_upload(isset($_GET['fname']) ? $_GET['fname'] : 'Filedata')) {
            $result = $this->models('system/attachment')->upload($this->uid, $this->upload->data());
            if(is_array($result)){
                $this->db->where('uid', $_GET['u'])->set('avatar', $result[0])->update('imt_member');
                $this->json(1, $result);
            }
        }
        $this->json(array(0, $this->upload->display_errors()));
    }
}