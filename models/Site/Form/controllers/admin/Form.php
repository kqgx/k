<?php

class Form extends M_controller {

    public $form;

    public function __construct() {
        parent::__construct();
        $form = str_replace('form_', '', $this->input->get('form'));
        if($form){
            $this->form = is_numeric($form) ? $this->get_cache('form-'.SITE_ID, $form) : $this->get_cache('form-name-'.SITE_ID, $form);
            if (!$this->form) {
    			$this->msg(0, L('表单不存在，请更新表单缓存'));
    		}
            $this->field = array(
                'author' => array(
                    'name' => L('录入作者'),
                    'ismain' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'author',
                    'setting' => array(
                        'option' => array(
                            'width' => 157,
                            'value' => $this->admin['username']
                        ),
                        'validate' => array(
                            'tips' => L('填写录入者的会员名称'),
                            'check' => '_check_member',
                            'required' => 1,
                        )
                    )
                ),
                'inputtime' => array(
                    'name' => L('录入时间'),
                    'ismain' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                    'setting' => array(
                        'option' => array(
                            'width' => 200
                        ),
                        'validate' => array(
                            'required' => 1,
                            'formattr' => '',
                        )
                    )
                ),
                // 'inputip' => array(
                //     'name' => L('客户端IP'),
                //     'ismain' => 1,
                //     'fieldname' => 'inputip',
                //     'fieldtype' => 'Text',
                //     'setting' => array(
                //         'option' => array(
                //             'width' => 200,
                //             'value' => $this->input->ip_address()
                //         ),
                //         'validate' => array(
                //         )
                //     )
                // )
            );
            $this->uriprefix = 'admin/form/form/'.$this->form['table'].'/';
        }
    }
    public function index(){
		$this->render(array(
			'list' => $this->db->get($this->models('site/form')->prefix)->result_array(),
			'menu' => $this->get_menu_v3(array(
				L('表单管理') => array('admin/form/index', 'table'),
				L('添加') => array('admin/form/add', 'plus')
			)),
		), 'form_index.html');
    }
    
	public function data() {
        if (IS_POST && $this->input->post('action')) {
            $table = $this->models('site/form')->prefix.'_'.$this->form['table'];
			if ($this->input->post('action') == 'del') {
				// 删除
				$_ids = $this->input->post('ids');
				foreach ($_ids as $id) {
                    $row = $this->db->where('id', (int)$id)->get($table)->row_array();
                    if ($row) {
                        $this->db->where('id', (int)$id)->delete($table);
                        $this->db->where('id', (int)$id)->delete($table.'_data_'.(int)$row['tableid']);
                        $this->models('system/attachment')->delete_for_table($table.'-'.$id);
                        $this->system_log('删除站点【#'.SITE_ID.'】表单【'.$this->form['table'].'】内容【#'.$id.'】'.$row['title']); // 记录日志
                    }
				}
            } elseif ($this->input->post('action') == 'order') {
				// 修改
				$_ids = $this->input->post('ids');
				$_data = $this->input->post('data');
				foreach ($_ids as $id) {
					$this->db->where('id', (int)$id)->update($table, $_data[$id]);
				}
                $this->system_log('排序站点【#'.SITE_ID.'】表单【'.$this->form['table'].'】内容【#'.@implode(',', $_ids).'】'); // 记录日志
				unset($_ids, $_data);
			}
			$this->msg(1, L('操作成功，正在刷新...'));
		}

        // 重置页数和统计
		IS_POST && $_GET['page'] = $_GET['total'] = 0;

		// 根据参数筛选结果
		$param = $this->input->get(NULL);
		unset($param['s'],$param['c'],$param['m'],$param['d'],$param['page']);
		if ($this->input->post('search')) {
			$search = $this->input->post('data');
			$param['keyword'] = $search['keyword'];
			$param['start'] = $search['start'];
			$param['end'] = $search['end'];
			$param['field'] = $search['field'];
		}

		$menu = array(
			L($this->form['name']) => array(dr_url('form/data', array('form' => $this->form['table'])), $this->form['setting']['icon'] ? str_replace('fa fa-', '', $this->form['setting']['icon']) : 'table'),
			// L('添加') => array(dr_url('admin/form/add', array('form'=>$this->form['table'])), 'plus') // 注释添加按钮
		);
		if ($this->form['table'] == 'need') {
			$cid = $this->input->get('cid');
			$param['cid'] = $cid;
			$menu[$this->form['name']] = array(dr_url('form/data', array('form' => $this->form['table'], 'cid' => $cid)), $this->form['setting']['icon'] ? str_replace('fa fa-', '', $this->form['setting']['icon']) : 'table');
		}
		// 数据库中分页查询
		list($data, $total)	= $this->models('site/form')->limit_page(
            $this->form['table'],
            $param,
            max((int)$_GET['page'], 1),
            (int)$_GET['total']
        );
		$param['total'] = $total;
        $tpl = APPPATH.'views/admin/form_lists_'.$this->form['table'].'.html';
		$this->render(array(
			'mid' => $this->mid,
            'tpl' => str_replace(FCPATH, '/', $tpl),
			'menu' => $this->get_menu_v3($menu),
			'list' => $data,
			'form' => $this->form['table'],
			'param'	=> $param,
			'total' => $total,
            'field' => $this->form['field'] + $this->field,
			'pages'	=> $this->get_pagination(dr_url($this->router->class.'/data', $param), $param['total']),
		), is_file($tpl) ? basename($tpl) : 'form_lists.html');
	}
    
