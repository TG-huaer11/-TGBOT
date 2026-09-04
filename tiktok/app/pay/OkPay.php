<?php

namespace app\pay;

use think\facade\Db;

class OkPay
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
            "mchId"             => $mch_id,
            "currency"          => 'INR',
            "out_trade_no"      => $mch_order_no,
            "pay_type"          => $pay_type,
            "money"             => number_format($trade_amount, 2, '.', ''),
            "attach"            => 'INR',
            "notify_url"        => $notify_url,
            "returnUrl"         => 'https://www.snapdealhappy.com',
            "reserve1"          => 'reserve1',
            "reserve2"          => 'reserve2',
            "reserve3"          => 'reserve3',
        );

        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = md5($md5str . "key=" . $merchant_key);
        $postdata['sign'] = $sign;

        $headers = array(
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_URL, "https://api.wpay.one/v1/daishou"); //支付请求地址
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);

        // var_dump($md5str . "key=" . $merchant_key);
        // // var_dump($postdata);
        // var_dump($data);

        if (isset($data['code']) && $data['code'] == '0') {
            return $data['data']['url'];
        } else {
            return ('');
        }
    }

    // 代收回调
    public function notify_recharge($post)
    {
        if (isset($post['status']) && $post['status'] == '1') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['out_trade_no'];
            $amount = $post['pay_money'];
            $orderInfo = Db::connect($connection)
                ->name('recharge')
                ->where('id', $orderNo)
                ->find();
            if ($orderInfo) {
                if ($orderInfo['status'] == 1) {
                    $message = 'Success. ' . $orderNo;
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
                        return ('fail');
                    }
                } else {
                    return ('fail');
                }
            } else {
                return ('fail');
            }
        }
        return ('fail');
    }

    // 代付
    public function withdraw($params, $pay)
    {
        $mbkey = $pay['sk']; //代付秘钥
        $merchant_id        = $pay['sn'];                   //商户号
        $order_id           = $params ['order_sn'];                    //订单号
        $amount             = $params ['amount'];                     //金额,单位: 分
        $receive_name       = $params ['name'];                        //姓名
        $receive_account    = $params ['account'];                     //卡号
        $transfer_type      = $pay['domain'];               //转账类型
        $notifyUrl          = $pay['notify_url'];           //异步通知

        $postdata = array(
            'mchId'             => $merchant_id,
            "currency"          => 'INR',
            'out_trade_no'      => $order_id,
            'pay_type'          => $transfer_type,
            'account'           => $receive_account,
            'userName'          => $receive_name,
            'money'             => $amount,
            'notify_url'        => $notifyUrl,
            "attach"            => 'INR',
            "reserve1"          => $params ['ifsc'],
            "reserve2"          => 'reserve2',
            "reserve3"          => 'reserve3',
        );

        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $postdata['sign'] = md5($md5str . "key=" . $mbkey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.wpay.one/v1/daifu");
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
        if (isset($data['code']) && $data['code'] == '0') {
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
    public function notify_withdraw($post)
    {
        if (isset($post['status']) && $post['status'] == '1') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['out_trade_no']; //订单号
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 4)
                ->find();
            if ($orderInfo) {
                $message = "Success.";
                $db = Db::connect($connection)
                    ->name('deposit')
                    ->where('id', $orderNo)
                    ->update([
                        'status' => 2, //提款成功
                        'remark' => "Success.",
                    ]);
                if ($db) {
                    (new \app\common\service\UserService($connection))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                    (new \app\common\service\CnmService($connection))->despoit($orderNo, $orderInfo['uid'], 2, $orderInfo['num'], $message, json_encode($post));
                    return ('success');
                }
            }
        }
        if (isset($post['status']) && $post['status'] == '2') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['out_trade_no']; //订单号
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 4)
                ->find();
            if ($orderInfo) {
                $message = "Payment Fail.";
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
        return ('FAILED');
    }
}
