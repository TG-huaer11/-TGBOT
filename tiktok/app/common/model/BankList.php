<?php

namespace app\common\model;

/**
 * 会员银行卡信息模型.
 */
class BankList extends BaseModel
{
    // 表名
    protected $name = 'bank_list';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
}
