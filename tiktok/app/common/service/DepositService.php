<?php

namespace app\common\service;

use app\common\constant\CT;
use app\common\model\BankInfo;
use app\common\model\Config;
use app\common\model\Deposit;
use app\common\model\LogBalance;
use app\common\model\Order;
use app\common\model\OrderModel;
use app\common\model\OrderModelList;
use app\common\model\Recharge;
use app\common\model\User;
use app\common\model\UserLevel;
use app\common\model\WithdrawalAddress;
use app\common\service\UserService;
use think\facade\Db;

class DepositService extends BaseService
{

    public function getPageList($uid, $page, $pageSize = 10, $field = '*', $order = 'id desc')
    {
        $list = Deposit::where(['uid' => $uid])
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

    public function deposit($uid)
    {
        $user = (new UserService)->getUserInfo($uid, 'id,tel,username,balance,invite_code,level,headpic');
        $user ['tel'] = substr_replace($user['tel'], '****', 3, 4);

        $addressInfo = (new WithdrawalAddressService())->getWithdrawalAddress($uid);
        $address = $addressInfo ['info'];
        if ($address) {
            $address['address'] = substr_replace($address['address'], '****', 7, 7);
        } else {
            $address['address'] = '****';
        }

        // 提现时间限制，开始+时长>现在时间
        if (($user['withdraw_time'] + $user['withdraw_long']) > time()) {
            $status = [
                'code' => 1, // 限制
                'time' => bcdiv($user['withdraw_long'], 60, 1),
                't' => bcdiv(($user['withdraw_time'] + $user['withdraw_long'] - time()), 60, 1),
                'start' => $user['withdraw_time'], // 开始时间
                'end' => $user['withdraw_time'] + $user['withdraw_long'], // 限制时间
            ];
        } else {
            $status = [
                'code' => 0, // 正常提现
            ];
        }

        $chongzhi = Recharge::where(['uid' => $uid, 'status' => 2, 'type' => 1])->sum('real_num');

        $caijin = Recharge::where(['uid' => $uid, 'type' => 2])->sum('num');

        $limit = bcadd($chongzhi, $caijin, 2);

        $shop = Order::where(['uid' => $uid, 'status' => 1, 'c_status' => 1])->sum('total');

        //提现限制
        $level = $user['level'];
        $user['level'] = !$user['level'] ? $level = 0 : '';
        $ulevel = UserLevel::where('level', $level)->find();

        return [
            'shouxu' => $ulevel['tixian_shouxu'],
            'limit' => $limit,
            'shop' => $shop,
            'status' => $status,
            'address' => $address,
            'user' => $user,
        ];
    }

    public function depositCopy($uid)
    {
        $user = (new UserService)->getUserInfo($uid, 'id,tel,username,balance,invite_code,level,headpic');
        $user ['tel'] = substr_replace($user['tel'], '****', 3, 4);

        $bankInfo = (new BankInfoService())->getBankInfo($uid);
        $bank = $bankInfo ['info'];

        if ($bank) {
            $bank['cardnum'] = substr_replace($bank['cardnum'], '****', 7, 7);
        } else {
            $bank['cardnum'] = '****';
        }

        // 提现时间限制，开始+时长>现在时间
        if (($user['withdraw_time'] + $user['withdraw_long']) > time()) {
            $status = [
                'code' => 1, // 限制
                'time' => bcdiv($user['withdraw_long'], 60, 1),
                't' => bcdiv(($user['withdraw_time'] + $user['withdraw_long'] - time()), 60, 1),
                'start' => $user['withdraw_time'], // 开始时间
                'end' => $user['withdraw_time'] + $user['withdraw_long'], // 限制时间
            ];
        } else {
            $status = [
                'code' => 0, // 正常提现
            ];
        }

        $chongzhi = Recharge::where(['uid' => $uid, 'status' => 2, 'type' => 1])->sum('real_num');

        $caijin = Recharge::where(['uid' => $uid, 'type' => 2])->sum('num');

        $limit = bcadd($chongzhi, $caijin, 2);

        $shop = Order::where(['uid' => $uid, 'status' => 1, 'c_status' => 1])->sum('total');

        //提现限制
        $level = $user['level'];
        $user['level'] = !$user['level'] ? $level = 0 : '';
        $ulevel = UserLevel::where('level', $level)->find();

        return [
            'shouxu' => $ulevel['tixian_shouxu'],
            'limit' => $limit,
            'shop' => $shop,
            'status' => $status,
            'info' => $bank,
            'user' => $user,
        ];
    }

    public function do_deposit_address($uid, $pwd2, $num, $address_id)
    {

        $user = (new UserService)->getUserInfo($uid, 'is_deposit,order_number,order_model,pwd2,salt2,recharge_num,deal_time,balance,level,addliushui,today_order_number');

        if (!$user['is_deposit']) {
            throw new \Exception('Please contact customer service');
        }

        $addressInfo = WithdrawalAddress::where(['user_id' => $uid])->find();

        if (!$addressInfo) {
            throw new \Exception('Bank card information has not been added!');
        }

        $withdraw_limit = Config::where('name', 'withdraw_limit')->value('value');
        if ($withdraw_limit) {
            //存在未完成訂單不能體現
            $assign = Order::where(['uid' => $uid, 'status' => CT::O_S_PENDING_PAYMENT])->find();
            if ($assign) {
                throw new \Exception(__('Withdraw_has_pending_pay'));
            }

            $day_order_num = Config::where('name', 'day_order_num')->value('value');
            if ($user->today_order_number < $day_order_num) {
                throw new \Exception(__('Withdraw_has_pending_pay'));
            }

        }

        //后台开启抢单模板
//        $order_model = Config::where('name', 'order_model')->value('value');
//        if ($order_model) {
//            //用户不存在清单模板
//            if(!$user['order_model']) {
//                throw new \Exception(__('Withdraw_no_deposit'));
//            }
//
//            //存在未完成訂單不能體現
//            $assign = Order::where(['uid' => $uid, 'status' => CT::O_S_PENDING_PAYMENT])->find();
//            if ($assign) {
//                throw new \Exception(__('Withdraw_no_deposit'));
//            }
//
//            $order_model_list = OrderModel::field('id')->where('parent_id', $user['order_model'])->select();
//
//            //须完成订单模型组所有订单
//            if ($user['order_number'] < count($order_model_list)) {
//                throw new \Exception(__('Withdraw_no_deposit'));
//            }
//        }


        if (!$address_id) {
            $address_id = $addressInfo ['id'];
        }
        if ($user['pwd2'] == '') {
            throw new \Exception('Transaction password not set');
        }
        if ($user['pwd2'] != sha1($pwd2 . $user['salt2'] . config('app.pwd_str'))) {
            throw new \Exception('Password error');
        }

        if ($num <= 0) {
            throw new \Exception('Error');
        }

        //提现限制
//
//        $chongzhi = Recharge::where(['uid' => $uid, 'status' => 2, 'type' => 1])->sum( 'real_num');
//
//        $caijin = Recharge::where(['uid' => $uid, 'type' => 2])->sum('num');
//
//        $limit = bcadd($chongzhi, $caijin, 2);
//
//        $shop = Order::where(['uid' => $uid, 'status' => 1, 'c_status' => 1])->sum('total');
//
//
//        $liushui = $shop + floatval($user['addliushui']);
//
//        if (bccomp($limit, $liushui, 2) == 1) {
//            throw new \Exception('The total order amount must be greater than the total recharge amount');
//        }

        $level = $user['level'];
        !$user['level'] ? $level = 0 : '';

        $ulevel = UserLevel::where('level', $level)->find();

        if ($num < $ulevel['tixian_min']) {
            throw new \Exception('Less than the minimum withdrawal amount!');
        }

        if ($num >= $ulevel['tixian_max']) {
            throw new \Exception('Greater than the maximum withdrawal amount!');
        }
        if ($num > $user['balance']) {
            throw new \Exception(__('No_Balance'));
        }

        $id = getSn('CO');
        Db::startTrans();
        try {

            $res = Deposit::insert([
                'id' => $id,
                'uid' => $uid,
                'address_id' => $address_id,
                'num' => $num,
                'create_at' => time(),
                'shouxu' => $ulevel['tixian_shouxu'],
                'real_num' => $num - ($num * $ulevel['tixian_shouxu']),
                'address_info' => json_encode($addressInfo),
            ]);

            //提现日志
            $res2 = LogBalance::name('log_balance')
                ->insert([
                    'uid' => $uid,
                    'oid' => $id,
                    'num' => $num,
                    'type' => 7, //TODO 7提现
                    'status' => 2,
                    'create_at' => time(),
                ]);

            (new CnmService())->despoit($id, $uid, 2, $num, 'User');

            $res1 = User::where('id', $uid)->dec('balance', $num)->update();
            if ($res && $res1) {
                Db::commit();
                return true;
            } else {
                Db::rollback();
                throw new \Exception('Please check the account balance!');
            }
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function do_deposit_bank($uid, $pwd2, $num, $bkid)
    {

        $user = (new UserService)->getUserInfo($uid, 'is_deposit,order_number,order_model,pwd2,salt2,recharge_num,deal_time,balance,level,addliushui,today_order_number');

        if (!$user ['is_deposit']) {
            throw new \Exception('Please contact customer service');
        }

        $bankInfo = BankInfo::where(['uid' => $uid, 'status' => 1])->find();

        if (!$bankInfo) {
            throw new \Exception('Bank card information has not been added!');
        }

        $withdraw_limit = Config::where('name', 'withdraw_limit')->value('value');
        if ($withdraw_limit) {
            //存在未完成訂單不能體現
            $assign = Order::where(['uid' => $uid, 'status' => CT::O_S_PENDING_PAYMENT])->find();
            if ($assign) {
                throw new \Exception(__('Withdraw_has_pending_pay'));
            }

            $day_order_num = Config::where('name', 'day_order_num')->value('value');
            if ($user->today_order_number < $day_order_num) {
                throw new \Exception(__('Withdraw_has_pending_pay'));
            }

        }

        //后台开启抢单模板
//        $order_model = Config::where('name', 'order_model')->value('value');
//        if ($order_model) {
//            //用户不存在清单模板
//            if (!$user['order_model']) {
//                throw new \Exception(__('Withdraw_no_deposit'));
//            }
//
//            //存在未完成訂單不能體現
//            $assign = Order::where(['uid' => $uid, 'status' => CT::O_S_PENDING_PAYMENT])->find();
//            if ($assign) {
//                throw new \Exception(__('Withdraw_no_deposit'));
//            }
//
//            $order_model_list = OrderModel::field('id')->where('parent_id', $user['order_model'])->select();
//
//            //须完成订单模型组所有订单
//            if ($user['order_number'] < count($order_model_list)) {
//                throw new \Exception(__('Withdraw_no_deposit'));
//            }
//        }


        if (!$bkid) {
            $bkid = $bankInfo ['id'];
        }
        if ($user['pwd2'] == '') {
            throw new \Exception('Transaction password not set');
        }
        if ($user['pwd2'] != sha1($pwd2 . $user['salt2'] . config('app.pwd_str'))) {
            throw new \Exception('Password error');
        }

        if ($num <= 0) {
            throw new \Exception('Error');
        }

        //提现限制

        $chongzhi = Recharge::where(['uid' => $uid, 'status' => 2, 'type' => 1])->sum('real_num');

        $caijin = Recharge::where(['uid' => $uid, 'type' => 2])->sum('num');

        $limit = bcadd($chongzhi, $caijin, 2);

        $shop = Order::where(['uid' => $uid, 'status' => 1, 'c_status' => 1])->sum('total');


        $liushui = $shop + floatval($user['addliushui']);

        if (bccomp($limit, $liushui, 2) == 1) {
            throw new \Exception('The total order amount must be greater than the total recharge amount');
        }

        $level = $user['level'];
        !$user['level'] ? $level = 0 : '';

        $ulevel = UserLevel::where('level', $level)->find();

        if ($num < $ulevel['tixian_min']) {
            throw new \Exception('Less than the minimum withdrawal amount!');
        }

        if ($num >= $ulevel['tixian_max']) {
            throw new \Exception('Greater than the maximum withdrawal amount!');
        }

        if ($num > $user['balance']) {
            throw new \Exception('Sorry, your credit is running low');
        }

        $id = getSn('CO');
        Db::startTrans();
        try {

            $res = Deposit::insert([
                'id' => $id,
                'uid' => $uid,
                'bk_id' => $bkid,
                'num' => $num,
                'create_at' => time(),
                'shouxu' => $ulevel['tixian_shouxu'],
                'real_num' => $num - ($num * $ulevel['tixian_shouxu']),
                'bankinfo' => json_encode($bankInfo),
            ]);

            //提现日志
            $res2 = LogBalance::name('log_balance')
                ->insert([
                    'uid' => $uid,
                    'oid' => $id,
                    'num' => $num,
                    'type' => 7, //TODO 7提现
                    'status' => 2,
                    'create_at' => time(),
                ]);

            (new CnmService())->despoit($id, $uid, 2, $num, 'User');

            $res1 = User::where('id', $uid)->dec('balance', $num)->update();
            if ($res && $res1) {
                Db::commit();
                return true;
            } else {
                Db::rollback();
                throw new \Exception('Please check the account balance!');
            }
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }
}

