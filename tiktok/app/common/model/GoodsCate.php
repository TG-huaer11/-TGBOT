<?php

namespace app\common\model;

/**
 * 商品分类模型
 */
class GoodsCate extends BaseModel
{
    // 表名
    protected $name = 'goods_cate';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_at';
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

}
