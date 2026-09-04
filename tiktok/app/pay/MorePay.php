<?php

namespace app\pay;

class MorePay
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
            "paytypecode"       => $pay_type,
            "returnurl"         => $notify_url,
            "method"            =>"trade.create",
            "currency"          =>"INR",
            "payemail"          =>"woaizhongguo@gmail.com",
            "payname"           =>"peterlang",
            "payphone"          =>"33336666888",

        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = substr($md5str,0,strlen($md5str)-1) . $merchant_key;
        // var_dump($md5str);
        $sign = md5($md5str);
        $postdata['sign'] = $sign;
        
        $ch = curl_init("http://more-pay.vip/gateway/");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json')
        );


        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://more-pay.vip/gateway/"); //支付请求地址
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        // var_dump($postdata);
        // var_dump($res);
        if (isset($data['status']) && $data['status'] == 'success') {
            return $data['order_data'];
        } else {
            return ('');
        }
    }
}
