<?php
include "Uploader.class.php";
$CONFIG = json_decode(preg_replace("/\\/\\*[\\s\\S]+?\\*\\//", "", file_get_contents(LIBRARIES . "Ueditor/config.json")), true);
switch ($_GET['action']) {
    case 'uploadimage':
        $config = array("pathFormat" => $CONFIG['imagePathFormat'], "maxSize" => $CONFIG['imageMaxSize'], "allowFiles" => $CONFIG['imageAllowFiles']);
        $fieldName = $CONFIG['imageFieldName'];
        break;
    case 'uploadscrawl':
        $config = array("pathFormat" => $CONFIG['scrawlPathFormat'], "maxSize" => $CONFIG['scrawlMaxSize'], "allowFiles" => $CONFIG['scrawlAllowFiles'], "oriName" => "scrawl.png");
        $fieldName = $CONFIG['scrawlFieldName'];
        break;
    case 'uploadvideo':
        $config = array("pathFormat" => $CONFIG['videoPathFormat'], "maxSize" => $CONFIG['videoMaxSize'], "allowFiles" => $CONFIG['videoAllowFiles']);
        $fieldName = $CONFIG['videoFieldName'];
        break;
    case 'uploadfile':
    default:
        $config = array("pathFormat" => $CONFIG['filePathFormat'], "maxSize" => $CONFIG['fileMaxSize'], "allowFiles" => $CONFIG['fileAllowFiles']);
        $fieldName = $CONFIG['fileFieldName'];
        break;
}

$uploader = new Uploader($fieldName, $config, $base64);

$result = $uploader->getFileInfo();

if (isset($result['state']) && $result['state'] == 'SUCCESS' && $result['size']) {
    $this->models('system/attachment')->siteid = max(1, (int) $this->input->get('siteid'));
    $filename = DR_UE_PATH . $result['url'];
    list($id, $url, $b) = $this->models('system/attachment')->upload($this->uid, array('file_ext' => $result['type'], 'full_path' => $filename, 'file_size' => $result['size'] / 1024, 'client_name' => str_replace($result['type'], '', $result['original'])));
    $result['id'] = UEDITOR_IMG_ID . '_img_' . $id;
    $result['url'] = $url;
    if (SITE_IMAGE_WATERMARK && SITE_IMAGE_CONTENT && in_array(trim($result['type'], '.'), array('jpg', 'png', 'jpeg')) && ($imageinfo = getimagesize($filename))) {
        $result['url'] = thumb_get($id, $imageinfo[0], $imageinfo[1], 1);
    }
}

return $result;