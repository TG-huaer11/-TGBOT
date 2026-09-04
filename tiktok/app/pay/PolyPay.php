<?php

namespace app\pay;

class PolyPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥

        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址

        $mch_order_no = $order_sn; //订单号
        $trade_amount = $amount; //交易金额

        $postdata = array(
            "mer_no"       => $mch_id,
            "mer_order_no"          => $mch_order_no,
            "pname" => 'ratnesh',
            "pemail" => 'pemail@polys.com',
            "phone" => '917398781472',
            "order_amount"            => number_format($trade_amount, 0, '', ''),
            "country_code" => 'IND',
            "cyy_no" => 'INR',
            "pay_type" => 'UPI',
            "notify_url"        => $notify_url,
        );
        ksort($postdata);
        $sign = strtolower(md5($mch_id . $mch_order_no . $merchant_key));
        $postdata['sign'] = $sign;
        // var_dump($postdata);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.polymerizations.com/poi/pay/index/PayOrderCreate"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        // var_dump($res);
        $data = json_decode($res, true);
        if (isset($data['code']) && $data['code'] == '1') {
            return $data['pay_url'];
        } else {
            return ('');
        }
    }
}