    public function add(){
        if($this->form){
    		if (IS_POST) {
    			$data = $this->validate_filter($this->form['field'] + $this->field);
    			// 验证出错信息
    			if (isset($data['error'])) {
    				$error = $data;
    				$data = $this->input->post('data', TRUE);
    			} else {
    				// 设定文档默认值
                    $data[1]['displayorder'] = 0;
                    $data[1]['uid'] = $data[0]['uid'] = get_member_id($data[1]['author']);
    				// 发布文档
    				if (($id = $this->models('site/form')->new_addc($this->form['table'], $data)) != FALSE) {
    					// 附件归档到文档
    					$this->attachment_handle($this->uid, $this->models('site/form')->prefix.'_'.$this->form['table'].'-'.$id, $this->form['field']);
                        $this->system_log('添加站点【#'.SITE_ID.'】表单【'.$this->form['table'].'】内容【#'.$id.'】'); // 记录日志
                        $this->member_msg(L('操作成功，正在刷新...'), dr_url($this->router->class.'/index'), 1);
    				}
    			}
                $data = $data[0] ? array_merge($data[1], $data[0]) : $data[1];
    			unset($data['id']);
    		}
            $tpl = APPPATH.'views/admin/form_addc_'.$this->form['table'].'.html';
    		$this->render(array(
                'tpl' => str_replace(FCPATH, '/', $tpl),
    			'menu' => $this->get_menu_v3(array(
    				L($this->form['name']) => array($this->uriprefix.'index', $this->form['setting']['icon'] ? str_replace('fa fa-', '', $this->form['setting']['icon']) : 'table'),
    				L('添加') => array($this->uriprefix.'add', 'plus'),
    			)),
                'data' => $data,
    			'error' => $error,
    			'field' => $this->field_input($this->form['field'] + $this->field, $data)
    		), is_file($tpl) ? basename($tpl) : 'form_addc.html');
        } else {
    		if (IS_POST) {
    			$data = $this->input->post('data', true);
    			$result = $this->models('site/form')->add($data);
    			if ($result === TRUE) {
    				$this->models('site/form')->cache();
                    $this->system_log('添加网站表单【#'.$data['table'].'】'); // 记录日志
    				$this->admin_msg(L('操作成功，更新缓存生效'), dr_url('form/index'), 1);
    			}
    		}
    		$this->render(array(
    			'menu' => $this->get_menu_v3(array(
    				L('表单管理') => array('admin/form/index', 'table'),
    				L('添加') => array('admin/form/add', 'plus'),
    				L('更新缓存') => array('admin/form/cache', 'refresh'),
    			)),
    			'data' => $data,
    			'result' => $result,
    		), 'form_add.html');               
        }
    }
    
