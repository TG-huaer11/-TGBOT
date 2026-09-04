<?php

namespace app\common\model;

/**
 * 充值配置模型
 */
class RechargeConfig extends BaseModel
{
    // 表名
    protected $name = 'recharge_config';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 定义软删除字段名

    // 追加属性
    protected $append = [
    ];

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

}
