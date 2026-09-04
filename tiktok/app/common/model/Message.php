<?php

namespace app\common\model;

class Message extends BaseModel
{

    // 表名
    protected $name = 'message';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
    ];

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id', [], 'LEFT')->joinType(0);
    }
}
