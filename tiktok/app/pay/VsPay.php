<?php

namespace app\pay;

class VsPay
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
            "mer_no"            => $mch_id,
            "order_no"          => $mch_order_no,
            "order_amount"      => number_format($trade_amount, 2, '.', ''),
            "currency"          => "INR",
            "paytypecode"       => $pay_type,
            "returnurl"         => $notify_url,
            "method"            => "trade.create",
            "payname"           => "xiaoming",
            "payemail"          => "xiaoming@email.com",
            "payphone"          => "1111111111",
        );

        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = substr($md5str,0,strlen($md5str)-1);
        // var_dump($md5str);
        $sign = md5($md5str . $merchant_key);
        $postdata['sign'] = $sign;
        // var_dump($md5str);

        $ch = curl_init("http://www.verysecure2000.com/gateway/");
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
        // var_dump($res);
        // var_dump($data);
        if (isset($data['status']) && $data['status'] == 'success') {
            return $data['order_data'];
        } else {
            return ('');
        }
    }
}
