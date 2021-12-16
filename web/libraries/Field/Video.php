<?php

/* v3.1.0  */

class F_Video extends A_Field {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->name = L('单文件'); // 字段名称
        $this->fieldtype = array('VARCHAR' => '255'); // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
        $this->defaulttype = 'VARCHAR'; // 当用户没有选择字段类型时的缺省值
    }

    /**
     * 字段相关属性参数
     *
     * @param	array	$value	值
     * @return  string
     */
    public function option($option) {

        $data = $this->ci->get_cache('downservers');
        $downservers = '';

        if ($data) {
            $server = isset($option['server']) ? $option['server'] : array();
            foreach ($data as $t) {
                $downservers.= '<label><input '.(@in_array($t['id'], $server) ? 'checked' : '').' type="checkbox" value="'.$t['id'].'" name="data[setting][option][server][]"> '.$t['name'].'</label>';
            }
        }

        $option['width'] = isset($option['width']) ? $option['width'] : 200;
        $option['fieldtype'] = isset($option['fieldtype']) ? $option['fieldtype'] : '';
        $option['uploadpath'] = isset($option['uploadpath']) ? $option['uploadpath'] : '';
        $option['fieldlength'] = isset($option['fieldlength']) ? $option['fieldlength'] : '';
        $option['is_swfupload'] = isset($option['is_swfupload']) ? $option['is_swfupload'] : 0;

        return '<div class="form-group">
                    <label class="col-md-2 control-label">'.L('文件大小').'：</label>
                    <div class="col-md-9">
						<label><input id="field_default_value" type="text" class="form-control" value="'.$option['size'].'" name="data[setting][option][size]"></label>
						<span class="help-block">'.L('单位MB').'</span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.L('扩展名').'：</label>
                    <div class="col-md-9">
                    	<label><input type="text" class="form-control" size="40" name="data[setting][option][ext]" value="'.$option['ext'].'"></label>
						<span class="help-block">'.L('格式：jpg,gif,png,exe,html,php,rar,zip').'</span>
                    </div>
                </div>
				
				<div class="form-group">
                    <label class="col-md-2 control-label">'.L('镜像服务器').'：</label>
                    <div class="col-md-9"><fieldset class="downservers">'.$downservers.'</fieldset></div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.L('本地存储目录').'：</label>
                    <div class="col-md-9">
                    <input type="text" class="form-control" size="50" name="data[setting][option][uploadpath]" value="'.$option['uploadpath'].'">
					<span class="help-block">'.L('目录中不得包含中文 <br />标签介绍：站点id{siteid}、模块目录{module}、年{y}、月{m}、日{d} <br />例如：{siteid}/{module}/test/，将附件保存至：uploadfile/站点/模块目录/test目录/附件名称.扩展名').'</span>
                    </div>
                </div>';
    }

    /**
     * 字段输出
     */
    public function output($value) {
        return string2array($value);
    }

    /**
     * 获取附件id
     */
    public function get_attach_id($value) {

        $data = array();
        if (!$value || !is_numeric($value)) {
            return $data;
        }

        $data[] = $value;

        return $data;
    }

    /**
     * 附件处理
     */
    public function attach($data, $_data) {

        // 新旧数据都无附件就跳出
        if (!$data && !$_data) {
            return NULL;
        }

        // 新旧数据都一样时表示没做改变就跳出
        if ($data === $_data) {
            return NULL;
        }

        // 当无新数据且有旧数据表示删除旧附件
        if (!$data && $_data) {
            return array(
                array(),
                array($_data)
            );
        }

        // 当无旧数据且有新数据表示增加新附件
        if ($data && !$_data) {
            return array(
                array($data),
                array()
            );
        }

        // 剩下的情况就是删除旧文件增加新文件
        return array(
            array($data),
            array($_data)
        );
    }


    /**
     * 字段入库值
     *
     * @param	array	$field	字段信息
     * @return  void
     */
    public function insert_value($field) {
        $data = $this->ci->post[$field['fieldname']];
        if ($data && $data['file']) {
            $value = array();
            if ($data['time']) {
                foreach ($data['time'] as $i => $t) {
                    if ($data['title'][$i]) {
                        $value['point'][$t] = $data['title'][$i];
                    }
                }
            }
            $value['file'] = $data['file'];
            $this->ci->data[$field['ismain']][$field['fieldname']] = array2string($value);
        } else {
            $this->ci->data[$field['ismain']][$field['fieldname']] = '';
        }
    }

    /**
     * 字段表单输入
     *
     * @param	string	$cname	字段别名
     * @param	string	$name	字段名称
     * @param	array	$cfg	字段配置
     * @param	array	$data	值
     * @return  string
     */
    public function input($cname, $name, $cfg, $value = NULL, $id = 0) {
        // 字段显示名称
        $text = (isset($cfg['validate']['required']) && $cfg['validate']['required'] == 1 ? '<font color="red">*</font>' : '').''.$cname.'：';
        // 表单附加参数
        $attr = isset($cfg['validate']['formattr']) && $cfg['validate']['formattr'] ? $cfg['validate']['formattr'] : '';
        // 字段提示信息
        $tips = isset($cfg['validate']['tips']) && $cfg['validate']['tips'] ? '<span class="help-block" id="dr_'.$name.'_tips">'.$cfg['validate']['tips'].'</span>' : '';
        // 禁止修改
        $disabled = !IS_ADMIN && $id && $value && isset($cfg['validate']['isedit']) && $cfg['validate']['isedit'] ? 'disabled' : '';

        // 快速上传
        $url = '/member.php?c=api&m=new_ajax_upload&name='.$name.'&siteid='.SITE_ID.'&count=1&code='.str_replace('=', '', dr_authcode($cfg['option']['size'].'|'.$cfg['option']['ext'].'|'.$this->get_upload_path($cfg['option']['uploadpath']), 'ENCODE'));
        // 文件值
        $my = $file = $info = '';
        $value = string2array($value);
        $my = '<button type="button" style="cursor:pointer;" class="btn red btn-sm" onclick="dr_add_video_'.$name.'()"> <i class="fa fa-flag"></i> '.L('提示点').'</button>';
        if ($value['file']) {
            $file = $value['file'];
            $data = dr_file_info($file);
            if ($data) {
                $my.= '
					<button type="button" style="cursor:pointer;pointer;margin-left: 6px;" class="btn green btn-sm" onclick="dr_show_file_info(\''.$data['id'].'\')"> <i class="fa fa-search"></i> '.L('预览').'</button>
					<button type="button" style="cursor:pointer;margin-left: 6px;"  class="btn red btn-sm" onclick="dr_delete_file2(\''.$name.'\')"> <i class="fa fa-trash"></i> ' . L('删除') . '</button>
					';
            } elseif (is_numeric($file) && !get_attachment($file)) {
                $my = '<span class="badge badge-danger">'.L('文件信息不存在').'</span>';
            }
            unset($data);
        }
        $tsd = '';
        if ($value['point']) {
            $i = 0;
            foreach ($value['point'] as $time => $title) {
                $tsd.= '
						<li id="dr_items_'.$name.'_'.$i.'">
						时间(秒)：<input type="text" class="input-text" style="width:70px;" value="'.$time.'" name="data['.$name.'][time][]">&nbsp;&nbsp;提示文字：<input type="text" class="input-text" style="width:200px;" value="'.$title.'" name="data['.$name.'][title][]\">&nbsp;&nbsp;<a href="javascript:;" onclick="$(\'#dr_items_'.$name.'_'.$i.'\').remove()">'.L('删除').'</a>
						</li>';
                $i++;
            }
        }
        $str = '<div class="row" style="margin:0">
		    <input type="hidden" value="'.$value['file'].'" name="data['.$name.'][file]" id="fileid_'.$name.'" />';
        // 加载js
        if (!defined('FINECMS_FILES_MOBILE')) {
            $str.= '<script type="text/javascript" src="'.THEME_PATH.'js/jquery-ui.min.js"></script>';
            $str.= '<script type="text/javascript">var homeurl = "'.THEME_PATH.'"</script>';
            $str.= '<script type="text/javascript" src="'.THEME_PATH.'js/dmuploader.min.js"></script>
				<style>
				div.uploader {
					height:30px !important;
					line-height:30px;
					width:60px !important;
					background-image: none !important;
					cursor: pointer;
					padding:0px 5px 0 5px;
				}
				.my_list .badge {margin-top: 5px;}
				.my_list {
					margin-top: 0px;padding-left:5px
				}
				.my_upload {
					width:66px!important;
					margin-top: 0px;padding-left:0px
				}
				
				.uploader input {
					position: absolute;
					top: 0;
					right: 0;
					margin: 0;
					border: solid transparent;
					border-width: 0 0 100px 200px;
					opacity: .0;
					filter: alpha(opacity= 0);
					-o-transform: translate(250px,-50px) scale(1);
					direction: ltr;
					cursor: pointer;
				}</style>';
            define('FINECMS_FILES_MOBILE', 1);//防止重复加载JS
        }
        if (!$disabled) {
            // 完整上传
            $furl = '/member.php?c=api&m=upload&name='.$name.'&siteid='.SITE_ID.'&count=1&code='.str_replace('=', '', dr_authcode($cfg['option']['size'].'|'.$cfg['option']['ext'].'|'.$this->get_upload_path($cfg['option']['uploadpath']), 'ENCODE'));

            $str.= '
				<div id="drag-and-drop-zone-'.$name.'" class="col-md-3 btn btn-sm blue uploader">
					<i class="fa fa-cloud-upload"></i> '.L('上传').' <input type="file" name="file">
				</div>';

            $str.= '<div style="margin-left: 10px;" class="col-md-3 my_upload"><button type="button" style="cursor:pointer;"  class="btn blue btn-sm" onclick="dr_upload_file(\''.$name.'\', \''.$furl.'\')"> <i class="fa fa-folder"></i> ' . L('浏览') . '</button></div>';

        }
        $str.= '
          	<div id="dr_my_'.$name.'_list" class="col-md-6 my_list">'.$my.'</div>
          	</div>';
        $str.= '<div style="padding-top: 10px;clear: both;">格式要求：'.$cfg['option']['ext'].'，最多上传 1 个文件，单文件最大 '.$cfg['option']['size'].' MB</div>';
          	$str.='
          	<style>
          	.my_tsd {
          	margin-top:15px;
          	}
          	.my_tsd li {
          	margin-top:10px;
          	}
</style>
            <ul id="'.$name.'-sort-items" class="my_tsd">
            '.$tsd.'
            </ul>
          	<div id="dr_hide_'.$name.'_list" style="display:none"></div>
          	<script type="text/javascript">
          	$("#'.$name.'-sort-items").sortable();
					var id=$("#'.$name.'-sort-items li").size();
					function dr_add_video_'.$name.'() {
						id ++;
						var html = "<li id=\"dr_items_'.$name.'_"+id+"\">";
						html+= "时间(秒)：<input type=\"text\" class=\"input-text\" style=\"width:70px;\" value=\"\" name=\"data['.$name.'][time][]\">&nbsp;&nbsp;";
						html+= "提示文字：<input type=\"text\" class=\"input-text\" style=\"width:200px;\" value=\"\" name=\"data['.$name.'][title][]\">&nbsp;&nbsp;";
						html+= "<a href=\"javascript:;\" onclick=\"$(\'#dr_items_'.$name.'_"+id+"\').remove()\">'.L('删除').'</a>";
						html+= "</li>";
						$("#'.$name.'-sort-items").append(html);
					}
      $("#drag-and-drop-zone-'.$name.'").dmUploader({
        url: "'.$url.'",
        dataType: "json",
        allowedTypes: "*",
        onInit: function(){
        },
        onBeforeUpload: function(id){
            $("#dr_hide_'.$name.'_list").html($("#dr_my_'.$name.'_list").html());
            $("#dr_my_'.$name.'_list").html("<span class=\"badge badge-danger\">0%</span>");
        },
        onNewFile: function(id, file){
          //alert(file);
        },
        onComplete: function(){
          //alert("All pending tranfers completed");
        },
        onUploadProgress: function(id, percent){
            $("#dr_my_'.$name.'_list").html("<span class=\"badge badge-danger\">"+(percent-1)+"%</span>");
        },
        onUploadSuccess: function(id, data){
		  if (data.code == 1) {
		  	dr_tips("上传成功", 3, 1);
		  	$("#fileid_'.$name.'").val(data.id);
		  	$("#dr_my_'.$name.'_list").html("<button type=\"button\" style=\"cursor:pointer;\" class=\"btn green btn-sm\" onclick=\"dr_show_file_info(\'"+data.id+"\')\"> <i class=\"fa fa-search\"></i> '.L('预览').'</button> ");
			$("#dr_my_'.$name.'_list").append("<button type=\"button\" style=\"cursor:pointer;\" class=\"btn red btn-sm\" onclick=\"dr_delete_file2(\''.$name.'\')\"> <i class=\"fa fa-trash\"></i> "+lang["del_file"]+"</button> ");
		  } else {
		  	dr_tips(data.msg,5);
            $("#dr_my_'.$name.'_list").html($("#dr_hide_'.$name.'_list").html());
		  }

        },
        onUploadError: function(id, message){
          alert("Failed to Upload file #" + id + ": " + message);
          $("#dr_my_'.$name.'_list").html("");
        },
        onFileTypeError: function(file){
          alert("File \"" + file.name + "\" cannot be added: must be an image");
          $("#dr_my_'.$name.'_list").html("");
        },
        onFileSizeError: function(file){
          alert( "File \"" + file.name + "\" cannot be added: size excess limit");
          $("#dr_my_'.$name.'_list").html("");
        },
        onFallbackMode: function(message){
          alert( "Browser not supported(do something else here!): " + message);
          $("#dr_my_'.$name.'_list").html("");
        }
      });
    </script>
          	';

        return $this->input_format($name, $text, $str.$tips);
    }
}