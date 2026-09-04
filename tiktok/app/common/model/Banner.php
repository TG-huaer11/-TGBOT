<?php

namespace app\common\model;

/**
 * 轮播图模型.
 */
class Banner extends BaseModel
{

    //数据表名称
    protected $name = 'banner';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
}
