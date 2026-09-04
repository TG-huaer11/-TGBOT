<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

/**
 * 订单模型
 */
class Order extends BaseModel
{
    // 使用软删除
    use SoftDelete;
    // 表名
    protected $name = 'order';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = false;
    // 定义软删除字段名
    protected $deleteTime = 'is_deleted';

    // 软删除默认值
    protected $defaultSoftDelete = 0;
    // 追加属性
    protected $append = [
    ];

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id', [], 'LEFT')->joinType(0);
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'operator', 'id', [], 'LEFT')->joinType(0);
    }

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id', [], 'LEFT')->joinType(0);
    }

}
