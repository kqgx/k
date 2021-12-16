<?php

class Pay extends M_Controller {

	/**
	 * 首页
	 */
	public function index() {
	    
        $this->template->assign(array(
			'menu' => $this->get_menu_v3(array(
				L('财务流水') => array('admin/pay/index', 'calculator'),
				L('添加') => array('admin/pay/add_js', 'plus')
			))
		));
		
		// 重置页数和统计
		IS_POST && $_GET['page'] = $_GET['total'] = 0;

		// 根据参数筛选结果
		$param = $this->input->get(NULL, TRUE);
		unset($param['s'], $param['c'], $param['m'], $param['d'], $param['page']);

		// 数据库中分页查询
		list($data, $param) = $this->models('system/pay')->limit_page(
			$param,
			max((int)$this->input->get('page'), 1),
			(int)$this->input->get('total')
		);

		$this->template->assign(array(
			'pay' => [],
			'list' => $data,
			'param'	=> $param,
			'pages'	=> $this->get_pagination(dr_url('admin/pay/'.$this->router->method, $param), $param['total']),
		));
		$this->template->display();
	}

	/**
	 * 充值
	 */
	public function add() {

		if (IS_POST) {
			$data = $this->input->post('data');
			$value = intval($data['value']);
			!$value && $this->msg(0, L('请填写变动数量值'), 'value');
			
			$userinfo = $this->db->where('username', $data['username'])->get('member')->row_array();
			!$userinfo && $this->msg(0, L('会员不存在'));
			
			$uid = intval($userinfo['uid']);
			$data['value'] < 0 && $userinfo['money'] + $data['value'] < 0 && $this->msg(0, L('%s值超出了账户余额', $data['value']));
			
			$this->models('system/pay')->add($uid, $data['value'], $data['note']);
			
			$this->models('member/notice')->add($userinfo['uid'], 1, L('%s变动：%s；本次操作人：%s', SITE_MONEY, $value, $this->member['username']));
			
			$this->system_log('会员【'.$userinfo['username'].'】充值金额【'.$value.'】'); // 记录日志

			$this->msg(1, L('操作成功，正在刷新...'));
		}
		$this->template->display();
	}

    public function setting() {

        $pay = [];
		if (IS_POST) {
            $this->load->library('dconfig');
            $data = $this->input->post('data');
            foreach ($pay as $dir => $t) {
                if (isset($data[$dir])) {
                    $data[$dir]['name'] = $t['name'];
                    $file = WEBPATH.'api/pay/'.$dir.'/config.php';
                    $size = $this->dconfig->file($file) ->note($dir.' 支付接口配置文件')->space(12)->to_require_one($data[$dir], $data[$dir]);
					!$size && $this->admin_msg(L('文件【%s】修改失败，请检查权限', 'api/pay/'.$dir.'/config.php'));
                }
            }
            $setting = $this->input->post('setting');
            asort($setting['order']);
			$this->models('system/pay')->setting($setting);
			$this->models('member')->cache();
            $this->system_log('网银配置'); // 记录日志
            $pay = [];
		}

		$this->template->assign(array(
            'pay' => $pay,
			'menu' => $this->get_menu_v3(array(
				L('网银配置') => array('admin/pay/setting/', 'rmb'),
			)),
			'setting' => $this->models('system/pay')->setting(),
		));
		$this->template->display();
    }

	// 导出
	public function export()
	{
		$param = $this->input->get('param', TRUE);
        $this->db->select('inputtime, uid, value, type, status, note');
		
		isset($param['start']) && $param['start'] && $param['start'] != $param['end'] && $this->db->where('inputtime BETWEEN ' . $param['start'] . ' AND ' . ($param['end'] ? $param['end'] : SYS_TIME));
		strlen($param['status']) > 0 && $this->db->where('status', (int)$param['status']);
		strlen($param['keyword']) > 0 && $this->db->where('(uid in (select uid from ' . $this->db->dbprefix('member') . ' where `username`="' . $param['keyword'] . '"))');
		strlen($param['type']) > 0 && ($param['type'] == 1 ? $this->db->where('value>0') : $this->db->where('value<0'));

		$content = $this->db->get('member_paylog')->result_array();
		foreach ($content as $k => $v) {
			$content[$k]['inputtime'] = dr_date($v['inputtime'], 'Y-m-d H:i:s');
			$content[$k]['uid'] = dr_member_info($v['uid'])['username'];
			switch ($v['status']) {
                case 0:
                    $content[$k]['status'] = L('等待付款');
                    break;
                case 1:
                    $content[$k]['status'] = L('交易成功');
                    break;
                case 2:
                    $content[$k]['status'] = L('交易失败');
                    break;
                default:
                    $content[$k]['status'] = L('未知');
            }
		}
		$data = [
			'inputtime' => '时间',
			'uid' => '会员',
			'value' => '金额',
			'type' => '支付方式',
			'status' => '状态',
			'note' => '备注说明'
		];
        $field=array_keys($data);
        $this->excel_export('财务流水', $field, $data, $content);
	}

	protected function excel_export($title,$field,$name,$data){
        require WEBPATH.'app/excel/libraries/Classes/Init.php';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        
        // 设置表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(50);
        $obj = $objPHPExcel->setActiveSheetIndex(0);
        $abc = get_abc(count($field), str_split(strtoupper('abcdefghijklmnopqrstuvwxyz'), 1));
  
        foreach ($field as $i => $t) {
            $obj->setCellValue($abc[$i].'1', $name[$t]?$name[$t]:$t);
        }

        $row = 1;
        foreach ($data as $r => $t) {
            foreach ($field as $i => $f) {
                $value = $t[$f];
                $obj->setCellValue($abc[$i].($row+1), $value);
            }
            $objPHPExcel->getActiveSheet()->getRowDimension($row+1);
            $row++;
        }
        $obj->setCellValue($abc[count($field)].($row+1), "总计{$row}行");

        $objPHPExcel->getActiveSheet()->setTitle($title);
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title."_".date("YmdHi").'.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
