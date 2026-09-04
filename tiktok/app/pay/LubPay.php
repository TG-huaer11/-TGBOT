<?php

namespace app\pay;

class LubPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn, $extend = [])
    {
        $private_key = $pay['sk'];

        $merchant_code = $pay["sn"];
        $notify_url = $pay["notify_url"];
        $pageUrl = $pay["redirect_url"];
        $busicode = $pay["domain"];

        $param = array(
            'busi_code'     => $busicode,
            'ccy_no'        => "INR",
            'mer_no'        => $merchant_code,
            'mer_order_no'  => $order_sn,
            'notifyUrl'     => $notify_url,
            'pageUrl'       => $pageUrl,
            'order_amount'  => $amount,
            'pname'         => "zhangsan",
            'pemail'        => "test@gmail.com",
            'phone'         => "13122336688",
        );

        // var_dump($param);
        ksort($param);
        $str = '';
        foreach ($param as $k => $v) {
            if (!empty($v)) {
                $str .= (string) $k . '=' . $v . '&';
            }
        }
        $str = rtrim($str, '&');
        $encrypted = '';
 
        $pem = chunk_split($private_key, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);
        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $encrypted = str_replace(array('+', '/', '='), array('-', '_', ''), $encrypted);
        $param['sign'] = $encrypted;

        $postdata = json_encode($param);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://crsqi.mywsypay.com/ty/orderPay");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($postdata),
        ));
        $res = curl_exec($ch);

        $data = json_decode($res, true);

        // var_dump($postdata);
        // var_dump($data);

        if (isset($data['status']) && $data['status'] == 'SUCCESS') {
            return urldecode($data['order_data']);
        } else {
            return ('');
        }
    }

}
