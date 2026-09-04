<?php

namespace app\pay;

class MacPay
{
    // $pay 
    public function payin($pay, $a, $o)
    {
        $p = $pay["sn"];
        $n = $pay["domain"];
        $u = $pay["notify_url"];
        $key = '-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDVrsBEFghAAVrC
Z1nMmBEwwdFgqMkFCIlT30Nytlo3BAAKTbPMYnLFkjBzMst0zplzaXiqo6g2bRCI
NVkZfPu/03oUBu+R/oPmcCIfiXeg2YkgoB9a+D/byDicyGsRFrwy67NeduIPZUmS
UTk3g0+vadd7b3Jn6/Q3/b6fVyiBL/ynav+Cj+0f7Epgqx8f0zz9WgGpqYQ9FIpu
qxTHf/tWi3q/m9GwzA2IUXS3io6YhZAf+KUZvk4f+YWMD0lhSgWRrkJLJ4CjRQTm
MPzkxnr84JgOnGquH3uLk44wyejBfQvGua0TtIL5bMoMivI6+8Q6MwGd2Q2CB06q
qajl4B/jAgMBAAECggEAS16UGm/bARh5NtDVb8DDb3stqvZd0RStu5OoarG/KGCU
0w2NOa4P5Xgm9rsX4F9u6LJvCWEoV+ooRqLqhn77HTIugRnhLpGjXIh4wL7wHyAb
qhJQfPnLLG526XYXrbNjNmLEpbExE1Uami1kDRyX4rtmcfdhVx52ybkKn5FSjB4H
e1foIQ0x7n7ZLqNuRPNe9ocoHOKKNUKcca9BfPN6ZyAtvT9ltyjv1qxJjcu36WDA
Da5csnaJqYc8SEcQz8swhy7F8VAsGzy0uCpGHML1DJNKGuekR0Ut68ZN5YjU5be3
vOVApNAqFlAs4pjayOIZxB1DJDCkoBqCKwJM6Qw3wQKBgQD8XAdNzvJXuePJkO1Z
LHZ489d2CDk0dbkvCCJ8DJ/Jhy/64Rkylz/7ZvItib6oVAcfqHWGQe0y0KhvqGMt
X1qo98PN+gzBDbV5OK/Q24k3FqGdXZ6OYdJ76Wvw0Qg3VGnS8el8GjFivKety5Ro
iJjmnAmHb0iSjRu2vn5mWmr9iwKBgQDYw+M8H45P2od3OgGVe+p6IGOB4nB0XxHN
jdRbG9WGnMjRc225YnokHqsolwkDI06gJjKxVBUDvg13Wd4Q22U8ahTV3FrganMP
8WF5NuYnmRMYIB3ls4tE0RvEwc2JwDdacmprx6rrAfYGS56ifBEPyIWgQypdF2Tb
apdZRupiCQKBgQCEAvRDLK2zXGefarPugQbckNo2QWkDW03rH3tCnyv7NT/RIm2W
/G4Y6ipnzfWxgntTgUExYU1e1q418sUm2AnJ+AoytspzNuOmrROz0xP9gFY8xtuJ
qfx8m4e+qup8XykYkznLlLwe5Ydlr+hLoqExiZCmi31QRIap2w6uJkBvrwKBgQDU
Inia2WMDwSB7zPfJ3Dhvhoz7iqh1KqkYmmmtNEM2du+NE0LZf4d7G8xzb8QxHveO
gXNw2ZGrVO6G1BsgMiYUBtkXJoyFPYgXnSnAX7rEG+l03dGEf76W/XIIj4Xf/o3t
ZTXC/ufFD+k+5fh8maB06s/jNHgcHI3msZ0mkOFGSQKBgG2dPAvt0VldM1XsLdUi
1qRuvIgC2Kott/cImOZDYgHTQXHX8Twjg93XYIqAWZVLsb9DVWehCKz0KTXhU9zK
gPxupR6U80CHSfNDrEw3l2CS4EG0sYI/fXEYP/+bcbF7+KfRF1RSH+cjk9f0kwM/
BqQdlJoFXbVKCcnvWnO7VIjK
-----END PRIVATE KEY-----';
        $bizContent = [
            'product_no' => $n,
            'order_sn' => $o,
            'amount' => $a,
            'notify_url' => $u,
        ];
        ksort($bizContent);
        $str = '';
        foreach ($bizContent as $Key => $value) {
            if ($value !== '') {
                $str .= $Key . '=' . $value . '&';
            }
        }
        $Sign = rtrim($str, '&');
        $postdata = [
            'partner' => $p,
            'sign_type' => 'RSA',
            'charset' => 'UTF-8',
            'language' => 'zh-cn',
            'timestamp' => time(),
            'biz_content' => json_encode($bizContent),
        ];
        $key = openssl_get_privatekey($key);
        $sign = "";
        openssl_sign($Sign, $sign, $key, OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign);
        $postdata['sign'] = $sign;
        $cURL = curl_init();
        curl_setopt($cURL, CURLOPT_URL, "https://api.macpayss.com/gateway/deposit/create"); //支付请求地址
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($cURL, CURLOPT_POST, true);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_HEADER, false);
        $res = curl_exec($cURL);
        curl_close($cURL);
        $Data = json_decode($res, true);
        $data = $Data['data'];
        if (isset($data['trade_url']) && $data['trade_url'] != '') {
            return $data['trade_url'];
        } else {
            return ('');
        }
    }
}
