<?php

namespace app\admin\controller;

use app\common\model\User;
use app\common\model\Recharge;
use app\common\model\Deposit;
use app\common\controller\Backend;
use think\facade\Db;

/**
 * 数据报表.
 *
 * @icon fa fa-user
 */
class Report extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 查看.
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {

            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['time'])) {
                $time = explode(' - ', $filter['time']);
                $start_time = strtotime($time [0]);
                $end_time = strtotime($time [1]);
            } else {
                $start_time = strtotime(date('Y-m-d 00:00:00'));
                $end_time = strtotime(date('Y-m-d 23:59:59'));
            }

            $days = intval(($end_time - $start_time) / 86400);

            $list = [];
            if ($days > 0) {
                for ($i = 0; $i <= $days; $i++) {

                    $day = date("Y-m-d", strtotime("-" . $i . " day"));
                    $_start = strtotime(date("Y-m-d 00:00:00", strtotime("-" . $i . " day")));
                    $_end = strtotime(date("Y-m-d 23:59:59", strtotime("-" . $i . " day")));

                    $where ['recharge.status'] = 2;
                    $where ['recharge.type'] = 1;

                    if(!$this->isSuperAdmin) {
                        $where ['user.parent_id'] = $this->auth->id;
                    }

                    //充值
                    $today_user_recharges = (new Recharge())->alias('recharge')->join('user user', 'recharge.uid = user.id', 'left')->where($where)->where('recharge.end_at', 'between', [$_start, $_end])->sum('recharge.num');
                    $today_user_recharge = sprintf("%.2f", $today_user_recharges);

                    unset($where ['recharge.status']);
                    $where ['recharge.type'] = 2;
                    //彩金
                    $today_user_lotterys = (new Recharge())->alias('recharge')->join('user user', 'recharge.uid = user.id', 'left')->where('type', 2)->where('recharge.create_at', 'between', [$_start, $_end])->sum('recharge.num');
                    $today_user_lottery = sprintf("%.2f", $today_user_lotterys);

                    unset($where ['recharge.type']);
                    $where ['deposit.status'] = 2;
                    //取款
                    $today_user_deposits = (new Deposit())->alias('deposit')->join('user user', 'deposit.uid = user.id', 'left')->where($where)->where('deposit.end_at', 'between', [$_start, $_end])->sum('deposit.real_num');
                    $today_user_deposit = sprintf("%.2f", $today_user_deposits);

                    $total = bcadd($today_user_recharge, $today_user_lottery, 2);


                    $list[] = [
                        'date' => $day,
                        'recharge' => $today_user_recharge,
                        'lottery' => $today_user_lottery,
                        'water' => $total,
                        'deposit' => $today_user_deposit,
                    ];
                }
            } else {
                $day = date("Y-m-d");

                $where ['recharge.status'] = 2;
                $where ['recharge.type'] = 1;

                if(!$this->isSuperAdmin) {
                    $where ['user.parent_id'] = $this->auth->id;
                }

                //充值
                $today_user_recharges = (new Recharge())->alias('recharge')->join('user user', 'recharge.uid = user.id', 'left')->where($where)->where('recharge.end_at', 'between', [$start_time, $end_time])->sum('recharge.num');
                $today_user_recharge = sprintf("%.2f", $today_user_recharges);

                unset($where ['recharge.status']);
                $where ['recharge.type'] = 2;
                //彩金
                $today_user_lotterys = (new Recharge())->alias('recharge')->join('user user', 'recharge.uid = user.id', 'left')->where($where)->where('recharge.create_at', 'between', [$start_time, $end_time])->sum('recharge.num');
                $today_user_lottery = sprintf("%.2f", $today_user_lotterys);

                unset($where ['recharge.type']);
                $where ['deposit.status'] = 2;
                //取款
                $today_user_deposits = (new Deposit())->alias('deposit')->join('user user', 'deposit.uid = user.id', 'left')->where($where)->where('deposit.end_at', 'between', [$start_time, $end_time])->sum('deposit.real_num');
                $today_user_deposit = sprintf("%.2f", $today_user_deposits);

                $total = bcadd($today_user_recharge, $today_user_lottery, 2);

                $list[] = [
                    'date' => $day,
                    'recharge' => $today_user_recharge,
                    'lottery' => $today_user_lottery,
                    'water' => $total,
                    'deposit' => $today_user_deposit,
                ];
            }

            $result = array("total" => count($list), "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }
}
