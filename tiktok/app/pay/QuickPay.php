<?php

namespace app\pay;

class QuickPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥
        $ak_id = $pay['sk']; //秘钥编号
        $mch_id = $pay['sn']; //商户号
        $type = $pay['domain'];
        $notify_url = $pay["notify_url"]; //异步通知地址
        $postdata = array(
            "sys_user_id"       => $mch_id,
            "sys_cert_no"       => $ak_id,
            "sys_sign_method"   => "MD5",
            "sys_api_name"      => "trade.create",
            "sys_api_version"   => "1.0",
            "outTradeSn"        => $order_sn,
            "currency"          => "INR",
            "amount"            => bcmul($amount, 100, 0),
            "payType"           => $type,
            "content"           => "Shop",
            "callbackURL"       => $notify_url,
            "clientIp"          => '1.1.1.1',
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtolower(md5($md5str . "key=" . $merchant_key));
        $postdata['sys_sign'] = $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ep-sl.mer.sminers.com/api/formapi"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);

        // if (isset($data['errorCode']) && $data['errorCode'] == 'SUCCESS') {
        return $data["data"]["payURL"];
        // } else {
        //     return ('');
        // }
    }
}
