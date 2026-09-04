<?php

namespace app\common\model;

/**
 * 会员充值模型.
 */
class Recharge extends BaseModel
{
    // 表名
    protected $name = 'recharge';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'end_at';
    // 追加属性
    protected $append = [
    ];

    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id', [], 'LEFT')->joinType(0);
    }

    public function pay()
    {
        return $this->belongsTo('Pay', 'pay_name', 'name2', [], 'LEFT')->joinType(0);
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'adminer', 'id', [], 'LEFT')->joinType(0);
    }
}
