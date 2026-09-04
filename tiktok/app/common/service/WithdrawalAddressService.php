<?php

namespace app\common\service;

use app\common\model\WithdrawalAddress;
use app\common\model\Deposit;
use Exception;

class WithdrawalAddressService extends BaseService
{

    /**
     * 获取银行卡模板
     * @param $uid
     * @return string
     */
    public function getWithdrawalAddressTemplate($uid)
    {
        $address = WithdrawalAddress::where(['user_id' => $uid])->find();
        $template = 'bind_withdrawal_address';
        if (isset($address['address']) && $address['address'] != '') {
            $template = 'withdrawal_address';
        }

        return $template;
    }

    public function getWithdrawalAddress($uid)
    {
        $address = WithdrawalAddress::where(['user_id' => $uid])->find();
        $del = 0;

        if (isset($address['address']) && $address['address'] != '') {
            $success = Deposit::where(['status' => 2, 'uid' => $uid])->find();
            $wait = Deposit::where(['status' => 4, 'uid' => $uid])->find();

            if ($success || $wait) {
                $del = 0;
            } else {
                $del = 1;
            }

            $card_array = str_split($address['address']);
            $n = count($card_array);
            $m = 0;
            $card_str = "";
            for ($i = 0; $i < $n; $i++) {
                if ($m == 4) {
                    $card_str .= " ";
                    $m = 0;
                }
                $card_str .= $card_array[$i];
                $m++;
            }
            $address['card'] = $card_str;
        }

        return ['info' => $address, 'del' => $del];
    }

    /**
     * 删除银行卡
     * @param $uid
     * @return int
     */
    public function delWithdrawalAddress($uid)
    {
        $shenqing = Deposit::where(['status' => 1, 'uid' => $uid])->find();
        $success = Deposit::where(['status' => 2, 'uid' => $uid])->find();
        $wait = Deposit::where(['status' => 4, 'uid' => $uid])->find();

        if (!$shenqing && !$success && !$wait) {
            $res = WithdrawalAddress::where(['user_id' => $uid])->delete();
            if ($res) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * 绑定银行卡
     * @param $params
     * @return int|string|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bindWithdrawalAddress($params)
    {
        $info = WithdrawalAddress::where('user_id', $params ['uid'])->find();

//         $address = WithdrawalAddress::where(['address' => $params ['address']])->find();
//        if ($address && $address['user_id'] != $params ['uid']) {
//            //该姓名和银行卡已绑定其他账号!
//            throw new Exception(lang('Set_Bind_Address_Unique'));
//        }

        $data = array(
            'user_id' => $params ['uid'],
            'address' => $params ['address'],
        );
        if (isset($params ['name']) && !empty($params ['name'])) {
            $data ['name'] = $params ['name'];
        }

        if ($info) {
            $res = WithdrawalAddress::update($data, ['user_id' => $params ['uid']]);
        } else {
            $res = WithdrawalAddress::insert($data);
        }

        return $res;
    }

}