<?php

namespace app\pay;

class Pay51
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥

        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址

        $mch_order_no = $order_sn; //订单号
        $pay_type = $pay["domain"]; //通道ID
        $trade_amount = $amount; //交易金额

        $postdata = array(
            "merchantNo"        => $mch_id,
            "merchantOrderId"   => $mch_order_no,
            "amount"            => bcmul($trade_amount, 100, 0),
            "currency"          => 'INR',
            "email"             => "recharge@pay.com",
            "userName"          => 'rechargeUser',
            "mobileNo"          => '15982360145',
            "channelCode"       => $pay_type,
            "notifyUrl"         => $notify_url,
            "expireTime"        => 60,
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtolower(hash_hmac('sha256', $md5str . "key=" . $merchant_key, $merchant_key));
        $postdata['version'] = '1.0';
        $postdata['subject'] = 'Recharge';
        $postdata['sign'] = $sign;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.eggoout.com/payin/unifiedorder.do"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        ));
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        // var_dump($data);
        if (isset($data['code']) && $data['code'] == '000') {
            return $data['data']['checkStand'];
        } else {
            return ('');
        }
    }
}
