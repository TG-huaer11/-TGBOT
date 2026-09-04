<?php

namespace app\common\model;

/**
 * 用户等级模型
 */
class UserLevel extends BaseModel
{
    // 表名
    protected $name = 'user_level';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
}
