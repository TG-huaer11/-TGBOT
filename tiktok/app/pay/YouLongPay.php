<?php

namespace app\pay;

class YouLongPay
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
            "merchantId"        => $mch_id,
            "userId"            => 'Test' . rand(1,98) . 'UserId' . rand(1,98),
            "payMethod"         => $pay_type,
            "money"             => number_format($trade_amount, 0, '.', '') * 100,
            "bizNum"            => $mch_order_no,
            "notifyAddress"     => $notify_url,            
            "type"              => "recharge",

        );

        // var_dump($postdata);


        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $url = "https://api.youlong.pro/pay/order/cur/create?" . $md5str;
        $md5str = $md5str . 'key=' . $merchant_key;


        $sign = strtoupper(md5($md5str)); 
        $url = $url .'sign=' . $sign;

        return $url;

        // var_dump($md5str);
        // var_dump($url);

        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: x-www-form-urlencoded')
        // );
        
        // $res = curl_exec($ch);
        // curl_close($ch);
        // $data = json_decode($res, true);
        // var_dump($postdata);
        // var_dump($data);
        // if (isset($data['success']) && $data['success'] == 'true') {
        //     return $data['data']['url'];
        // } else {
        //     return ('');
        // }
    }
}
