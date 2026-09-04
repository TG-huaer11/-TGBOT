<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

class Address extends BaseModel
{

    use SoftDelete;

    // 表名
    protected $name = 'address';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = false;
    // 追加属性
    protected $append = [
        'status_text'
    ];

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }
     public function getStatusList2()
    {
        return ['0' => '假人地址', '1' => '真人地址'];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

}
