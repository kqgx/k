<?php

class System_Wechat_User_model extends CI_Model {

    protected $table = 'wechat_user';

    public function create(array $user, int $uid = null)
    {
        $now = time();
        $this->db->insert($this->table, [
            'uid' => $uid,
            'openid' => $user['openid'],
            'unionid' => $user['unionid'] ?? null,
            'nickname' => $user['nickname'],
            'sex' => [0 => 'unknown', 1 => 'male', 2 => 'female'][$user['sex']],
            'avatar_url' => $user['headimgurl'],
            'country' => $user['country'],
            'city' => $user['city'],
            'province' => $user['province'],
            'privilege' => json_encode($user['privilege'] ?? []),
            'create_time' => $now,
            'update_time' => $now,
            'subscribe_time' => $user['subscribe_time'] ?? 0
        ]);
        return $this->db->insert_id();
    }

    public function getItem(int $id)
    {
        return $this->find('id', $id);
    }

    public function getByUID(int $uid)
    {
        return $this->find('uid', $uid);
    }

    public function getByOpenid(string $openid)
    {
        return $this->find('openid', $openid);
    }

    public function getByUnionID(string $unionID)
    {
        return $this->find('unionid', $unionID);
    }

    public function bindUID(int $id, int $uid)
    {
        return $this->db->set('uid', $uid)->set('update_time', time())->where('id', $id)->update($this->table);
    }

    protected function find(string $field, $value)
    {
        $data = $this->db->where($field, $value)->get($this->table)->row_array();
        if ($data) {
            $data['privilege'] = json_decode($data['privilege'], true);
        }
        return $data;
    }
}
