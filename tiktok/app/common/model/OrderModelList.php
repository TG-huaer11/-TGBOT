<?php

namespace app\common\model;

/**
 * 抢单模板模型
 */
class OrderModelList extends BaseModel
{
    // 表名
    protected $name = 'order_model_list';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
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

    public function ordermodel()
    {
        return $this->belongsTo('OrderModel', 'model_id', 'id', [], 'LEFT')->joinType(0);
    }


}
