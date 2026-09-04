<?php

namespace app\common\service;

use app\common\model\LogAssign;

/**
 * 指派日志服务
 * Class ConveyAssignService
 * @package app\admin\service
 */
class LogAssignService extends BaseService
{

    /**
     * 创建日志
     */
    public function record($userId, $goodsId, $goodsPrice, $num, $total, $commission)
    {
        return LogAssign::create([
            'uid'         => $userId,
            'goods_id'    => $goodsId,
            'num'         => $num,
            'goods_price' => $goodsPrice,
            'total'       => $total,
            'commission'  => $commission,
        ]);
    }

}