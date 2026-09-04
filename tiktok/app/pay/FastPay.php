<?php

namespace app\pay;

class FastPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥
        $mch_id = $pay['sn'];
        $notify_url = $pay["notify_url"];
        $mch_order_no = $order_sn;
        $nonce_str = md5(mt_rand(1000, 9999) . time());
        $pay_type = $pay["domain"]; //通道ID
        $postdata = array(
            "merchantNo"        => $mch_id,
            "orderNo"           => $mch_order_no,
            "amount"            => $amount,
            "type"              => $pay_type,
            "notifyUrl"         => $notify_url,
            "ext"               => "test",
            "userName"          => "paul",
            "version"           => "2.0.0"
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $merchant_key));
        $postdata['sign'] = $sign;


        $ch = curl_init("https://api.fast8866.com/okex-admin/okex/api/v2/pay");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json')
        );

        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        // var_dump($postdata);
        // var_dump($data);
        if (isset($data['code']) && $data['code'] == '0') {
            return $data['url'];
        } else {
            return ('');
        }
    }
}
