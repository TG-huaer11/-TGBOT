<?php

namespace app\pay;

use think\facade\Db;

class XdPay
{
    // 代收
    public function recharge($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥

        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址

        $mch_order_no = $order_sn; //订单号
        $pay_type = $pay["domain"]; //通道ID
        $trade_amount = $amount; //交易金额

        $postdata = array(
            "merchant" => $mch_id,
            "payCode" => $pay_type,
            "orderId" => $mch_order_no,
            "amount" => number_format($trade_amount, 2, '.', ''),
            "notifyUrl" => $notify_url,
        );

        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $postdata['sign'] = md5($md5str . "key=" . $merchant_key);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://apis.xdpay168.com/client/collect/create");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (isset($data['code']) && $data['code'] == '200') {
            return ['code' => 1, 'msg' => '', 'url' => $data['data']['url']];
        } else {
            return ['code' => 0, 'msg' => $data['msg'], 'url' => ''];
        }
    }

    // 代收回调
    public function notify_recharge($post)
    {
        if (isset($post['status']) && $post['status'] == '1') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['orderId'];
            $tradeNo = $post['platOrderId']; //平台订单号
            $amount = $post['amount'];
            $orderInfo = Db::connect($connection)
                ->name('recharge')
                ->where('id', $orderNo)
                ->find();
            if ($orderInfo) {
                if ($orderInfo['status'] == 1) {
                    $message = 'Success. ' . $tradeNo;
                    $db = Db::connect($connection)
                        ->name('recharge')
                        ->where('id', $orderNo)
                        ->update([
                            'real_num' => $amount,
                            'status' => 2,
                            'end_at' => time(),
                            'remark' => $message,
                        ]);
                    if ($db) {
                        (new \app\common\service\CnmService($connection))->recharge($orderNo, $orderInfo['uid'], $amount, $message, json_encode($post));
                        (new \app\common\service\WalletService($connection))->balanceRecharge($orderInfo['uid'], $amount);
                        return ('success');
                    } else {
                        return ('fail4');
                    }
                } else if ($orderInfo['status'] == 2) {
                    return ('success');
                } else {
                    return ('fail3');
                }
            } else {
                return ('fail2');
            }
        } else {
            return ('fail1');
        }
    }

    // 代付
    public function withdraw($params, $pay)
    {

        $skey = $pay['sk']; //代付秘钥

        $orderId = $params ['order_sn'];                          //订单号
        $beneficiaryName = $params ['name'];                       //用户姓名
        $beneficiaryAccount = $params ['account'];                 //银行卡号
        $merchId = $pay['sn'];                       //商户号
        $notifyUrl = $pay['notify_url'];               //异步通知


        $request_data = [
            'merchant' => $merchId,
            'payCode' => $pay['domain'],
            'amount' => number_format($params ['amount'], 2, '.', ''),
            'orderId' => $orderId,
            'notifyUrl' => $notifyUrl,
            'remark' => $params ['ifsc'],
            'bankAccount' => $beneficiaryAccount,
            'customName' => $beneficiaryName,
        ];
        ksort($request_data);
        $md5str = "";
        foreach ($request_data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $request_data['sign'] = md5($md5str . "key=" . $skey);

        $postdata = $request_data;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://apis.xdpay168.com/client/pay/create");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        // var_dump($res);

        $data = json_decode($res, true);
        if (isset($data['code']) && $data['code'] == '200') {
            return ([
                'res' => true,
                'data' => $data
            ]);
        } else {
            return ([
                'res' => false,
                'data' => $data
            ]);
        }
    }

    // 代付回调
    public function notify_withdraw()
    {
        if (isset($post['status']) && $post['status'] == '1') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['orderId']; //订单号
            // $amount = $post['amount']; //实际支付金额
            $tradeNo = $post['platOrderId']; //平台订单号
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 4)
                ->find();
            if ($orderInfo) {
                $message = "Success." . $tradeNo;
                $db = Db::connect($connection)
                    ->name('deposit')
                    ->where('id', $orderNo)
                    ->update([
                        'status' => 2, //提款成功
                        'remark' => $message,
                    ]);
                if ($db) {
                    (new \app\common\service\UserService($connection))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                    (new \app\common\service\CnmService($connection))->despoit($orderNo, $orderInfo['uid'], 2, $orderInfo['num'], $message, json_encode($post));
                    return ('success');
                }
            }
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 2)
                ->find();
            if ($orderInfo) {
                return ('success');
            }
        }
        if (isset($post['status']) && $post['status'] == '2') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['orderId']; //订单号
            // $amount = $post['amount']; //实际支付金额
            $tradeNo = $post['platOrderId']; //平台订单号
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 4)
                ->find();
            if ($orderInfo) {
                $message = "Payment Fail." . $tradeNo;
                $db = Db::connect($connection)
                    ->name('deposit')
                    ->where('id', $orderNo)
                    ->update([
                        'status' => 3, //提款失败
                        'remark' => $message,
                    ]);
                if ($db) {
                    (new \app\common\service\WalletService($connection))->balanceRecharge($orderInfo['uid'], $orderInfo['num']); // RNM,退钱
                    (new \app\common\service\CnmService($connection))->despoit($orderNo, $orderInfo['uid'], 1, $orderInfo['num'], $message, json_encode($post));
                    (new \app\common\service\UserService($connection))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                    return ('success');
                }
            }
        }
        return ('nononono');
    }
}
