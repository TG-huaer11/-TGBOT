<?php

namespace app\common\service;

use app\common\model\BankInfo;
use app\common\model\BankList;
use app\common\model\Deposit;
use app\common\model\User;
use Exception;

class BankInfoService extends BaseService
{

    /**
     * 获取银行卡模板
     * @param $uid
     * @return string
     */
    public function getBankInfoTemplate($uid)
    {
        $bankInfo = BankInfo::where(['uid' => $uid])->find();
        $template = '';
        if (isset($bankInfo['cardnum']) && $bankInfo['cardnum'] != '') {
            $template = 'bind_bank_card';
        }

        return $template;
    }

    public function getBankInfo($uid)
    {
        $bankInfo = BankInfo::where(['uid' => $uid])->find();
        $bank = BankList::select();
        $del = 0;

        if (isset($bankInfo['cardnum']) && $bankInfo['cardnum'] != '') {
            $success = Deposit::where(['status' => 2, 'uid' => $uid])->find();
            $wait = Deposit::where(['status' => 4, 'uid' => $uid])->find();

            if ($success || $wait) {
                $del = 0;
            } else {
                $del = 1;
            }

            $card_array = str_split($bankInfo['cardnum']);
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
            $bankInfo['card'] = $card_str;
        }

        return ['info' => $bankInfo, 'del' => $del, 'banks' => $bank];
    }

    /**
     * 删除银行卡
     * @param $uid
     * @return int
     */
    public function delBank($uid)
    {
        $shenqing = Deposit::where(['status' => 1, 'uid' => $uid])->find();
        $success = Deposit::where(['status' => 2, 'uid' => $uid])->find();
        $wait = Deposit::where(['status' => 4, 'uid' => $uid])->find();

        if (!$shenqing && !$success && !$wait) {
            $res = BankInfo::where(['uid' => $uid])->delete();
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
    public function bindBank($params)
    {
        $info = BankInfo::where('uid', $params ['uid'])->find();
        $userInfo = User::where('id', $params ['uid'])->find();

        $bankInfo = BankInfo::where(['cardnum' => $params ['cardnum']])->find();
        if ($bankInfo && $bankInfo['uid'] != $params ['uid']) {
            //该姓名和银行卡已绑定其他账号!
            throw new Exception('The name and bank card were bound to other accounts!');
        }

        $data = array(
            'username' => $params ['username'],
            'bankname' => $params ['bankname'],
            'cardnum' => $params ['cardnum'],
            'site' => $params ['site'],
            'address' => $params ['address'],
            'ifsc' => $params ['ifsc'],
            'tel' => $userInfo['tel'],
            'status' => 1,
            'uid' => $params ['uid']
        );

        if ($info) {
            $res = BankInfo::update(['uid' => $params ['uid']], $data);
        } else {
            $res = BankInfo::insert($data);
        }

        return $res;
    }

}