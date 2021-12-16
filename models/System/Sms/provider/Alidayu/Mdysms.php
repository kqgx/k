<?php
/**

 +------------------------------------------------------------------------------

 * 文件名/文件说明
 *
 * Nanjing Mu Tao Network Technology Co., Ltd.

 +------------------------------------------------------------------------------

 * $website: www.mutaoinc.com
 *
 * $mailto: info@mutaoinc.com

 +------------------------------------------------------------------------------

 */

require_once dirname(__FILE__).'/dysms/SignatureHelper.php';
require_once dirname(__FILE__).'/dysms/conf.inc.php';
use Aliyun\DySDKLite\SignatureHelper;
class Mdysms {
     public $conf;

     public function __construct()
     {
         $this->conf['accessKeyId'] = ACCESSKEYID;
         $this->conf['accessKeySecret'] = ACCESSKEYSECRET;
         $this->conf['signName'] = SignName;
         $this->conf['templateCode'] = TemplateCode;
     }

     public function sendSms($phoneNumbers, $param, $templateCode = FALSE, $signName = FALSE)
     {
        $params['TemplateParam'] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $params['TemplateCode'] = $templateCode?$templateCode:$this->conf['templateCode'];
        $params['SignName'] = $signName?$signName:$this->conf['signName'];
        $params['PhoneNumbers'] = $phoneNumbers;
        // exit(json_encode($params));
        $helper = new SignatureHelper();
        $resp = $helper->request(
            $this->conf['accessKeyId'],
            $this->conf['accessKeySecret'],
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25"
            ))
        );
        return $resp;
     }
}
