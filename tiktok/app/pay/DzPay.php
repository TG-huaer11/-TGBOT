<?php

namespace app\pay;

use app\common\service\WalletService;
use app\common\service\UserService;
use think\facade\Db;

class DzPay
{
    // 代收
    public function recharge($pay, $amount, $order_sn)
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
        $goods_name = "Snapdeal";
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payment.dzxum.com/pay/web"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);

        // var_dump($postdata);
        // var_dump($data);

        if (isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            return ['code' => 1, 'msg' => '', 'url' => $data['payInfo']];
        } else {
            return ['code' => 0, 'msg' => $data['msg'], 'url' => ''];
        }
    }

    // 代收回调
    public function notify_recharge($post)
    {
        if (isset($post['tradeResult']) && $post['tradeResult'] == '1') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['mchOrderNo'];
            $orderInfo = Db::connect($connection)
                ->name('recharge')
                ->where('id', $orderNo)
                ->find();
            if ($orderInfo) {
                if ($orderInfo['status'] == 1) {
                    $message = 'Success. ' . $post['orderNo'];
                    $db = Db::connect($connection)
                        ->name('recharge')
                        ->where('id', $orderNo)
                        ->update([
                            'real_num' => $post['amount'],
                            'status' => 2,
                            'end_at' => time(),
                            'remark' => $message,
                        ]);
                    if ($db) {
                        (new \app\common\service\CnmService($connection))->recharge($orderNo, $orderInfo['uid'], $post['amount'], $message, json_encode($post));
                        (new \app\common\service\WalletService($connection))->balanceRecharge($orderInfo['uid'], $post['amount']);
                        return ('success');
                    } else {
                        return ('failed');
                    }
                } else {
                    return ('failed');
                }
            } else {
                return ('failed');
            }
        }
        return ('failed');
    }

    // 代付
    public function withdraw($params, $pay)
    {
        $merchant_key = $pay['sk']; //代付秘钥

        $apply_date = date('Y-m-d H:i:s'); // 申请时间
        $bank_code = $pay['domain']; // 固定 IDPT0001
        $mch_id = $pay["sn"]; //商户号
        $mch_transferId = $params ['order_sn']; //订单号
        $transfer_amount = $params ['amount']; //转账金额
        $receive_name = $params ['name']; //姓名
        $receive_account = $params ['account']; //银行卡号
        $remark = $params ['ifsc']; //IFSC
        $back_url = $pay['notify_url']; //异步通知

        // sign
        $sign_type = "MD5";
        $signStr = "";
        $signStr = $signStr . "apply_date=" . $apply_date . "&";
        $signStr = $signStr . "back_url=" . $back_url . "&";
        $signStr = $signStr . "bank_code=" . $bank_code . "&";
        $signStr = $signStr . "mch_id=" . $mch_id . "&";
        $signStr = $signStr . "mch_transferId=" . $mch_transferId . "&";
        $signStr = $signStr . "receive_account=" . $receive_account . "&";
        $signStr = $signStr . "receive_name=" . $receive_name . "&";
        $signStr = $signStr . "remark=" . $remark . "&";
        $signStr = $signStr . "transfer_amount=" . $transfer_amount;

        $sign = $this->sign($signStr, $merchant_key);
        // dump($sign);

        $postdata = array(
            'apply_date' => $apply_date,
            'back_url' => $back_url,
            'bank_code' => $bank_code,
            'mch_id' => $mch_id,
            'mch_transferId' => $mch_transferId,
            'receive_account' => $receive_account,
            'receive_name' => $receive_name,
            'transfer_amount' => $transfer_amount,
            'remark' => $remark,
            'sign_type' => $sign_type,
            'sign' => $sign
        );
        // dump($postdata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payment.dzxum.com/pay/transfer");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        // var_dump($res);
        $data = json_decode($res, true);
        if (isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            return ([
                'res' => true,
                'data' => $data
            ]);
        } else {
            return ([
                'res' => false,
                'data' => $data
            ]);
        }
    }

    // 代付回调
    public function notify_withdraw($post)
    {
        // 判断status交易状态 1成功 2失败
        if (isset($post['tradeResult']) && $post['tradeResult'] == '1') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['merTransferId']; //订单号
            // $amount = $post['amount']; //实际支付金额
            $tradeNo = $post['tradeNo']; //平台订单号
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 4)
                ->find();
            if ($orderInfo) {
                // if ($orderInfo['status'] == 4) {    // 不判断是否为提款中, 直接更新
                $message = "Success. " . $tradeNo;
                $db = Db::connect($connection)
                    ->name('deposit')
                    ->where('id', $orderNo)
                    ->update([
                        'status' => 2, //提款成功
                        'remark' => "Success. " . $tradeNo,
                    ]);
                if ($db) {
                    (new \app\common\service\UserService($connection))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                    (new \app\common\service\CnmService($connection))->despoit($orderNo, $orderInfo['uid'], 2, $orderInfo['num'], $message, json_encode($post));
                    return ('success');
                }
                // }
            }
        }
        if (isset($post['tradeResult']) && $post['tradeResult'] == '2') {
            $connection = $post ['connection'];
            // 处理数据
            $orderNo = $post['merTransferId']; //订单号
            // $amount = $post['amount']; //实际支付金额
            $tradeNo = $post['tradeNo']; //平台订单号
            $orderInfo = Db::connect($connection)
                ->name('deposit')
                ->where('id', $orderNo)
                ->where('status', 4)
                ->find();
            if ($orderInfo) {
                // if ($orderInfo['status'] == 4) {     // 不判断是否为提款中, 直接更新
                $message = "Payment Fail. " . $tradeNo;
                $db = Db::connect($connection)
                    ->name('deposit')
                    ->where('id', $orderNo)
                    ->update([
                        'status' => 3, //提款失败
                        'remark' => $message,
                    ]);
                if ($db) {
                    (new \app\common\service\WalletService($connection))->balanceRecharge($orderInfo['uid'], $orderInfo['num']); // RNM,退钱
                    (new \app\common\service\CnmService($connection))->despoit($orderNo, $orderInfo['uid'], 1, $orderInfo['num'], $message, json_encode($post));
                    (new \app\common\service\UserService($connection))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                    return ('success');
                }
                // }
            }
        }
        return ('failed');
    }

    // 代付查询
    public function query_payout($order_sn, $pay)
    {

        $merchant_key = $pay['sk']; //代付秘钥
        $mch_id = $pay["sn"]; //商户号
        // Sign
        $signStr = "";
        $signStr = $signStr . "mch_id=" . $mch_id . "&";
        $signStr = $signStr . "mch_transferId=" . $order_sn;
        $sign = $this->sign($signStr, $merchant_key);

        $postdata = array(
            'mch_id' => $mch_id,
            'mch_transferId' => $order_sn,
            'sign_type' => 'MD5',
            'sign' => $sign
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payment.dzxum.com/query/transfer");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        // var_dump($res);
        $data = json_decode($res, true);
        if (isset($data['respCode']) && $data['respCode'] == 'SUCCESS') {
            switch ($data['tradeResult']) {
                case '0':
                    $text = '申请成功';
                    return ([
                        'res' => false,
                        'data' => $text
                    ]);
                    break;

                case '1':
                    $text = '转账成功';
                    // 处理数据
                    $orderNo = $data['merTransferId']; //订单号
                    // $amount = $post['amount']; //实际支付金额
                    $tradeNo = $data['tradeNo']; //平台订单号
                    $orderInfo = Db::connect($pay ['connection'])
                        ->name('deposit')
                        ->where('id', $orderNo)
                        ->find();
                    if ($orderInfo) {
                        if ($orderInfo['status'] == 4) {
                            $message = "Success. " . $tradeNo;
                            $db = Db::connect($pay ['connection'])
                                ->name('deposit')
                                ->where('id', $orderNo)
                                ->update([
                                    'status' => 2, //提款成功
                                    'remark' => "Success. " . $tradeNo,
                                ]);
                            if ($db) {
                                (new UserService($pay ['connection']))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                            }
                        }
                    }
                    return ([
                        'res' => true,
                        'data' => $text
                    ]);
                    break;

                case '2':
                    $text = '转账失败';
                    // 处理数据
                    $orderNo = $data['merTransferId']; //订单号
                    // $amount = $post['amount']; //实际支付金额
                    $tradeNo = $data['tradeNo']; //平台订单号
                    $orderInfo = Db::connect($pay ['connection'])
                        ->name('deposit')
                        ->where('id', $orderNo)
                        ->find();
                    if ($orderInfo) {
                        if ($orderInfo['status'] == 4) {
                            $message = "Payment Fail. " . $tradeNo;
                            $db = Db::connect($pay ['connection'])
                                ->name('deposit')
                                ->where('id', $orderNo)
                                ->update([
                                    'status' => 3, //提款失败
                                    'remark' => $message,
                                ]);
                            if ($db) {
                                (new WalletService($pay ['connection']))->balanceRecharge($orderInfo['uid'], $orderInfo['num']); // RNM,退钱
                                (new UserService($pay ['connection']))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                            }
                        }
                    }
                    return ([
                        'res' => true,
                        'data' => $text
                    ]);
                    break;

                case '3':
                    $text = '转账拒绝';
                    $text = '转账失败';
                    // 处理数据
                    $orderNo = $data['merTransferId']; //订单号
                    // $amount = $post['amount']; //实际支付金额
                    $tradeNo = $data['tradeNo']; //平台订单号
                    $orderInfo = Db::connect($pay ['connection'])
                        ->name('deposit')
                        ->where('id', $orderNo)
                        ->find();
                    if ($orderInfo) {
                        if ($orderInfo['status'] == 4) {
                            $message = "Payment Fail. " . $tradeNo;
                            $db = Db::connect($pay ['connection'])
                                ->name('deposit')
                                ->where('id', $orderNo)
                                ->update([
                                    'status' => 3, //提款失败
                                    'remark' => $message,
                                ]);
                            if ($db) {
                                (new WalletService($pay ['connection']))->balanceRecharge($orderInfo['uid'], $orderInfo['num']); // RNM,退钱
                                (new UserService($pay ['connection']))->sendMsg($orderInfo['uid'], 0, 'Withdrawal Notice', $message, 1);
                            }
                        }
                    }
                    return ([
                        'res' => true,
                        'data' => $text
                    ]);
                    break;

                case '4':
                    $text = '处理中';
                    return ([
                        'res' => false,
                        'data' => $text
                    ]);
                    break;

                default:
                    return ([
                        'res' => false,
                        'data' => "订单查询失败({$data['errorMsg']})"
                    ]);
                    break;
            }
        } else {
            return ([
                'res' => false,
                'data' => "订单查询失败({$data['errorMsg']})"
            ]);
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
            return iconv("utf-8", "gbk", $str);
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
