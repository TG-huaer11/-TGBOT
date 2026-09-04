<?php

namespace app\pay;

class YbbPay
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
            "pay_memberid"       => $mch_id,
            "pay_orderid"        => $mch_order_no,
            "pay_type"           => $pay_type,
            "pay_amount"         => number_format($trade_amount, 2, '.', ''),
            "pay_applytime"      => time(),//("Y-m-d H:i:s"),  //订单时间
            "pay_notifyurl"      => $notify_url,
            "pay_returnurl"      => 'https://www.snapdealhappy.com',
            "pay_email"          => "q@qq.com",
            "pay_mobile"         => "1234567890",
            "pay_name"           => "gaga",
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $merchant_key));
        $postdata['pay_sign'] = $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.ybbpay.net/pay"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true); 

        // var_dump($postdata);
        // var_dump($res);

        if (isset($data['code']) && $data['code'] == '1000') {
            return $data['data']['pay_url'];
        } else {
            return ('');
        }
    }
}
