<?php

namespace app\pay;

class GrPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $appId = $pay['ak']; //支付秘钥
        $merchant_key = $pay['sk']; //支付秘钥
        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址

        $mch_order_no = $order_sn; //订单号
        $pay_type = $pay["domain"]; //通道ID
        $trade_amount = $amount; //交易金额

        $postdata = array(
            "mchNo"       => $mch_id,
            "appId"       => $appId,
            "mchOrderNo"  => $mch_order_no,
            "wayCode"     => $pay_type,
            "currency"    => "INR",
            "amount"      => number_format($trade_amount, 0, '', ''),
            "subject"     => "SHOP",
            "body"        => "shop",
            "notifyUrl"   => $notify_url,
            "reqTime"     => date("Y-m-d H:i:s"),
            "version"     => "1.0",
            "signType"    => "MD5"
        );
        ksort($postdata);
        
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $sign = strtoupper(md5($md5str . "key=" . $merchant_key));
        $postdata['sign'] = $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://pay.grpay.net/api/anon/pay/unifiedOrder"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (isset($data['data']['payData']) && $data['data']['payData'] != '') {
            return $data['data']['payData'];
        } else {
            return ('');
        }
    }
}
