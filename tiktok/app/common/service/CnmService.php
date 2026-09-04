<?php

namespace app\common\service;

use app\common\model\OrangeLogBalance;

/**
 * 流水日志服务
 * C N M
 * [约定] SB: 1充值 2提款 3彩金 4扣款 5佣金 6订单 0其他
 * [约定] NC: 1+ 2-
 */
class CnmService extends BaseService
{

    /**
     * 充值
     * @param $orderno
     * @param $uid
     * @param $number
     * @param $msg
     * @param $textmsg
     * @param $adminer
     * @return void
     */
    public function recharge($orderno, $uid, $number, $msg, $textmsg = "", $adminer = "")
    {
        $data = [
            'orderno' => $orderno,
            'uid' => $uid,
            'action' => 1,
            'i' => 1,
            'number' => $number,
            'msg' => $msg,
            'textmsg' => $textmsg,
            'created' => time(),
            'adminer' => $adminer,
        ];
        return OrangeLogBalance::insert($data);
    }

    /**
     *提款
     * @param string $orderno 订单号
     * @param int $uid 用户ID
     * @param int|string $i 方向
     * @param int|float $number 数额
     * @param string $msg 备注
     * @param string $textmsg 备注(长文本)
     * @param string $adminer 管理员
     * @return int|string
     */
    public function despoit($orderno, $uid, $i, $number, $msg, $textmsg = "", $adminer = "")
    {
        $data = [
            'orderno' => $orderno,
            'uid' => $uid,
            'action' => 2,
            'i' => $i,
            'number' => $number,
            'msg' => $msg,
            'textmsg' => $textmsg,
            'created' => time(),
            'adminer' => $adminer,
        ];
        return OrangeLogBalance::insert($data);
    }

    /**
     * 彩金
     * @param int $uid 用户ID
     * @param int|float $number 数额
     * @param string $msg 备注
     * @param string|int $adminer 管理员
     * @return mixed
     */
    public function caijin($uid, $number, $msg, $adminer = "")
    {
        $data = [
            'uid' => $uid,
            'action' => 3,
            'i' => 1,
            'number' => $number,
            'orderno' => $msg,
            'created' => time(),
            'adminer' => $adminer,
        ];
        return OrangeLogBalance::insert($data);
    }

    /**
     * 扣款
     * @param int $uid 用户ID
     * @param int|float $number 数额
     * @param string $msg 备注
     * @param string $textmsg 备注(长文本)
     * @param string|int $adminer 管理员
     * @return mixed
     */
    public function koukuan($uid, $number, $msg, $adminer = "")
    {
        $data = [
            'uid' => $uid,
            'action' => 4,
            'i' => 2,
            'number' => $number,
            'orderno' => $msg,
            'created' => time(),
            'adminer' => $adminer,
        ];
        return OrangeLogBalance::insert($data);
    }

    /**
     * 佣金
     * @param string $orderno OrderNo
     * @param int $uid 用户ID
     * @param int|float ￥number 数额
     * @return mixed
     */
    public function yongjin($orderno, $uid, $number, $action = 5)
    {
        $data = [
            'uid' => $uid,
            'action' => $action,
            'i' => 1,
            'number' => $number,
            'orderno' => $orderno,
            'created' => time(),
        ];
        return OrangeLogBalance::insert($data);
    }

    /**
     * 订单
     * @param string $orderno OrderNo
     * @param int $uid 用户ID
     * @param int|float $number 数额
     * @return mixed
     */
    public function order($orderno, $uid, $i, $number)
    {
        $data = [
            'uid' => $uid,
            'action' => 6,
            'i' => $i,
            'number' => $number,
            'orderno' => $orderno,
            'created' => time(),
        ];
        return OrangeLogBalance::insert($data);
    }
}
