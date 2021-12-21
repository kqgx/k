<?php

class Excel extends M_Controller {

    public $tableinfo;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->tableinfo = $this->get_cache('table');
        if (!$this->tableinfo) {
            $this->tableinfo = $this->models('system')->cache(); // 表结构缓存
        }
        $this->template->assign(array(
            'menu2' => $this->get_menu_v3(array(
                L('Excel导出') => array(dr_url(APP_DIR.'/excel/index'), 'reply-all'),
                L('Excel导入') => array(dr_url(APP_DIR.'/excel/import'), 'sign-in'),
            )),
            'tableinfo' => $this->tableinfo,
        ));
    }

    /**
     * 导出
     */
    public function index() {

        $action = $this->input->get('action');

        if ($action == 'export') {
            $table = $this->input->get('table');
            if (IS_POST) {
                $field = $name = $func = array();
                $total = $this->input->post('total');
                $where = $this->input->post('where');
                $data = $this->input->post('data');
                $ids = $this->input->post('ids');
                !$ids && $this->admin_msg(L('无可用字段'));
                foreach ($ids as $id) {
                    $field[] = $id;
                    $name[] = $data[$id]['name'];
                    $func[] = $data[$id]['func'];
                }
                $where && $this->db->where($where);
                $count = $this->db->count_all_results($table);
                !$count && $this->admin_msg(L('查询导出结果为空'));
                !$total && $total = $count;

                require APPPATH.'libraries/Classes/Init.php';

                $objPHPExcel = new PHPExcel();
                $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                            ->setLastModifiedBy("Maarten Balliauw")
                            ->setTitle("Office 2007 XLSX Test Document")
                            ->setSubject("Office 2007 XLSX Test Document")
                            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                            ->setKeywords("office 2007 openxml php")
                            ->setCategory("Test result file");
                $obj = $objPHPExcel->setActiveSheetIndex(0);
                $abc = str_split(strtoupper('abcdefghijklmnopqrstuvwxyz'), 1);
                foreach ($field as $i => $t) {
                    $obj->setCellValue($abc[$i].'1', $name[$i] ? $name[$i] : $t);
                }

                $where && $this->db->where($where);
                $data = $this->db->limit($total)->get($table)->result_array();
                foreach ($data as $r => $t) {
                    $obj = $objPHPExcel->setActiveSheetIndex(0);
                    foreach ($field as $i => $f) {
                        $value = $t[$f];
                        if (isset($func[$i]) && $func[$i] && function_exists($func[$i])) {
                            $value = call_user_func($func[$i], $value);
                        }
                        $obj->setCellValue($abc[$i].($r+2), $value);
                    }
                }
                $objPHPExcel->getActiveSheet()->setTitle($table);
                $objPHPExcel->setActiveSheetIndex(0);

                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="'.$table.'-'.$total.'.xls"');
                header('Cache-Control: max-age=0');
                header('Cache-Control: max-age=1');

                header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
                header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
                header ('Pragma: public'); // HTTP/1.0

                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                $objWriter->save('php://output');
            } else {
                $this->template->assign(array(
                    'table' => $table,
                ));
                $this->template->display('export.html');
            }
        } else {
            if (IS_POST) {
                $table = $this->input->post('table');
                if (!$this->tableinfo[$table]) {
                    $this->admin_msg(L('数据表不存在'));
                } elseif (!$this->tableinfo[$table]['rows']) {
                    $this->admin_msg(L('数据表中没有任何记录'));
                }
                $this->admin_msg(L('准备中...'), $this->url('excel/index', array('action' => 'export', 'table'=>$table)), 2);
            }
            $this->template->display('config.html');
        }
    }

    public function import() {
        if (IS_POST) {
            $table = $this->input->post('table');
            if ($_FILES["file"]["error"] > 0) {
                $this->admin_msg('文件上传失败: '.$_FILES["file"]["error"]);
            } else {
                $ext = substr(strrchr($_FILES["file"]["name"], '.'), 1);
                $file = APPPATH.'cache/'.SYS_TIME.'.'.$ext;
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $file)) {
                    if (!is_file($file)) {
                        $this->admin_msg('上传文件移动失败');
                    }
                    // 提交参数
                    $ids = $this->input->post('ids');
                    $post = $this->input->post('data');

                    /** Include PHPExcel */
                    require APPPATH.'libraries/Classes/Init.php';
                    //建立reader对象
                    $PHPReader = new PHPExcel_Reader_Excel2007();
                    if(!$PHPReader->canRead($file)){
                        $PHPReader = new PHPExcel_Reader_Excel5();
                        if(!$PHPReader->canRead($file)){
                            $this->admin_msg('不是Excel文件');
                        }
                    }

                    $sheet = max(0, intval($post['id']) - 1);
                    $PHPExcel = $PHPReader->load($file);        //建立excel对象
                    $currentSheet = $PHPExcel->getSheet($sheet);        //**读取excel文件中的指定工作表*/
                    $allColumn = $currentSheet->getHighestColumn();        //**取得最大的列号*/
                    $allRow = $currentSheet->getHighestRow();        //**取得一共有多少行*/
                    $data = array();
                    for($rowIndex=1;$rowIndex<=$allRow;$rowIndex++){        //循环读取每个单元格的内容。注意行从1开始，列从A开始
                        for($colIndex='A';$colIndex<=$allColumn;$colIndex++){
                            $addr = $colIndex.$rowIndex;
                            $cell = $currentSheet->getCell($addr)->getValue();
                            if($cell instanceof PHPExcel_RichText){ //富文本转换字符串
                                $cell = $cell->__toString();
                            } elseif (is_float($cell)) {
                                $time = PHPExcel_Shared_Date::ExcelToPHP($cell);
                                if ($time > 0) {
                                    $cell = gmdate("Y-m-d H:i:s", $time);
                                }
                            }
                            $data[$rowIndex][$colIndex] = $cell;
                        }
                    }

                    if (!$data) {
                        @unlink($file);
                        $this->admin_msg('Excel文件解析数据失败');
                    }

                    $count = 0;
                    // 数据处理
                    foreach ($data as $i => $t) {
                        // 验证行数
                        if ($post['ks'] && $i<$post['ks']) {
                            continue;
                        }
                        // 验证不能为空
                        $yz = 0;
                        $insert = array();
                        foreach ($ids as $id) {
                            if (is_null($t[$post[$id]['excel']])) {
                                $yz = 1;
                                continue;
                            }
                            $value = $t[$post[$id]['excel']];
                            if (isset($post[$id]['func']) && $post[$id]['func'] && function_exists($post[$id]['func'])) {
                                $value = call_user_func($post[$id]['func'], $value);
                            }
                            $insert[$id] = $value;
                        }
                        if ($yz) {
                            continue;
                        }
                        if ($insert) {
                            $this->db->insert($table, $insert);
                            $count ++;
                        }
                    }
                    @unlink($file);
                    $this->admin_msg('共导入'.$count.'个', '', 1);
                } else {
                    @unlink($file);
                    $this->admin_msg('上传失败');
                }
            }

        }

        $this->template->assign(array(
            'table' => $_GET['table'],
            'abcd' => str_split(strtoupper('abcdefghijklmnopqrstuvwxyz'), 1),
        ));
        $this->template->display('import.html');

    }

}