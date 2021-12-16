<?php

/* v3.1.0  */

class Cart extends M_Controller {

    /**
     * 购物车
     */
    public function index() {

		if (IS_AJAX || IS_POST) {
			$ids = $this->input->post('ids');
            if (!$ids) {
                $this->msg(L('没有选择购物车中的商品'));
            }
			// 购买页面地址
            $url = SITE_URL.'index.php?s='.APP_DIR.'&oid='.@implode('-', $ids);
            if (IS_AJAX) {
                exit(dr_json(1, $url));
            }
            // 跳转页面
            redirect($url, 'refresh');
            exit;
		} else {
			$this->template->assign(array(
				'meta_title' => $this->mconfig['config']['cart_title'] ? $this->mconfig['config']['cart_title'] : '我的购物车',
				'meta_keywords' => SITE_KEYWORDS,
				'meta_description' => SITE_DESCRIPTION,
			));
			$this->template->display('cart.html');
		}
    }
	
	// 删除购物车商品
	public function delete() {

        $table = $this->models('Module/order')->prefix.'cart';
        $data = $this->db->where('id', (int)$this->input->get('id'))->get($table)->row_array();
        if (!$data) {
            $this->return_jsonp(dr_json(0, L('购物车记录不存在')));
        }

        if ($this->uid != $data['uid']) {
            $this->return_jsonp(dr_json(0, L('无权操作其他人的订单')));
        }

        $this->db->where('id', $data['id'])->delete($table);

        $this->return_jsonp(dr_json(1, 'ok'));
    }

	// 删除购物车商品 All
	public function delete_all() {

        $ids = $this->input->post('ids');
        if (!$ids) {
            $this->return_jsonp(dr_json(0, L('没有选择商品')));
        }
        $_ids = array();
        foreach ($ids as $t) {
            if (intval($t)) {
                $_ids[] = (int)$t;
            }
        }
        if (!$_ids) {
            $this->return_jsonp(dr_json(0, L('没有选择商品')));
        }

        $table = $this->models('Module/order')->prefix.'cart';
        $data_all = $this->db->where_in('id', $_ids)->get($table)->result_array();
        if (!$data_all) {
            $this->return_jsonp(dr_json(0, L('购物车记录不存在')));
        }

        $delete = array();
        foreach ($data_all as $data) {
            if ($this->uid != $data['uid']) {
                $this->return_jsonp(dr_json(0, L('无权操作其他人的订单')));
            }
            $delete[] = $data['id'];
        }

        $this->db->where_in('id', $delete)->delete($table);

        $this->return_jsonp(dr_json(1, 'ok'));
    }

	// 更新商品数量
	public function update_num() {

		$id = (int)$this->input->get('id');
		$num = max(0, (int)$this->input->get('num'));

        // 购物车数据
		$data = $this->models('Module/order')->get_cart_row($this->uid, $id);
		if (!$data) {
            $this->return_jsonp(dr_json(0, L('购物车记录不存在')));
		} elseif ($this->uid != $data['uid']) {
            $this->return_jsonp(dr_json(0, L('无权操作其他人的订单')));
        } elseif ($num == 0) {
		    // 删除
            $this->db->where('uid', $this->uid)->where('id', $data['id'])->delete($this->models('Module/order')->prefix.'cart');
            $this->return_jsonp(dr_json(1, L('移除购物车')));
        }

        // 检测商品数据
        $data['quantity'] = $num;
        $error = $this->models('Module/order')->get_goods($data);
        if (!is_array($error)) {
            return $this->return_jsonp(dr_json(0, $error));
        }

		// 更新入库
		$this->db->where('id', $id)->update($this->models('Module/order')->prefix.'cart', array(
			'quantity' => $num,
		));

        $this->return_jsonp(dr_json(1, 'ok'));
		
	}
	
	// 动态获取订单列表数据
	public function ajax_data() {

        ob_start();
		list($list, $total, $url) = $this->models('Module/order')->get_cart($this->uid, $this->agent);
		$this->template->assign(array(
			'order_url' => $url,
			'order_list' => $list,
			'order_total' => $total,
		));
		$this->template->display('cart_data.html');
        $html = ob_get_contents();
        ob_clean();

        $this->return_jsonp(json_encode(array('html' => $html)));
	}

	// 动态获取订单列表数据2
	public function ajax() {

        ob_start();
		list($list, $total, $url) = $this->models('Module/order')->get_cart($this->uid, $this->agent);
		$this->template->assign(array(
			'order_url' => $url,
			'order_list' => $list,
			'order_total' => $total,
		));
		$this->template->display('cart_ajax.html');
        $html = ob_get_contents();
        ob_clean();

        $this->return_jsonp(json_encode(array('html' => $html)));
	}
	
    /**
     * 购物车的商品数量
     */
    public function nums() {
		$this->return_jsonp(dr_json(1, $this->models('Module/order')->get_cart_nums($this->uid, $this->agent)));
	}
	
    /**
     * 添加购物车
     */
    public function add() {

        if (!$this->uid) {
            echo safe_replace($this->input->get('callback', TRUE)).'('.dr_json(0, L('请登录之后再操作')).')';exit;
        }

        $cid = intval($this->input->get('cid'));
		$mid = safe_replace($this->input->get('mid'));
		$quantity = max(1, intval($this->input->get('num')));
		$specification = safe_replace($this->input->get('spec'));

        // 添加的数据格式
        $add = array(
            'mid' => $mid,
            'cid' => $cid,
            'uid' => $this->uid,
            'agent' => $this->agent,
            'quantity' => $quantity,
            'specification' => $specification,
        );

        // 验证模块数据
        $data = $this->models('Module/order')->get_goods($add);
        if (!is_array($data)) {
            echo safe_replace($this->input->get('callback', TRUE)).'('.dr_json(0, $data).')';exit;
        }

        if ($data['uid'] == $this->uid) {
            echo safe_replace($this->input->get('callback', TRUE)).'('.dr_json(0, L('不允许购买自己的商品')).')';exit;
        }

        $add['sell'] = $data['uid'];

		// 添加购物车
		$num = $this->models('Module/order')->add_cart($add);
		echo safe_replace($this->input->get('callback', TRUE)).'('.dr_json(1, $num).')';exit;
    }


}