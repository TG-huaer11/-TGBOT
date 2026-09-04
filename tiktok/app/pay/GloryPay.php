<?php

namespace app\pay;

class GloryPay
{
    // $pay 
    public function payin($pay, $a, $n)
    {
        $a = $pay['sn'];
        $u = $pay["notify_url"];
        $c = $pay["redirect_url"];
        $b = $pay["domain"];
        $params = [
            "pay_memberid"    => $a,
            "pay_orderid"     => $n,
            "pay_applydate"   => date("Y-m-d H:i:s"),
            "pay_bankcode"    => $b,
            "pay_notifyurl"   => $u,
            'pay_callbackurl' => $c,
            "pay_amount"      => number_format($a, 2, '.', ''),
        ];
        ksort($params);
        $md5str = '';
        $key = $pay['ak'];
        foreach ($params as $Key => $val) {
            $md5str = $md5str . $Key . "=" . $val . "&";
        }
        $params['type'] = 'url';
        $params['pay_productname'] = 'SHOP';
        $params['pay_md5sign'] = strtoupper(md5($md5str . "key=" . $key));
        $data = http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://glorypayment.com/Pay_Index.html");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
