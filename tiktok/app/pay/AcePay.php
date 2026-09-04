<?php

namespace app\pay;

class AcePay
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
        $page_url = $pay["redirect_url"];

        $postdata = array(
            "mch_id"                => $mch_id,
            "mch_order_num"         => $mch_order_no,
            "price"                 => number_format($trade_amount, 2, '.', ''),
            "channel"               => $pay_type,
            "country"               => "INR",
            "attach"                => "zaa",
            "notify_url"            => $notify_url,
            "page_url"              => $page_url,
            "order_date"            => date("Y-m-d H:i:s"),  //订单时间,
            "timestamp"             => time(),
        );


        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        // $md5str = substr($md5str,0,strlen($md5str)-1);
        // var_dump($md5str);
        $sign = strtoupper(md5($md5str . "key=" . $merchant_key));
        $postdata['sign'] = $sign;
        $postdata['sign_type'] = "MD5";
        // var_dump($md5str);

        $ch = curl_init("https://api.ace-pay.vip/acepay/pay_in");
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
        if (isset($data['code']) && $data['code'] == '200') {
            return $data['data']['pay_url'];
        } else {
            return ('');
        }
    }
}
