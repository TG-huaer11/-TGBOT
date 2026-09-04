<?php

namespace app\pay;

use think\facade\Db;

class TarsPay
{
    // 代收
    public function recharge($pay, $amount, $order_sn)
    {
        $merchant_ak = $pay['ak']; //支付秘钥
        $merchant_sk = $pay['sk']; //支付秘钥
        $mch_id = $pay['sn']; //商户号

        $notify_url = $pay["notify_url"]; //异步通知地址
        $redirect_url = $pay["redirect_url"]; //异步通知地址
        $mch_order_no = $order_sn; //订单号
        $trade_amount = $amount; //交易金额

        $postdata = array(
            "mchNo"            => $mch_id,
            "mchOrderNo"       => $mch_order_no,
            "amount"           => number_format($trade_amount, 2, '.', ''),
            "wayCode"          => "CASHIER",
            "currency"         => "INR",
            "notifyUrl"        => $notify_url,
            "returnUrl"        => $redirect_url,
        );
        // var_dump($postdata);
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = substr($md5str, 0, strlen($md5str) - 1);
        $times = time() . mt_rand(100, 999);
        // -------
        $ccc = [
            "content" => "POST|/api/pay/unifiedOrder|$times|$md5str",
            "privateKey" => $merchant_sk,
        ];
  

        // $ccc = [
        //     "content" => "POST|/api/pay/unifiedOrder|1666008270904|amount=100.00&currency=INR&mchNo=M1665822956&mchOrderNo=TARS221017173430889000&notifyUrl=http://149.129.175.224:8011/game-center/paynotify/pay/tars/rechargeNotify&returnUrl=http://149.129.175.224&wayCode=CASHIER",
        //     "privateKey" => $merchant_sk,
        // ];

        
        $ch = curl_init("http://149.129.175.224:8011/game-center/pay/tars/sign");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ccc));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $md5res = curl_exec($ch);
        curl_close($ch);

        
        $md5json = json_decode($md5res, true);
        $md5 = $md5json['data'];

        // var_dump($md5);

        $header = [
            "Content-Type:application/json",
            "X-API-KEY:$merchant_ak",
            "X-API-NONCE:$times",
            "X-API-SIGNATURE:$md5",
        ];
        // var_dump($header);
        //-------
        $ch1 = curl_init("https://payment.tarspay.com/api/pay/unifiedOrder"); //
        curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_HEADER, false);
        curl_setopt(
            $ch1,
            CURLOPT_HTTPHEADER,
            $header
        );
        $res = curl_exec($ch1);
        curl_close($ch1);
        $data = json_decode($res, true);


        // var_dump($data);


        $code = $data['code'];
        if (isset($code) && $code == 0) {
            return $data['data']['payUrl'];
        } else {
            return ('');
        }
    }

    // 代收回调
    public function notify_recharge($post)
    {
        if (isset($post['state']) && $post['state'] == '2') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['mchOrderNo'];
            $amount = $post['payAmount'];
            $tradeNo = $post['payOrderId']; //平台订单号

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
                        return ('OK');
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
    }

    // 代付 @finish
    public function withdraw($params, $pay)
    {
        $merchant_ak = $pay['ak']; //支付秘钥
        $merchant_sk = $pay['sk']; //支付秘钥
        $mch_id = $pay['sn']; //商户号
        $notifyUrl  = $pay['notify_url'];               //异步通知


        $postdata = array(
            "mchNo"                         => $mch_id,
            "mchOrderNo"                    => $params['order_sn'],
            "amount"                        => number_format($params['amount'], 2, '.', ''),
            "wayCode"                       => "UPI",
            "currency"                      => "INR",
            "notifyUrl"                     => $notifyUrl,
            "customerName"                  => $params['name'],
            "customerEmail"                 => "name@gmail.in",
            "customerContact"               => "15221113288",
            "customerAccountNumber"         => $params['account'],
            "address"                       => "Mumbai",
            "ifsc"                          => $params['ifsc'],
            "bankName"                      => "bank",
        );
        ksort($postdata);
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = substr($md5str, 0, strlen($md5str) - 1);
        $times = time() . mt_rand(100, 999);
        // -------
        $ccc = [
            "content" => "POST|/api/payOut/unifiedOrder|$times|$md5str",
            "privateKey" => $merchant_sk,
        ];


        // $ccc = [
        //     "content" => "POST|/api/pay/unifiedOrder|1666008270904|amount=100.00&currency=INR&mchNo=M1665822956&mchOrderNo=TARS221017173430889000&notifyUrl=http://149.129.175.224:8011/game-center/paynotify/pay/tars/rechargeNotify&returnUrl=http://149.129.175.224&wayCode=CASHIER",
        //     "privateKey" => $merchant_sk,
        // ];


        $ch = curl_init("http://149.129.175.224:8011/game-center/pay/tars/sign");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ccc));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $md5res = curl_exec($ch);
        curl_close($ch);


        $md5json = json_decode($md5res, true);
        $md5 = $md5json['data'];


        $header = [
            "Content-Type:application/json",
            "X-API-KEY:$merchant_ak",
            "X-API-NONCE:$times",
            "X-API-SIGNATURE:$md5",
        ];
        // var_dump($header);
        //-------
        // $ch1 = curl_init("https://payment.tarspay.com/api/pay/unifiedOrder"); //
        $ch1 = curl_init("https://payment.tarspay.com/api/payOut/unifiedOrder"); //
        curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_HEADER, false);
        curl_setopt(
            $ch1,
            CURLOPT_HTTPHEADER,
            $header
        );
        $res = curl_exec($ch1);
        curl_close($ch1);
        $data = json_decode($res, true);

        // var_dump($data);

        $code = $data['code'];
        if (isset($code) && $code == 0) {
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

    // 代付回调 @finish
    public function notify_withdraw($post)
    {
        if (isset($post['state']) && $post['state'] == '2') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['mchOrderNo']; //订单号
            // $amount = $post['amount']; //实际支付金额
            $tradeNo = $post['payOrderId']; //平台订单号
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
                    return ('OK');
                }
            }
        }
        if (isset($post['state']) && $post['state'] == '3') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['mchOrderNo']; //订单号
            // $amount = $post['amount']; //实际支付金额
            $tradeNo = $post['payOrderId']; //平台订单号
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
                    return ('OK');
                }
            }
        }
    }


    function request(string $method, string $path, $data, $md5, $merchant_ak)
    {
        $ch = curl_init();
        $nonce = time() * 1000;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $replace_path = substr_replace($path, "", 0, 12);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Biz-Api-Key:" . $merchant_ak,
            "Biz-Api-Nonce:" . $nonce,
            "Biz-Api-Signature:" . $md5
        ]);


        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
            curl_setopt($ch, CURLOPT_URL, $path . "?" . $data);
        }


        // if ($this->debug) {
        //     echo "request >>>>>>>>\n";
        //     echo join("|", [$method, $path, $nonce, $sorted_data]), "\n";
        // }
        list($header, $body) = explode("\r\n\r\n", curl_exec($ch), 2);
        curl_close($ch);
        return json_decode($body);
    }
}
