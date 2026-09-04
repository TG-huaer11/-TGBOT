<?php

namespace app\pay;

class GocashPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥

        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址
        $pay_type = $pay["domain"]; //通道ID
        $trade_amount = $amount; //交易金额

        $postdata = array(
            "merchantId"        => $mch_id,
            "notifyUrl"         => $notify_url,
            "orderNo"           => $order_sn, 
            "payCode"           => $pay_type,
            "amount"            => number_format($trade_amount, 0, '.', ''), 
            "version"           => '1.0',
        );

        // var_dump($postdata);


        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = $md5str . 'key=' . $merchant_key;
        $sign = strtoupper(md5($md5str)); 

        var_dump($sign);

        $ch = curl_init("https://pay.gocash.cloud/cop/order/create");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: x-www-form-urlencoded')
        );
        
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);

        var_dump($postdata);
        var_dump($data);

        if (isset($data['code']) && $data['code'] == '200') {
            return $data['payInfo'];
        } else {
            return ('');
        }
    }
}
