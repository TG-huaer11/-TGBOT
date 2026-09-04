<?php

namespace app\common\model;

use app\common\model\MoneyLog;
use app\common\model\ScoreLog;

class User extends BaseModel
{

    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
    ];

    protected static function onBeforeUpdate($row)
    {
        $changed = $row->getChangedData();
        //如果有修改密码
        if (isset($changed['pwd'])) {
            if ($changed['pwd']) {
                $salt = rand(0, 99999);            //生成盐
                $pwd_str = config('app.pwd_str');
                $row->password = sha1($changed['pwd'] . $salt . $pwd_str);
                $row->salt = $salt;
            } else {
                unset($row->password);
            }
        }
    }

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

    public function group()
    {
        return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->joinType(0);
    }

    public function bank()
    {
        return $this->belongsTo('BankInfo', 'id', 'uid', [], 'LEFT')->joinType(0);
    }
}
