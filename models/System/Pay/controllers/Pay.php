<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Pay extends M_Controller
{

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->config->load('pay', true);
        $this->load->library('easypay', $this->config->item('pay'));
    }

    public function index()
    {
        $id = (int)$this->input->get('id');
        $pay = $this->models('system/pay')->get($id);
        if ($pay) {

        } else {

        }
        $this->render(array());
    }

    /**
     * 支付统一下单
     */
    public function payment()
    {
        $header = $this->input->request_headers();
        $uid = $this->models('index')->online_model($header['X-Token'], $header['X-Sign']);
        $id = (int)$_GET['id'];
        $type = $_GET['type'];
        $gateway = $_GET['gateway'];
        if ($uid < 1) {
            apiSuccess(-4001, L('请登录'));
        } elseif (!$id || !$type) {
            $this->json(0, '参数错误');
        } elseif (!$data = $this->models('system/pay')->getItem($id)) {
            $this->json(0, '记录不存在');
        } elseif ($data['uid'] && $data['uid'] != $uid) {
            $this->json(0, '没有权限');
        } elseif ($data['status'] != 0) {
            $this->json(0, '订单已结束');
        } elseif (in_array($type, $data['disabled_pays'])) {
            $this->json(0, '不支持此支付方式');
        } else {
            $order_price = abs($data['value']);
            try {
                $gatewayInstance = $this->easypay->driver($type)->gateway($gateway);
                $out_trade_no = 'P-' . date('ymdHis', $data['inputtime'])
                    . '-' . str_pad($data['id'], '8', 0, STR_PAD_LEFT);
                $title = SITE_NAME . '-' . ($data['note'] ?: '消费');
                $config_biz = ['out_trade_no' => $out_trade_no];
                switch ($type) {
                    case 'wechat':
                        $config_biz = [
                                'total_fee' => $order_price * 100,
                                'body' => $title,
                                'spbill_create_ip' => $_SERVER['REMOTE_ADDR']
                            ] + $config_biz;
                        if (in_array($gateway, ['mp', 'miniapp'])) {
                            $openid = dr_member_info($uid)['openid'];
                            if ($openid) {
                                $config_biz['openid'] = $openid;
                            } else {
                                $this->json(-4001, '无法获取openid');
                            }
                        }
                        break;
                    case 'alipay':
                        $config_biz = [
                                'total_amount' => $order_price,
                                'subject' => $title
                            ] + $config_biz;
                        if ($gateway == 'web') {
                            if (isset($_GET['qr_mode']) && is_numeric($_GET['qr_mode'])) {
                                $config_biz['qr_pay_mode'] = $_GET['qr_mode'];
                                if ($_GET['qr_mode'] == 4 && isset($_GET['qr_size']) && is_numeric($_GET['qr_size'])) {
                                    $config_biz['qrcode_width'] = $_GET['qr_size'];
                                }
                            }
                        }
                        break;
                    default:
                        throw new Yansongda\Pay\Exceptions\InvalidArgumentException("Driver [$type]'s is unsupported.");
                }
                $result = $gatewayInstance->pay($config_biz);
                $this->json(1, ['payed' => 0, 'payment' => $result], 'success');
            } catch (Yansongda\Pay\Exceptions\InvalidArgumentException $e) {
                $this->json(0, '支付方式不支持:' . $e->getMessage());
            } catch (Exception $e) {
                $this->json(0, '支付出错:' . $e->getMessage());
            }
        }
    }

    public function pay_status()
    {
        $id = (int)$_GET['id'];
        if (!$id) {
            $this->json(0, '参数错误');
        } elseif (!$data = $this->models('system/pay')->getItem($id)) {
            $this->json(0, '记录不存在');
        } else {
            $this->json(1, ['status' => (int)$data['status']]);
        }
    }

    public function pay_return()
    {
        echo $this->easypay->driver($_GET['type'])->gateway()->verify($_SERVER['QUERY_STRING']);
    }

    public function notify()
    {
        $gateway_instance = $this->easypay->driver($_GET['type'])->gateway('app');
        switch ($_GET['type']) {
            case 'alipay':
        file_put_contents(__DIR__ . '/test.txt', http_build_query($_REQUEST));
                $request = $_REQUEST;
                if (
                    $gateway_instance->verify($request)
                    && in_array($request['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])
                ) {
                    $out_trade_no = $request['out_trade_no'];
                    $money = $request['total_amount'];
                }
                break;
            case 'wechat':
                if ($request = $gateway_instance->verify(file_get_contents('php://input') ?: '<xml></xml>')) {
                    $out_trade_no = $request['out_trade_no'];
                    $money = $request['total_fee'] / 100;
                }
        }
        if (isset($out_trade_no)) {
            $payid = (int)substr(strrchr($out_trade_no, '-'), 1);
            $paylog = $this->models('system/pay')->getItem($payid);
            if ($paylog && $paylog['status'] == 0 && abs($paylog['value']) == $money) {
                $this->models('index')->payment_success($_GET['type'], $paylog);
                $this->models('system/pay')->paySuccess($_GET['type'], $paylog, $request);
            }
        }
        echo 'success';
    }

    public function test()
    {
        var_export($this->get_cache('member', 'group', '4'));
    }
}
