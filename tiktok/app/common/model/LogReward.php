<?php

namespace app\common\model;


class LogReward extends BaseModel
{
    //数据表名称
    protected $name = 'log_reward';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];

}
