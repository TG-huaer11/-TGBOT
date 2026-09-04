<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

class Kd extends BaseModel
{

    use SoftDelete;

    // 表名
    protected $name = 'kd';
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
        return ['0' => '不启用', '1' => '启用'];
    }
     public function getType()
    {
        return ['1' => '真人卡单', '2' => '假人卡单'];
    }

 public function getdays()
    {
        return ['1' => '第一天', '2' => '第二天', '3' => '第三天', '4' => '第四天', '5' => '第五天', '6' => '第六天', '7' => '第七天'
        , '8' => '第八天', '9' => '第九天', '10' => '第十天'];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

}
