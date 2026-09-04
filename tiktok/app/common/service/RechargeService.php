<?php

namespace app\common\service;

use app\common\model\Pay;
use app\common\model\Recharge;
use app\common\model\RechargeBankList;
use app\common\model\User;

class RechargeService extends BaseService
{

    public function getPageList($uid, $page, $pageSize = 10, $field = '*', $order = 'id desc')
    {
        $list = Recharge::where(['uid' => $uid])
            ->field($field)
            ->page($page, $pageSize)
            ->order($order)
            ->select()
            ->toArray();

        if ($list) {
            foreach ($list as &$item) {
                $item ['create_time'] = date('Y-m-d H:i:s', $item ['create_at']);
            }
        }

        return $list;
    }

    public function recharge($uid)
    {
        $tel = User::where('id', $uid)->value('tel');

        $tel = substr_replace($tel, '****', 3, 4);

        $pay = Pay::where('status', 1)->order('sort desc')->select();
        $paybank = RechargeBankList::where('del', 0)->where('status', 1)->order('sort desc')->select();

        return ['tel' => $tel, 'pay' => $pay, 'paybank' => $paybank,];
    }

    public function recharge_do_before($uid, $num, $type, $pay_type, $pay_image = '')
    {

        $order_sn = 'R' . date('YmdHi') . mt_rand(1000, 9999);
        $insert = [
            'id' => $order_sn,
            'uid' => $uid,
            'num' => $num,
            'real_num' => 0,
            'create_at' => time(),
            'type' => $pay_type,
            'pic' => $pay_image,
        ];

        if ($pay_type == 1) {
            $pay = Pay::name('pay')->where('name2', $type)->find();

            if (!$pay) {
                throw new \Exception('Error');
            }

            if ($num < $pay['min']) {
                throw new \Exception(lang('Recharge_min') . ' ' . $pay['min']);
            }

            if ($num > $pay['max']) {
                throw new \Exception(lang('Recharge_max') . ' ' . $pay['max']);
            }


            $class = '\app\pay\\' . $type;
            $payClass = new $class();

            $result = $payClass->recharge($pay, $num, $order_sn);

            $insert ['pay_name'] = $type;
            $insert ['fee'] = $pay['fee'];
            $insert ['gfee'] = $pay['gfee'];

            if ($result ['code']) {
                $insert ['status'] = 1;
            } else {
                $insert ['status'] = 3;
            }
        } else {
            if (!$pay_image) {
              //  throw new \Exception(lang('Recharge_Upload'));
            }
            $insert ['status'] = 1;
            $result = ['code' => 1, 'msg' => 'success'];
        }

        Recharge::insert($insert);
        return $result;
    }
}

