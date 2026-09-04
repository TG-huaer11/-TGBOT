<?php

namespace app\common\model;

/**
 * 用户等级模型
 */
class UserLevelLog extends BaseModel
{
    // 表名
    protected $name = 'user_level_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
}
