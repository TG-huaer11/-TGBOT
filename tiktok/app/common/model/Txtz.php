<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

class Txtz extends BaseModel
{

    use SoftDelete;

    // 表名
    protected $name = 'txtz';
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

 public function lang()
    {
        return ["zh" => '中文', "en" =>'英语', "de" =>'德语', "fr" =>'法语', "it" => '意大利语', "pt" =>'葡萄牙', "ru" =>'俄语', "spa" => '加泰罗尼亚', "tr" =>'土耳其', "ar" => '阿拉伯'];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

}
