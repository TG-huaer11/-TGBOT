<?php

namespace app\pay;

class VipPay
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
            "merNo"             => $mch_id,
            "merchantOrderNo"   => $mch_order_no,
            "amount"            => number_format($trade_amount, 2, '.', ''),
            "goodsName"         => "shop",
            "currency"          => "INR",
            "payCode"           => $pay_type,
            "notifyUrl"         => $notify_url,
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtolower(md5($md5str . "key=" . $merchant_key));
        $postdata['sign'] = $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ord.payvip.net/pay/order"); //支付请求地址
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
        if (isset($data['code']) && $data['code'] == 'SUCCESS') {
            return $data['payLink'];
        } else {
            return ('');
        }
    }
}
