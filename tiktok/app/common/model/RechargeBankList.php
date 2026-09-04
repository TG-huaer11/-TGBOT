<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

/**
 * 会员充值模型.
 */
class RechargeBankList extends BaseModel
{
    // 使用软删除
    use SoftDelete;
    // 表名
    protected $name = 'recharge_banklist';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'end_at';
    // 定义软删除字段名
    protected $deleteTime = 'del';
    // 软删除默认值
    protected $defaultSoftDelete = 0;
    // 追加属性
    protected $append = [
    ];

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
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

    public function pay()
    {
        return $this->belongsTo('Pay', 'pay_name', 'name2', [], 'LEFT')->joinType(0);
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'adminer', 'id', [], 'LEFT')->joinType(0);
    }
}
