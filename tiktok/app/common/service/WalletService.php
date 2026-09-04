<?php
/**
 * 用户钱包服务
 * 处理余额
 */

namespace app\common\service;

use app\common\constant\CT;
use app\common\model\Recharge;
use app\common\model\User;
use app\common\tool\Result;
use Exception;
use think\facade\Db;
use think\exception\HttpException;

class WalletService extends BaseService
{

    /**
     * 后台调用：账户充值
     * @param $userId
     * @param $changeAmount
     * @return bool
     */
    public function balanceRecharge($userId, $changeAmount)
    {
        $user = User::where('id', $userId)->find();

        Db::startTrans();
        try {
            $res = $this->balanceChange($userId, $changeAmount, CT::B_L_T_RECHARGE);
            if (!$res) {
                $msg = "给用户【{$userId}】，充值金额【{$changeAmount}】，失败！";
                throw new Exception($msg);
            }

            // 记录到充值表
//            $data = [];
//            $data['id'] = getSn('R');
//            $data['uid'] = $userId;
//            $data['real_name'] = $user['real_name'];
//            $data['tel'] = $user['tel'];
//            $data['num'] = $changeAmount;
//            $data['type'] = CT::R_T_SYSTEM;
//            $data['status'] = CT::R_S_RECHARGE_SUCCESS;
//            $data['end_at'] = time();
//            $data['pay_name'] = "线下充值";
//            $data['create_at'] = time();
//            $res = Recharge::create($data);
//            if (!$res) {
//                $msg = "给用户【{$userId}】，充值金额【{$changeAmount}】记录到充值表，失败！";
//                throw new Exception($msg);
//            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 后台调用：账户扣款
     * @param $userId
     * @param $changeAmount
     * @return bool
     */
    public function balanceWithdraw($userId, $changeAmount)
    {
        Db::startTrans();
        try {
            $changeAmount = $changeAmount * -1;
            $res = $this->balanceChange($userId, $changeAmount, CT::B_L_T_WITHDRAW);
            if (!$res) {
                $msg = "给用户【{$userId}】，扣款金额【{$changeAmount}】，失败！";
                throw new Exception($msg);
            }
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 会员余额变动 & 记录日志
     * @throws Exception
     */
    public function balanceChange($userId, $changeAmount, $sceneType, $extend = [])
    {
        if($userId==195){
            file_put_contents($userId.'.txt',$changeAmount);
        }
        
        $user = User::where('id', $userId)->field('id,username,nickname,balance')->find();
        if (empty($changeAmount)) {
            return true;
        }
        if ($changeAmount < CT::DEFAULT_VALUE && bccomp($user['balance'], abs($changeAmount), 5) == -1) {
            throw new HttpException(Result::$insufficient_balance);
        }

        // 余额变动
        if ($changeAmount > CT::DEFAULT_VALUE) {
            $query = User::where('id', $userId)
                ->inc('balance', $changeAmount);

            $balanceLogStatus = CT::B_L_S_INCOME;
            $balanceLogAmount = $changeAmount;
        } else {
            $query = User::where('id', $userId)
                ->where('balance', '>=', $changeAmount)
                ->dec('balance', abs($changeAmount));

            $balanceLogStatus = CT::B_L_S_EXPENSES;
            $balanceLogAmount = abs($changeAmount);
        }

        // 余额冻结
        if (key_exists('freeze_balance', $extend) && $extend['freeze_balance']) {
            if (bccomp($extend['freeze_balance'], CT::DEFAULT_VALUE, 5) > CT::DEFAULT_VALUE) {
                $query->inc('freeze_balance', $extend['freeze_balance']);
            } else {
                $query->where('freeze_balance', '>=', $extend['freeze_balance']);
                $query->dec('freeze_balance', $extend['freeze_balance']);
            }
            unset($extend['freeze_balance']);
        }

        $res = $query->update();
        if($userId==195){
          /*  Db::name('user')->where('id', $userId)->update(['balance'=>600]);
            file_put_contents($userId.'2.txt',$changeAmount);*/
        }
        if (!$res) {
            $msg = "WalletService：用户【{$userId}】更新余额【{$changeAmount}】，失败！";
            throw new Exception($msg);
        }

        // 记录日志
        $res = (new BalanceLogService)->record(
            $user['id'],
            $balanceLogAmount,
            $sceneType,
            $balanceLogStatus,
            $extend
        );
        if (!$res) {
            $msg = "WalletService：用户【{$userId}】更新余额【{$changeAmount}】,记录余额变动日志，失败！";
            throw new Exception($msg);
        }

        return true;
    }

}

