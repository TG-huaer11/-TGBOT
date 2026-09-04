<?php

namespace app\common\service;

use app\common\constant\CT;
use app\common\model\LogBalance;

class BalanceLogService extends BaseService
{

    /**
     * 记录描述
     * @var string
     */
    private static $exp = '';

    /**
     * @return string
     */
    public static function getExp(): string
    {
        return self::$exp;
    }

    /**
     * @param string $exp
     */
    public static function setExp(string $exp): void
    {
        self::$exp = $exp;
    }

    /**
     * 记录余额变动记录
     */
    public function record($userId, $amount, $sceneType, $status, $extend)
    {
        return LogBalance::create(array_merge([
            'uid' => $userId,
            'num' => $amount,
            'type' => $sceneType,
            'status' => $status,
        ], $extend));
    }

    /**
     * 获取用户充值记录
     * @param $userId
     * @param $page
     * @param $limit
     * @return array
     */
    public function getRechargeRecord($userId, $page, $limit): array
    {
        $subSql = LogBalance::where(['uid' => $userId])
            ->where('num', '>', CT::DEFAULT_VALUE)
            ->field('id')
            ->page($page, $limit)
            ->order('id desc')
            ->buildSql();

        $field = [
            'b_l.id', 'b_l.uid', 'b_l.sid', 'b_l.oid', 'b_l.num', 'b_l.type', 'b_l.status',
            'b_l.create_at',
        ];

        return LogBalance::alias('b_l')
            ->join([$subSql => 'sub'], 'b_l.id = sub.id')
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 获取 日志场景 对应 描述
     * @param $sceneType
     * @return string
     */
    public static function getSceneTypeExp($sceneType): string
    {
        $expArr = [
            CT::B_L_T_SYSTEM => "系统",
            CT::B_L_T_RECHARGE => "充值",
            CT::B_L_T_TRADE => "交易",
            CT::B_L_T_REBATE => "返佣",
            CT::B_L_T_ENFORCE_TRADE => "强制交易",
            CT::B_L_T_REBATE_PROMOTE => "推广返佣",
            CT::B_L_T_REBATE_SUB => "下级交易返佣",
            CT::B_L_T_WITHDRAW => "提现",
        ];
        return $expArr[$sceneType];
    }
}

