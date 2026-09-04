<?php

namespace app\common\model;

class AdminTotp extends BaseModel
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = false;

}
