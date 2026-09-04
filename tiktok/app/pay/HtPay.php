<?php

namespace app\pay;

class HtPay
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
            "pay_memberid"      => $mch_id,
            "pay_orderid"       => $mch_order_no,
            "pay_amount"        => number_format($trade_amount, 2, '.', ''),
            "pay_applydate"     => date("Y-m-d H:i:s"),
            "pay_bankcode"      => $pay_type,
            "pay_notifyurl"     => $notify_url,
        );
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $merchant_key));
        $postdata['pay_md5sign'] = $sign;
        $postdata['return_type'] = 'json';
        $postdata['email'] = "test@123.com";
        $postdata['customer_id'] = "123312";
        $postdata['customer_name'] = "test";
        $postdata['customer_phone'] = "918038123";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.htpayio.com/Pay_Index.html"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
        $array = [];
        preg_match($regex, $res, $array);
        if (isset($array[0])) {
            return $array[0];
        } else {
            return "";
        }
    }
}
