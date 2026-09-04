<?php

namespace app\pay;

class PayGloabal
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
            "mch_id"            => $mch_id,
            "mch_order_no"      => $mch_order_no,
            "trade_amount"      => number_format($trade_amount, 2, '.', ''),
            "pay_type"          => $pay_type,
            "notify_url"        => $notify_url,
            "order_date"        => date("Y-m-d H:i:s"),  //订单时间
            "goods_name"        => "test",
            "version"           => "1.0",
        );
 

        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = md5($md5str . "key=" . $merchant_key);
        $postdata['sign'] = $sign;
        $postdata['sign_type'] = "MD5";
        // var_dump($md5str);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://pay.8paygloabal.com/pay/web"); //支付请求地址
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
        // var_dump($data);
        if (isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            return $data['payInfo'];
        } else {
            return ('');
        }
    }
}
