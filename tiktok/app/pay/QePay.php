<?php

namespace app\pay;

class QePay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        $merchant_key = $pay['ak']; //支付秘钥

        $version = '1.0'; // return=>JSON
        $mch_id = $pay['sn']; //商户号
        $notify_url = $pay["notify_url"]; //异步通知地址
        // $page_url = $pay["redirect_url"]; //同步跳转地址
        $mch_order_no = $order_sn; //订单号
        $pay_type = $pay["domain"]; //通道ID
        $trade_amount = $amount; //交易金额
        $order_date = date('Y-m-d H:i:s'); //订单时间
        // $bank_code = $_POST["bank_code"];
        $goods_name = "Recharge";
        $sign_type = 'MD5';
        // $mch_return_msg = $_POST["mch_return_msg"];

        $signStr = "";
        // if ($bank_code != "") {
        //     $signStr = $signStr . "bank_code=" . $bank_code . "&";
        // }

        $signStr = $signStr . "goods_name=" . $goods_name . "&";
        $signStr = $signStr . "mch_id=" . $mch_id . "&";
        $signStr = $signStr . "mch_order_no=" . $mch_order_no . "&";
        // if ($mch_return_msg != "") {
        //     $signStr = $signStr . "mch_return_msg=" . $mch_return_msg . "&";
        // }
        $signStr = $signStr . "notify_url=" . $notify_url . "&";
        $signStr = $signStr . "order_date=" . $order_date . "&";
        // $signStr = $signStr . "page_url=" . $page_url . "&";
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
            /** 下面这些参数有填写才需要提交，不填写的不需要提交也不需要参与签名 */
            /**'bank_code'=>$bank_code,'mch_return_msg'=>$mch_return_msg,*/
            // 'page_url' => $page_url,
            'sign_type' => $sign_type,
            'sign' => $sign
        );

        // var_dump($postdata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payment.qeapay.com/pay/web"); //支付请求地址
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
        if(isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            return($data['payInfo']);
        }else{
            return('');
        }
    }

    private function http_post($url, $data)
    {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'header' => 'Content-Encoding : gzip',
                'content' => $data,
                'timeout' => 15 * 60
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    private function http_post_res($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; zh-CN) AppleWebKit/535.12 (KHTML, like Gecko) Chrome/22.0.1229.79 Safari/535.12");
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    private function convToGBK($str)
    {
        if (mb_detect_encoding($str, "UTF-8, ISO-8859-1, GBK") != "UTF-8") {
            return  iconv("utf-8", "gbk", $str);
        } else {
            return $str;
        }
    }

    private function sign($signSource, $key)
    {
        if (!empty($key)) {
            $signSource = $signSource . "&key=" . $key;
        }
        return md5($signSource);
    }

    private function validateSignByKey($signSource, $key, $retsign)
    {
        if (!empty($key)) {
            $signSource = $signSource . "&key=" . $key;
        }
        $signkey = md5($signSource);
        if ($signkey == $retsign) {
            return true;
        }
        return false;
    }
}
