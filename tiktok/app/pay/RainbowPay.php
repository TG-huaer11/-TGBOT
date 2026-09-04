<?php

namespace app\pay;

class RainbowPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak'];
        $mch_id = $pay['sn'];
        $notify_url = $pay["notify_url"];
        $signStr = "";
	$signStr = $signStr . "amount=" . $amount . "&";
        $signStr = $signStr . "mchId=" . $mch_id . "&";
        $signStr = $signStr . "notifyUrl=" . $notify_url . "&";
        $signStr = $signStr . "orderNo=" . $order_sn . "&";
	$signStr = $signStr . "passageId=" . $pay['domain'];
        $sign = $this->sign($signStr, $merchant_key);

        $postdata = [
            'mchId' => $mch_id,
            'notifyUrl' => $notify_url,
            'orderNo' => $order_sn,
            'passageId' => $pay['domain'],
            'amount' => $amount,
            'sign' => $sign
        ];

        //var_dump($postdata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://apis.rainbowpay.xyz/client/collect/create"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);

        //var_dump($res);

        curl_close($ch);
        $data = json_decode($res, true);
        if (isset($data['success']) && $data['success'] == TRUE) {
            return ($data['data']['payUrl']);
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
