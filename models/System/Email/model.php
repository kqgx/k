<?php

class System_Email_model extends CI_Model {

    public function send($tomail, $subject, $message) {
    
        if (!$tomail || !$subject || !$message) {
            return FALSE;
        }

        $cache = $this->ci->get_cache('email');
        if (!$cache) {
            return NULL;
        }

        $this->load->library('Dmail');
        foreach ($cache as $data) {
            $this->dmail->set(array(
                'host' => $data['host'],
                'user' => $data['user'],
                'pass' => $data['pass'],
                'port' => $data['port'],
                'from' => $data['user'],
            ));
            if ($this->dmail->send($tomail, $subject, $message)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * 邮件队列发送
     *
     * @param	string	$tomail
     * @param	string	$subject
     * @param	string	$message
     * @return  bool
     */
    public function queue($tomail, $subject, $message) {

        if (!$tomail || !$subject || !$message) {
            return FALSE;
        }

        $this->models('system/cron')->add(1, array(
            'tomail' => $tomail,
            'subject' => $subject,
            'message' => $message,
        ));

        return TRUE;
    }
}
