<?php

namespace app\pay;

class VictoryPay
{
    // $pay 
    public function payin($pay, $amount, $order_sn)
    {
        //定义数据
        $merchant_id = $pay['sn'];                  //商户号
        $mer_order_num = $order_sn;                 //订单号
        $price = $amount;                           //交易金额
        $pay_code = $pay["domain"];                 //通道ID
        $attach  = 'attach';                        //附带参数
        $notify_url = $pay["notify_url"];           //异步通知地址
        $page_url = $pay["redirect_url"];           //同步跳转地址
        $order_date = date('Y-m-d H:i:s');          //时间格式yyyy-MM-dd HH:mm:ss
        $timestamp = time();                        //时间戳
        $sign_type = 'MD5';                         //签名方式
        $merchant_key = $pay['ak'];                 //支付秘钥

        //组合参数
        $postdata = array(
            "merchant_id"       => $merchant_id,
            "mer_order_num"     => $mer_order_num,
            "price"             => $price,
            "pay_code"          => $pay_code,
            "attach"            => $attach,
            "notify_url"        => $notify_url,
            "page_url"          => $page_url,
            "order_date"        => $order_date,
            "timestamp"         => $timestamp
        );

        //签名
        ksort($postdata);
        $md5str = "";
        foreach ($postdata as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = $md5str . "key=" . $merchant_key;
        $sign = md5($md5str);
        $sign = strtoupper($sign);
        $postdata['sign'] = $sign;
        $postdata['sign_type'] = $sign_type;
        
        // var_dump($md5str);
        // var_dump($sign);
        // var_dump($postdata);
        
        //请求头
        $header = [
            'Content-Type: application/json',
        ];
        //请求
        $result = $this->http_restfull_curl_timeout('https://api.victory-pay.com/payweb/recharge', 'POST', json_encode($postdata), $header, 30, 30);
        if($result['curl_code'] != 200){
            return ('');
        }
        $return_data = json_decode($result['curl_response'], true);
        if($return_data['code']  == 200){
            return $return_data['data']['pay_url'];
        } else {
            return ('');
        }
    }
    
    /**
     * 发送http请求
     * @param $url
     * @param $method
     * @param string $data
     * @param string $headers
     * @param int $con_timeout
     * @param int $timeout
     * @return mixed
     */
    private function http_restfull_curl_timeout($url, $method, $data = "", $headers = "", $con_timeout = 3, $timeout = 20)
    {
        //如果$data是字符串
        if (is_string($data)) {
            $params = $data;
        }
        if(is_array($data)){
            $params = json_encode($data);
        }
        //开始curl操作
        $ch = curl_init();
        //超时时间设置
        $curl_con_timeout = $con_timeout;        //设置连接超时时间（单位秒） —— 在发起连接前等待的时间，即用来告诉PHP脚本在成功连接服务器前等待多久（连接成功之后就会开始缓冲输出），这个参数是为了应对目标服务器的过载，下线，或者崩溃等可能状况
        $curl_timeout = $timeout;         //设置执行超时时间（单位秒）—— 允许执行的最长秒数，即用来告诉成功PHP脚本，从服务器接收缓冲完成前需要等待多长时间
        //资源地址
        curl_setopt($ch, CURLOPT_URL, $url);
        //https请求不验证证书处理
        if (strpos(strtolower($url), "https://") !== false) {
            //https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        //http头文件类型处理
        if ($headers == "") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); //CURLOPT_HEADER值为false或零(默认x-www-from-urlencod方式提交数据)，服务端可以通过 _POST或_REQUEST获取参数值
            $params = http_build_query($data);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //使用cURL下载MP3文件为例说明：CURLOPT_CONNECTTIMEOUT可以设置为10秒，标识如果服务器10秒内没有响应，脚本就会断开连接；CURLOPT_TIMEOUT可以设置为100，如果MP3文件100秒内没有下载完成，脚本将会断开连接
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_con_timeout);  //连接超时，请求服务器20秒内没响应，中断连接(如果服务器10秒没有响应，脚本就会断开连接)
        curl_setopt($ch, CURLOPT_TIMEOUT, $curl_timeout);  //允许执行的最长秒数，执行超时，连接服务器后，30秒内没执行完请求，中断连接(如果服务器30秒还没执行完请求，脚本将会断开连接)
        //请求类型处理
        $method = strtoupper($method); //转换为大写
        if ($method == "GET") {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } elseif ($method == "PUT") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } elseif ($method == "DELETE") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        //获得返回值
        $ret_contents = curl_exec($ch);
        //当前会话错误信息的数字编号
        $curl_errno = curl_errno($ch);  //等于0表示，请求OK
        //当前会话错误信息的字符串
        $curl_error = curl_error($ch);
        //获取一个curl连接资源句柄的信息
        //$curl_info = curl_getinfo($ch);
        //$curl_info["curl_error"] = sprintf("error (%d): %s",$curl_errno,$curl_error);
        curl_close($ch);
        //$curl_errno大于0，请求报错(http报错)
        if ($curl_errno > 0) {
            //响应超时
            if ($curl_errno == 28) {
                //返回
                $curl_data["curl_code"] = 408;   //Request Timeout，http请求超时
            } else {
                //其他报错
                //返回
                $curl_data["curl_code"] = 201;
            }
            $curl_data["curl_response"] = sprintf("error (%d): %s", $curl_errno, $curl_error);
        } else {
            //请求成功(http请求成功)
            $curl_data["curl_code"] = 200;
            $curl_data["curl_response"] = $ret_contents;
        }
        //返回结果
        return $curl_data;
    }
}