    public function edit() {
        if($this->form){
		    $id = (int)$this->input->get('id');
    		$table = $this->models('site/form')->prefix.'_'.$this->form['table'];
            // 获取表单数据
    		$data = $this->models('site/form')->get_data($id, $table);
    		!$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
    			
    		if (IS_POST) {
    			$post = $this->validate_filter($this->form['field'] + $this->field);
    			// 验证出错信息
    			if (isset($post['error'])) {
    				$error = $post;
    				$data = $this->input->post('data', TRUE);
    			} else {
    				// 发布文档
                    // $post[1]['uid'] = $post[0]['uid'] = get_member_id($post[1]['author']); // 注释此行防止修改 uid
                    if ($this->models('site/form')->new_editc($id, $this->form['table'], $data['tableid'], $post)) {
    					// 附件归档到文档
    					$this->attachment_handle($this->uid, $table.'-'.$id, $this->form['field']);
                        $this->system_log('修改站点【#'.SITE_ID.'】表单【'.$this->form['table'].'】内容【#'.$id.'】'); // 记录日志
    					$this->member_msg(L('操作成功，正在刷新...'), dr_url($this->router->class.'/data', array('form' => $this->form['table'])), 1);
    				}
    			}
    			$data = $post[0] ? array_merge($post[1], $post[0]) : $post[1];
    			unset($data['id']);
    		}
    
            $tpl = APPPATH.'views/admin/form_addc_'.$this->form['table'].'.html';
			$menu = array(
				L($this->form['name']) => array(dr_url('form/data', array('form' => $this->form['table'])), $this->form['setting']['icon'] ? str_replace('fa fa-', '', $this->form['setting']['icon']) : 'table'),
			);
    		$this->render(array(
                'tpl' => str_replace(FCPATH, '/', $tpl),
    			'menu' => $this->get_menu_v3($menu),
                'data' => $data,
    			'error' => $error,
    			'field' => $this->field_input($this->form['field'] + $this->field, $data),
				'form' => $this->form['table'],
				'cids' => $data['cids'] ?: NULL
    		), is_file($tpl) ? basename($tpl) : 'form_addc.html');                
        } else {
    		$id = (int)$this->input->get('id');
    		$data = $this->db->where('id', $id)->limit(1)->get($this->models('site/form')->prefix)->row_array();
    		if (!$data) {
                $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
            }
    		if (IS_POST) {
    			$data = $this->input->post('data', true);
    			$this->models('site/form')->edit($id, $data);
    			$this->models('site/form')->cache();
    			$this->system_log('修改网站表单【#'.$data['table'].'】'); // 记录日志
    			$this->admin_msg(L('操作成功，更新缓存生效'), dr_url('form/index'), 1);
    		}
    		$data['setting'] = string2array($data['setting']);
    		$this->render(array(
    			'menu' => $this->get_menu_v3(array(
    				L('表单管理') => array('admin/form/index', 'table'),
    				L('添加') => array('admin/form/add', 'plus'),
    				L('更新缓存') => array('admin/form/cache', 'refresh'),
    			)),
    			'data' => $data,
    		), 'form_add.html');                
        }
    }
    
    public function del(){
        $id = (int)$this->input->get('id');
		$this->models('site/form')->del($id);
        $this->system_log('删除网站表单【#'.$id.'】'); // 记录日志
		$this->admin_msg(1, L('操作成功，更新缓存生效'), dr_url('form/index'));
    }
    
    public function cache(){
		$this->models('site/form')->cache($site = isset($_GET['site']) && $_GET['site'] ? (int)$_GET['site'] : SITE_ID);
        $this->admin_msg(1, L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    }

	/**
	 * 分销申请通过
	 */
	public function distribution_application_approved()
	{
		$id = (int)$this->input->get('id', TRUE);
		$table = $this->models('site/form')->prefix.'_'.$this->form['table'];
		$data = $this->models('site/form')->get_data($id, $table, FALSE);
		$this->models('site/form')->distribution_application_approved_model($data);
		$this->msg(1, L('操作成功，正在刷新...'));
	}

	/**
	 * 分销申请驳回
	 */
	public function distribution_application_rejected()
	{
		$id = (int)$this->input->get('id', TRUE);
		$this->models('site/form')->distribution_application_rejected_model($id);
		$this->msg(1, L('操作成功，正在刷新...'));
	}

	/**
	 * 提现申请通过
	 */
	public function withdraw_application_passed()
	{
		$id = (int)$this->input->get('id', TRUE);
		$this->models('site/form')->withdraw_application_status($id, 1);
		$this->msg(1, L('操作成功，正在刷新...'));
	}

	/**
	 * 提现申请驳回
	 */
	public function withdraw_application_rejected()
	{
		$id = (int)$this->input->get('id', TRUE);
		$table = $this->models('site/form')->prefix.'_'.$this->form['table'];
		$data = $this->models('site/form')->get_data($id, $table, FALSE);
		$this->models('member')->modify_money($data['uid'], $data['money']);
		$this->models('site/form')->withdraw_application_status($id, -1);
		$this->msg(1, L('操作成功，正在刷新...'));
	}
}