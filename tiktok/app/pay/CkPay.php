<?php

namespace app\pay;

class CkPay
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
            "order_amount"        => number_format($trade_amount, 0, '', ''),
            "order_date"        => date("Y-m-d H:i:s"),
            "pay_code"          => $pay_type,
            "currency"          => "INR",
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
        curl_setopt($ch, CURLOPT_URL, "http://pay.dskbd.com/pay/order/create"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (isset($data['code']) && $data['code'] == 'SUCCESS') {
            return $data['pay_url'];
        } else {
            return "";
        }
    }
}
