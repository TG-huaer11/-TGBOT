<?php

namespace app\pay;

class SeproPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥

        $version = '1.0'; // return=>JSON
        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址
        $mch_order_no = $order_sn; //订单号
        $pay_type = $pay["domain"]; //通道ID
        $trade_amount = (int)$amount; //交易金额
        $order_date = date('Y-m-d H:i:s'); //订单时间
        $goods_name = "Recharge";
        $sign_type = 'MD5';

        $signStr = "";
        $signStr = $signStr . "goods_name=" . $goods_name . "&";
        $signStr = $signStr . "mch_id=" . $mch_id . "&";
        $signStr = $signStr . "mch_order_no=" . $mch_order_no . "&";
        $signStr = $signStr . "notify_url=" . $notify_url . "&";
        $signStr = $signStr . "order_date=" . $order_date . "&";
        $signStr = $signStr . "pay_type=" . $pay_type . "&";
        $signStr = $signStr . "trade_amount=" . $trade_amount . "&";
        $signStr = $signStr . "version=" . $version;
        $sign = $this->sign($signStr, $merchant_key);

        $postdata = array(
            'goods_name' => $goods_name,
            'mch_id' => $mch_id,
            'mch_order_no' => $mch_order_no,
            'notify_url' => $notify_url,
            'order_date' => $order_date,
            'pay_type' => $pay_type,
            'trade_amount' => $trade_amount,
            'version' => $version,
            'sign_type' => $sign_type,
            'sign' => $sign
        );

        // var_dump($postdata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://pay.sepropay.com/sepro/pay/web"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);

        // var_dump($res);

        curl_close($ch);
        $data = json_decode($res, true);
        if (isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            return ($data['payInfo']);
        } else {
            return ('');
        }
    }

    private function sign($signSource, $key)
    {
        if (!empty($key)) {
            $signSource = $signSource . "&key=" . $key;
        }
        return strtolower(md5($signSource));
    }
}
