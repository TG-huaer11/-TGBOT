<?php

namespace app\common\service;

use app\common\model\LogReward;

class LogRewardService extends BaseService
{

    public function rotLogRewardDataOfIndex($uid)
    {
        $yes1 = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $yes2 = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));

        $yes_team_num =  LogReward::where('uid', $uid)->where('create_at', 'between', [$yes1, $yes2])->where('status', 1)->sum('num');                //获取下级返佣数额
        $today_team_num =  LogReward::where('uid', $uid)->where('create_at', 'between', [strtotime('Y-m-d'), time()])->where('status', 1)->sum('num'); //获取下级返佣数额

        return ['yes_team_num' => $yes_team_num, 'today_team_num' => $today_team_num];
    }
}