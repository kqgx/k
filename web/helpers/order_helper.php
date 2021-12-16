<?php

/* v3.1.0  */


/**
 * 订单状态
 *
 * 支付状态
 *
 * 物流状态
 * */
function dr_order_status($order) {
	
	$status = array();

	// 订单状态
	switch($order['order_status']) {
		case 0:
			$status[0] = L('已失效');
			break;
		case 1:
			$status[0] = L('已下单');
			break;
		case 2:
			$status[0] = L('配货中');
			break;
		case 3:
			$status[0] = L('交易完成');
			break;
		case 4:
			$status[0] = L('待收货');
			break;
		case 5:
			$status[0] = L('卖家缺货');
            break;
		case 6:
			$status[0] = L('转账中');
			break;
		case 7:
			$status[0] = L('退货');
			break;
		case 8:
			$status[0] = L('退款');
            break;
		case 9:
			$status[0] = L('交易关闭');
			break;
	}

    // 支付状态
	switch($order['pay_status']) {
		case 0:
			$status[1] = L('未付款');
			break;
		case 1:
			$status[1] = L('退款中');
			break;
		case 2:
			$status[1] = L('已退款');
			break;
		case 3:
			$status[1] = L('已付款');
            break;
		case 4:
			$status[1] = L('付款中');
			break;
	}

    // 物流状态
	// switch($order['shipping_status']) {
	// 	case 0:
	// 		$status[2] = L('未发货');
	// 		break;
	// 	case 1:
	// 		$status[2] = L('已退货');
	// 		break;
	// 	case 2:
	// 		$status[2] = L('退货中');
	// 		break;
	// 	case 3:
	// 		$status[2] = L('已发货');
	// 		break;
	// 	case 4:
	// 		$status[2] = L('退货失败');
	// 		break;
	// 	case 5:
	// 		$status[2] = L('已收货');
	// 		break;
	// }

    // 根据订单购买流程
    if (intval($order['buy_step']) == 2) {
        unset($status[2]);
    }
	
	return implode(' , ', $status);
}

// 订单操作状态
function dr_order_member_status($i) {

    // 订单状态
    switch($i) {
        case 0:
            $code = '<font color="red">'.L('已失效').'</font>';
            break;
        case 1:
            $code = '<font color="blue">'.L('已下单').'</font>';
            break;
        case 2:
            $code = '<font color="blue">'.L('配货中').'</font>';
            break;
        case 3:
            $code = '<font color="green">'.L('交易完成').'</font>';
            break;
        case 4:
            $code = '<font color="blue">'.L('待收货').'</font>';
            break;
        case 5:
            $code = '<font color="red">'.L('卖家缺货').'</font>';
            break;
        case 7:
            $code = '<font color="red">'.L('退货').'</font>';
            break;
        case 8:
            $code = '<font color="red">'.L('退款').'</font>';
            break;
        case 9:
            $code = '<font color="blue">'.L('交易关闭').'</font>';
            break;
        default:
            $code = $i;
            break;
    }

    return $code;
}

// 订单操作
function dr_order_member_option($uid, $order) {

    $code = '';
    // 订单状态
    switch($order['order_status']) {
        case 0:
            # 失效订单
            $code.= '<p><a href="javascript:;" class="btn red-mint btn-xs"> <i class="fa fa-frown-o"></i> '.L('已失效').'</a></p>';
            break;
        case 1:
            # 已下单
            if ($uid == $order['buy_uid']) {
                # 买家
                if ($order['pay_status'] == 0) {
                    # 未付款
                    $code.= '<p><a href="'.SITE_URL.'index.php?s='.APP_DIR.'&c=buy&id='.$order['id'].'" class="btn green btn-xs" target="_blank"> <i class="fa fa-paypal"></i> '.L('立即付款').'</a></p>';
                }
                $code.= '<a href="javascript:;" onclick="dr_order_close('.$order['id'].')" class="btn red-mint btn-xs"> <i class="fa fa-trash-o"></i> '.L('关闭订单').'</a>';
            } else {
                # 商家
                if ($order['pay_status'] == 0) {
                    # 未付款
                    // $code.= '<p><a href="javascript:;" class="btn red-mint btn-xs"> <i class="fa fa-frown-o"></i> '.L('等待付款').'</a></p>';
                    // $code.= '<a href="javascript:;" class="btn green btn-xs" onclick="dr_order_price('.$order['id'].')"> <i class="fa fa-edit"></i> '.L('修改价格').'</a> ';
                }
                if (IS_ADMIN) {
                    $code.= '<a href="javascript:;" class="btn red-mint btn-xs" onclick="dr_order_close('.$order['id'].')"> <i class="fa fa-frown-o"></i> '.L('关闭订单').'</a>';
                }
            }
            break;
        case 2:
            # 配货中
            if ($uid == $order['buy_uid']) {
                # 买家
                $code.= '<p><a href="javascript:;" class="btn green btn-xs"> <i class="fa fa-truck"></i> '.L('等待发货').'</a></p>';
            } else {
                # 商家
                $code.= '<p><a href="javascript:;" onclick="dr_order_shipping('.$order['id'].')" class="btn green btn-xs"> <i class="fa fa-truck"></i> '.L('立即发货').'</a></p>';

            }
           // $code.= '<p><a href="javascript:;" onclick="dr_order_tuikuan('.$order['id'].')">退款</a></p>';
            break;
        case 3:
            # 交易完成
            if ($uid == $order['buy_uid']) {
                # 买家
                // $code.= '<p><a href="javascript:;" class="btn green btn-xs"> <i class="fa fa-check-circle"></i> '.L('交易完成').'</a></p>';
            } else {
                # 商家
                // $code.= '<p><a href="javascript:;" class="btn green btn-xs"> <i class="fa fa-check-circle"></i> '.L('交易完成').'</a></p>';
            }
            break;
        case 4:
            # 待收货
            if (IS_ADMIN || $uid == $order['buy_uid']) {
                # 买家
                $code.= '<p><a href="javascript:;" onclick="dr_order_shouhuo('.$order['id'].')" class="btn green btn-xs"> <i class="fa fa-paper-plane"></i> '.L('确认收货').'</a></p>';
            } else {
                # 商家
                $code.= '<a href="javascript:;" class="btn green btn-xs"> <i class="fa fa-paper-plane"></i> '.L('等待收货').'</a>';
            }
            break;
        case 5:
            # 卖家缺货
            if ($uid == $order['buy_uid']) {
                # 买家
            } else {
                # 商家
            }
            break;
        case 6:
            # 银行转账中
            if ($uid == $order['buy_uid']) {
                # 买家
                $code.= '<p><a href="javascript:;" class="btn blue btn-xs"> <i class="fa fa-rmb"></i> '.L('已转账').'</a></p>';
            } else {
                # 商家
                $code.= '<p><a href="javascript:;" class="btn blue btn-xs"> <i class="fa fa-rmb"></i> '.L('等待系统确认').'</a></p>';
            }
            break;
        case 7:
            # 退货
            if ($uid == $order['buy_uid']) {
                # 买家
            } else {
                # 商家
            }
            break;
        case 8:
            # 退款
            if ($uid == $order['buy_uid']) {
                # 买家
            } else {
                # 商家
            }
            break;
        case 9:
            # 交易关闭
            // $code.= '<p><a href="javascript:;" class="btn red-mint btn-xs"> <i class="fa fa-frown-o"></i> '.L('交易关闭').'</a></p>';
            break;

    }

    return $code;
}