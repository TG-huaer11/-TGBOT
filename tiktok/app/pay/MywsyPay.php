<?php

namespace app\pay;

class MywsyPay
{
    // $pay 
    public function payin($pay, $amount, $order_no)
    {
        $key = $pay['ak'];
        //$key = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAOLBA7ybI/nuOl2ViqsEd2Cdz2Et0kqzMJdak9ajeUZTtUWU8uCLJhlEqwW1VZLaKhUZYHyY/te/MDMRSK4o4hyFUBF7d5JGSunr3wBh9zSRuyS/HfJ37M4vodCzfpNIfbhe6XTmrZCEFIRV4Qst/tFtJRg8lGiJZIXywPkFCajVAgMBAAECgYEAwKAIxZrhN/rZGBDmKJt1sdy9g6dCQnTwbVLjze03I7gOgJqkrH1okwSTaBkAJe0w3JWfMplhu/NNRcSEAnb2hHwygDKcQvLP4Sgn8YrDXcTXu0bLJAKofTWMBCjK9OR6+umB5aUKYACu2aq5m0f0LsWnQIFrI1Bjpfw/TRwTwAECQQD8HsV/HwgM/iqEVPiszgLu2mHqIenU8j82ps3UTSoVSFUwonE4dH3h5PRTZtyLF7MfqrYsvJeqgQJh7b1K4PjVAkEA5j5QDy2lBbLdzIWC6lFD9G9zIKk7wbKSsm/xNMVjvWfjdhlLATeZ5EOEeE6SctgUIvixRM2U1BjDzwsKfd3wAQJAVC4KD0nTLFNo6spcRYZ7oDi2XLB6HKnNxXeoXext0rFWEGkMmKb8qQIDOh2sIZ0GJ9qd/Q3zLfDpVL1GaMv5CQJABS2tm0nJhmFJf8oY8bA2OQ8wpbFouKiNiBngcPFnluD5SrSy7SoU+f9SwWny/UZC3a5+Pi/pgwOzk7qJ197gAQJBAM3ueXAHTRehlozyD2g/JfOHa59VNBxQwQqCvxmu2jcQixp0oS1S58Kz/8EbQ2V6rVC9QEQUbo0sq+DZu4G6v1M=';
        $postdata = [
            'mer_no' => $pay["sn"],
            'mer_order_no' => $order_no,
            'pname' => 'zhangsan',
            'pemail' => 'test@gmail.com',
            'phone' => '13122336688',
            'order_amount' => $amount,
            'ccy_no' => 'INR',
            'busi_code' => $pay["domain"],
            'notifyUrl' => $pay["notify_url"],
            'pageUrl' => $pay["redirect_url"],
        ];
        ksort($postdata);
        $str = '';
        foreach ($postdata as $k => $v) {
            if (!empty($v)) {
                $str .= (string) $k . '=' . $v . '&';
            }
        }
        $str = rtrim($str, '&');
        $encrypted = '';
        $pem = chunk_split($key, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);
        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $encrypted = str_replace(array('+', '/', '='), array('-', '_', ''), $encrypted);
        $postdata['sign'] = $encrypted;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://crsqi.mywsypay.com/ty/orderPay");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] == 'SUCCESS') {
            return $data['order_data'];
        } else {
            return ('');
        }
    }
}
