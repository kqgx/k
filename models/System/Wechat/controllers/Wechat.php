<?php

use Yuanshe\WeChatSDK\Exception\ModelException;
use Yuanshe\WeChatSDK\Exception\NotifyException;
use Yuanshe\WeChatSDK\Model\OAuth;
use Yuanshe\WeChatSDK\Notify;

require_once __DIR__ . '/../WeChatInstance.php';

class Wechat extends M_Controller
{
    use WeChatInstance;
    private $oauthState = 'imtcms';

    public function test()
    {
        setcookie('XDEBUG_SESSION', 'XDEBUG_ECLIPSE', time() + 864000);
    }

    /**
     * OAuth授权
     * 首次授权会弹出授权窗口，用户同意后创建新用户
     */
    public function oAuth()
    {
        $this->authorize(OAuth::SCOPE_USER_INFO);
    }

    /**
     * OAuth静默授权
     * 授权无需弹出授权窗口，但需用户订阅公众号，如未关注则提示用户订阅
     */
    public function subscribeAuthorize()
    {
        $this->authorize(OAuth::SCOPE_BASE);
    }

    public function authorizeCallback()
    {
        if (empty($_GET['code'])) {
            $this->msg(0, '参数错误');
        } elseif (empty($_GET['state']) || $_GET['state'] != $this->oauthState) {
            $this->msg(0, 'state不匹配');
        } else {
            try {
                $tokenResponse = $this->wechat->oAuth->getAccessToken($_GET['code']);
                $wechatUser = $this->models('System/Wechat/User')->getByOpenid($tokenResponse['openid']);
                if (empty($wechatUser)) {
                    if ($tokenResponse['scope'] == OAuth::SCOPE_USER_INFO) {
                        $wechatUser = $this->wechat->oAuth->getUserInfo(
                            $tokenResponse['openid'],
                            $tokenResponse['access_token']
                        );
                    } else {
                        $wechatUser = $this->wechat->user->getInfo($tokenResponse['openid']);
                        if ($wechatUser['subscribe'] == 0) {
                            header('Location: /h5/wechat/subscribe.html');
                            return;
                        }
                    }
                    $wechatUserID = $this->models('system/wechat/user')->create($wechatUser);
                    if (empty($wechatUserID)) {
                        $this->msg(0, '创建微信用户失败');
                        return;
                    }
                    $wechatUser = $this->models('system/wechat/user')->getItem($wechatUserID);
                }
                if (empty($wechatUser['uid'])) {
                    $uid = $this->uid ?: $this->models('member')->add([
                        'nickname' => $wechatUser['nickname'],
                        'username' => $wechatUser['openid'],
                        'password' => md5($wechatUser['openid'] . microtime() . mt_rand())
                    ], null, 2);
                    if ($uid > 1) {
                        $this->models('system/wechat/user')->bindUID($wechatUser['id'], $uid);
                        $wechatUser['uid'] = $uid;
                    } else {
                        $this->msg(0, '创建用户数据失败');
                    }
                }
                $user = $this->models('member')->get_member($wechatUser['uid']);
                $this->input->set_cookie('member_uid', $user['uid'], 86400);
                $this->input->set_cookie('member_cookie', substr(md5(SYS_KEY . $user['password']), 5, 20), 86400);
                header('Location: ' . (@$_GET['redirect'] ?: SITE_URL));
            } catch (ModelException $e) {
                $this->msg(0, $e->getCode() . ' - ' . $e->getMessage());
            }
        }
    }

    public function notify()
    {
        try {
            if ($this->wechat->checkNotifyIP($_SERVER['REMOTE_ADDR'])) {
                $notify = $this->wechat->notify($_GET, file_get_contents('php://input'));
                if ($notify instanceof Notify) {
                    $this->hooks->call_hook('wechat_notify', $notify);
                    if ($notify->getType() == Notify::TYPE_MESSAGE) {
                        $this->hooks->call_hook('wechat_message', $notify);
                    } elseif ($notify->getType() == Notify::TYPE_EVENT) {
                        $this->hooks->call_hook('wechat_event', $notify);
                    }
                } elseif (is_string($notify)) {
                    echo $notify;
                }
            } else {
                http_response_code(403);
                echo 'Unknown request origin';
            }
        } catch (NotifyException $e) {
            http_response_code(401);
            echo SYS_DEBUG ? $e->getMessage() : 'Verification failed';
        } catch (Exception $e) {
            http_response_code(500);
            echo SYS_DEBUG ? get_class($e) . '(' . $e->getCode() . '): ' . $e->getMessage() : 'Server exception';
        }
    }

    protected function authorize(string $scope)
    {
        $redirect = SITE_URL . '?c=wechat&m=authorizeCallback';
        empty($_GET['redirect']) or $redirect .= '&redirect=' . urlencode($_GET['redirect']);
        header('Location: ' . $this->wechat->oAuth->codeURL($scope, $redirect, $this->oauthState));
    }
}
