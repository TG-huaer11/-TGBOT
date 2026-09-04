<?php
/**
 * *
 *  * ============================================================================
 *  * Created by PhpStorm.
 *  * User: Ice
 *  * 邮箱: ice@sbing.vip
 *  * 网址: https://sbing.vip
 *  * Date: 2019/9/19 下午3:33
 *  * ============================================================================.
 */

namespace app\admin\controller;

use app\common\model\User;
use app\common\model\Recharge;
use app\common\model\Deposit;
use app\common\model\Config;
use app\common\controller\Backend;
use think\facade\Db;

/**
 * 控制台.
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{
    protected $noNeedRight = ['register', 'recharge', 'withdraw', 'profit'];

    /**
     * 查看
     */
    public function index()
    {
        //昨天
        $yes1 = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $yes2 = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));

        //上月
        $monyes1 = strtotime(date('Y-m-d 00:00:00', strtotime('first day of -1 month')));
        $monyes2 = strtotime(date('Y-m-d 23:59:59', strtotime('last day of -1 month')));

        if (!$this->isSuperAdmin) {
            $db = User::where('top_parent', $this->auth->user_id)->select();
            $xiaji = [];
            foreach ($db as $v) {
                $xiaji[] = $v['id'];
            }
            $xiaji_where['uid'] = ['in', $xiaji];
//            $parent_where['top_parent'] = $this->auth->user_id;
        } else {
            $xiaji_where = [];
            $parent_where = [];
        }
        $childWhere = [];
        if(!$this->isSuperAdmin) {
            // 查询userid
            $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
            $childWhere[] = ['uid', 'IN', $user_id];
        }
        //用户
        $today_users_num = User::where('create_at', 'between', [strtotime(date('Y-m-d')), time()])->where('top_parent', $this->auth->user_id)->count('id');
        $yes_users_num = User::where('create_at', 'between', [$yes1, $yes2])->where('top_parent', $this->auth->user_id)->count('id');

        //充值
        $today_user_recharges = Recharge::where('status', 2)->where($childWhere)->where('type', 3)->where('end_at', 'between', [strtotime(date('Y-m-d')), time()])->select();
        $today_user_recharge = 0;

        $today_count_recharge_tmp = [];
        foreach ($today_user_recharges as $v) {
            $today_count_recharge_tmp[] = $v['uid'];
            $today_user_recharge = bcadd($today_user_recharge, $v['real_num'], 2);
        }
        $today_count_recharge = count(array_unique($today_count_recharge_tmp));

        $yes_user_recharges = Recharge::where('status', 2)->where($childWhere)->where('type', 3)->where('end_at', 'between', [$yes1, $yes2])->select();
        $yes_user_recharge = 0;
        $yes_count_recharge_tmp = [];
        foreach ($yes_user_recharges as $key => $v) {
            $yes_count_recharge_tmp[] = $v['uid'];
            $yes_user_recharge = bcadd($yes_user_recharge, $v['real_num'], 2);
        }
        $yes_count_recharge = count(array_unique($yes_count_recharge_tmp));

        $mon_user_recharges = Recharge::where('status', 2)->where($childWhere)->where('end_at', 'between', [strtotime(date("Y-m-01 00:00:00")), time()])->select();
        $mon_user_recharge = 0;
        foreach ($mon_user_recharges as $key => $v) {
            $mon_user_recharge = bcadd($mon_user_recharge, $v['real_num'], 2);
        }
        $monyes_user_recharges = Recharge::where('status', 2)->where($childWhere)->where('end_at', 'between', [$monyes1, $monyes2])->select();
        $monyes_user_recharge = 0;
        foreach ($monyes_user_recharges as $key => $v) {
            $monyes_user_recharge = bcadd($monyes_user_recharge, $v['real_num'], 2);
        }

        //提现
        $today_user_deposits = Deposit::where('status', 2)->where($childWhere)->where('end_at', 'between', [strtotime(date('Y-m-d')), time()])->select();
        $today_user_deposit = 0;
        foreach ($today_user_deposits as $key => $v) {
            $today_user_deposit = bcadd($today_user_deposit, $v['real_num'], 2);
        }
        $yes_user_deposits = Deposit::where('status', 2)->where($childWhere)->where('end_at', 'between', [$yes1, $yes2])->select();
        $yes_user_deposit = 0;
        foreach ($yes_user_deposits as $key => $v) {
            $yes_user_deposit = bcadd($yes_user_deposit, $v['real_num'], 2);
        }
        $mon_user_deposits = Deposit::where('status', 2)->where($childWhere)->where('end_at', 'between', [strtotime(date("Y-m-01 00:00:00")), time()])->select();
        $mon_user_deposit = 0;
        foreach ($mon_user_deposits as $key => $v) {
            $mon_user_deposit = bcadd($mon_user_deposit, $v['real_num'], 2);
        }
        $monyes_user_deposits = Deposit::where('status', 2)->where($childWhere)->where('end_at', 'between', [$monyes1, $monyes2])->select();
        $monyes_user_deposit = 0;
        foreach ($monyes_user_deposits as $key => $v) {
            $monyes_user_deposit = bcadd($monyes_user_deposit, $v['real_num'], 2);
        }

        $this->view->assign([
            'todayUser' => (float)$today_users_num, //今日注册
            'yesterdayUser' => (float)$yes_users_num, //昨日注册
            'todayRecharge' => (float)$today_user_recharge, //今日充值
            'yesterdayRecharge' => (float)$yes_user_recharge, //昨日充值
            'todayDeposit' => (float)$today_user_deposit, //今日取款
            'yesterdayDeposit' => (float)$yes_user_deposit, //昨日取款
            'todayRechargeUser' => (int)$today_count_recharge, //充值人数
            'yesterdayRechargeUser' => (int)$yes_count_recharge, //昨日人数
            'todayProfit' => (float)bcsub($today_user_recharge, $today_user_deposit, 2), //今日盈利
            'yesterdayProfit' => (float)bcsub($yes_user_recharge, $yes_user_deposit, 2), //昨日盈利
            'thisMonthProfit' => (float)bcsub($mon_user_recharge, $mon_user_deposit, 2), //本月盈利
            'lastMonthProfit' => (float)bcsub($monyes_user_recharge, $monyes_user_deposit, 2), //上月盈利
        ]);

        return $this->view->fetch();
    }

    /**
     * 近一个月用户注册数据
     */
    public function register()
    {
        $daylist = [];
        $datalist = [];

        $childWhere = [];
        if(!$this->isSuperAdmin) {
            // 查询userid
            $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
            $childWhere[] = ['id', 'IN', $user_id];
        }
        for ($i = 30; $i >= 1; $i--) {
            $daylist[] = date("m-d", strtotime("-" . $i . " day"));

            $start = strtotime(date("Y-m-d 00:00:00", strtotime("-" . $i . " day")));
            $end = strtotime(date("Y-m-d 23:59:59", strtotime("-" . $i . " day")));

            //注册
            $yes_users_num = User::where('create_at', 'between', [$start, $end])->where($childWhere)->count('id');
            $datalist[] = $yes_users_num;
        }
        $pos = number_format(max($datalist), 0, "", "");

        $data = [
            'maxs'  => $pos,
            'splitNumbers' => 5,
            'days'  => $daylist,
            'datas' => $datalist,
        ];

        return json($data);
    }

    /**
     * 近一个月充值数据
     */
    public function recharge()
    {
        $daylist = [];
        $datalist = [];
        $childWhere = [];
        if(!$this->isSuperAdmin) {
            // 查询userid
            $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
            $childWhere[] = ['uid', 'IN', $user_id];
        }
        for ($i = 30; $i >= 1; $i--) {
            $daylist[] = date("m-d", strtotime("-" . $i . " day"));

            $start = strtotime(date("Y-m-d 00:00:00", strtotime("-" . $i . " day")));
            $end = strtotime(date("Y-m-d 23:59:59", strtotime("-" . $i . " day")));

            //充值
            $today_user_recharges = Recharge::where('status', 2)->where('type', 1)->where($childWhere)->where('end_at', 'between', [$start, $end])->select();
            $today_user_recharges = Recharge::where('status', 2)->where('type', 1)->where($childWhere)->where('end_at', 'between', [$start, $end])->select();
            $today_user_recharge = 0;
            foreach ($today_user_recharges as $v) {
                $today_user_recharge = bcadd($today_user_recharge, $v['num'], 2);
            }
            $datalist[] = $today_user_recharge;
        }

        $pos = number_format(max($datalist), 0, "", "");

        $data = [
            'maxs'  => $pos,
            'splitNumbers' => 5,
            'days' => $daylist,
            'datas' => $datalist,
        ];

        return json($data);
    }

    /**
     * 近一个月的取款数据
     */
    public function withdraw()
    {
        $daylist = [];
        $datalist = [];
        $childWhere = [];
        if(!$this->isSuperAdmin) {
            // 查询userid
            $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
            $childWhere[] = ['uid', 'IN', $user_id];
        }
        for ($i = 30; $i >= 1; $i--) {
            $daylist[] = date("m-d", strtotime("-" . $i . " day"));

            $start = strtotime(date("Y-m-d 00:00:00", strtotime("-" . $i . " day")));
            $end = strtotime(date("Y-m-d 23:59:59", strtotime("-" . $i . " day")));

            //取款
            $today_user_deposits = Deposit::where('status', 2)->where($childWhere)->where('end_at', 'between', [$start, $end])->select();
            $today_user_deposit = 0;
            foreach ($today_user_deposits as $v) {
                $today_user_deposit = bcadd($today_user_deposit, $v['real_num'], 2);
            }
            $datalist[] = $today_user_deposit;
        }
        $pos = number_format(max($datalist), 0, "", "");

        $data = [
            'maxs'  => $pos,
            'splitNumbers' => 5,
            'days' => $daylist,
            'datas' => $datalist,
        ];

        return json($data);
    }

    /**
     * 近一月的盈利数据
     */
    public function money()
    {
        $daylist = [];
        $datalist = [];
        $childWhere = [];
        if(!$this->isSuperAdmin) {
            // 查询userid
            $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
            $childWhere[] = ['uid', 'IN', $user_id];
        }
        for ($i = 30; $i >= 1; $i--) {
            $daylist[] = date("m-d", strtotime("-" . $i . " day"));

            $start = strtotime(date("Y-m-d 00:00:00", strtotime("-" . $i . " day")));
            $end = strtotime(date("Y-m-d 23:59:59", strtotime("-" . $i . " day")));

            //充值
            $today_user_recharges = Recharge::where('status', 2)->where($childWhere)->where('type', 1)->where('end_at', 'between', [$start, $end])->select();
            $today_user_recharge = 0;
            foreach ($today_user_recharges as $v) {
                $today_user_recharge = bcadd($today_user_recharge, $v['num'], 2);
            }

            //取款
            $today_user_deposits = Deposit::where('status', 2)->where($childWhere)->where('end_at', 'between', [$start, $end])->select();
            $today_user_deposit = 0;
            foreach ($today_user_deposits as $v) {
                $today_user_deposit = bcadd($today_user_deposit, $v['real_num'], 2);
            }
            $datalist[] = bcsub($today_user_recharge, $today_user_deposit, 2);
        }

        $pos = number_format(max($datalist), 0, "", "");


        $data = [
            'maxs'  => $pos,
            'splitNumbers' => 5,
            'days' => $daylist,
            'datas' => $datalist,
        ];

        return json($data);
    }

    public function getSysId()
    {
        $id = Config::where('name', 'orange_id')->value('value');
        $type = Config::where('name', 'orange_type')->value('value');
        return json(['id' => $id, 'type' => $type]);
    }
}
