<?php

namespace app\common\model;

/**
 * 会员取款模型.
 */
class Deposit extends BaseModel
{
    // 表名
    protected $name = 'deposit';
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

    public function bank()
    {
        return $this->belongsTo('BankInfo', 'bk_id', 'id', [], 'LEFT')->joinType(0);
    }

    public function pay()
    {
        return $this->belongsTo('PayOut', 'type', 'name2', [], 'LEFT')->joinType(0);
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'adminer', 'id', [], 'LEFT')->joinType(0);
    }
}
