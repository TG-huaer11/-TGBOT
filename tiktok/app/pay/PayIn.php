<?php

namespace app\pay;

class PayIn
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
            "pay_amount"         => number_format($trade_amount, 2, '.', ''),
            "pay_applydate"      => date("Y-m-d H:i:s"),  //订单时间
            "pay_bankcode"       => $pay_type,
            "pay_notifyurl"      => $notify_url,
            "pay_callbackurl"    => 'https://www.snapdealhappy.com',
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $merchant_key));
        $postdata['pay_md5sign'] = $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://Payin.pro/Pay_Index.html"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        // var_dump($postdata);
        // var_dump($res);
        $data = json_decode($res, true);
        if (isset($data['pay_url'])) {
            return $data['pay_url'];
        } else {
            return ('');
        }
    }
}
