<?php

namespace app\common\service;

use app\common\model\LogBalance;

class LogBalanceService extends BaseService
{

    public function getPageList($uid, $type, $page, $pageSize = 10, $field = '*', $order = 'id desc')
    {
        $where ['uid'] = $uid;
        if ($type == 1) {
            $where['type'] = 7;
        } elseif ($type == 2) {
            $where['type'] = 1;
        }

        $list = LogBalance::where($where)
            ->field($field)
            ->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        if (!empty($list)) {
            foreach ($list as &$item) {
                $item ['create_time'] = format_datetime($item ['create_at'], 'Y-m-d H:i:s');
            }
        }

        return $list;
    }
}