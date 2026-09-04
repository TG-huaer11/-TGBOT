<?php

namespace app\pay;

class WePay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $key = $pay['ak'];
        $version = '1.0';
        $mch_id = $pay['sn'];
        $notify_url = $pay["notify_url"];
        $page_url = $pay["redirect_url"];
        $mch_order_no = $order_sn;
        $pay_type = $pay["domain"];
        $trade_amount = $amount;
        $order_date = date('Y-m-d H:i:s');
        $goods_name = "Amazon";
        $sign_type = 'MD5';
        $signStr = "";
        $signStr = $signStr . "goods_name=" . $goods_name . "&";
        $signStr = $signStr . "mch_id=" . $mch_id . "&";
        $signStr = $signStr . "mch_order_no=" . $mch_order_no . "&";
        $signStr = $signStr . "notify_url=" . $notify_url . "&";
        $signStr = $signStr . "order_date=" . $order_date . "&";
        $signStr = $signStr . "page_url=" . $page_url . "&";
        $signStr = $signStr . "pay_type=" . $pay_type . "&";
        $signStr = $signStr . "trade_amount=" . $trade_amount . "&";
        $signStr = $signStr . "version=" . $version;
        $sign = $this->sign($signStr, $key);
        $postdata = array(
            'goods_name' => $goods_name,
            'mch_id' => $mch_id,
            'mch_order_no' => $mch_order_no,
            'notify_url' => $notify_url,
            'order_date' => $order_date,
            'pay_type' => $pay_type,
            'trade_amount' => $trade_amount,
            'version' => $version,
            'page_url' => $page_url,
            'sign_type' => $sign_type,
            'sign' => $sign
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payment.weglobalpayment.com/pay/web");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        
        // var_dump($data);

        if(isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            return($data['payInfo']);
        }else{
            return('');
        }
    }


    private function sign($signSource, $key)
    {
        if (!empty($key)) {
            $signSource = $signSource . "&key=" . $key;
        }
        return md5($signSource);
    }

}
