<?php

namespace app\common\service;

use app\common\model\Config;
use app\common\model\Goods;
use app\common\model\GoodsCate;
use app\common\model\IndexMsg;
use app\common\model\Order;
use app\common\model\UserLevel;
use app\common\tool\Random;

class IndexService extends BaseService
{

    /**
     * 获取默认国家区号
     * @return array
     */
    public function getDefaultCountryCode()
    {
        return Config::where('name', 'default_country_code')->value('value');
    }

    public function getHomeRewardList()
    {
        $res = Goods::field('goods_price')->order('id desc')->select();

        if ($res) {
            foreach ($res as $k => $v) {
                $v['goods_price'] = bcmul($v['goods_price'], 0.15, 2);
                $v['username'] = strtolower(Random::randomString(4)) . '*****';
                $v['level'] = mt_rand(2, 4);
                $res[$k] = $v;
            }
        }

        return Random::shuffleAssoc($res);
    }

    public function getHomeCateLevelList($uid)
    {
        $userInfo = (new \app\common\service\UserService)->getUserInfo($uid);
        $level = $userInfo ['level'];
        $progress = $userInfo ['progress'];

        $list = GoodsCate::alias('c')
            ->join('user_level u', 'u.id=c.level_id', 'left')
            ->field('c.name,c.id,c.cate_info,c.cate_pic,c.status,u.name as levelname,u.pic,u.level,u.bili')
            ->order('c.id asc')
            ->select()
            ->toArray();

        $now = Order::where('uid', $uid)
            ->where('level', $level)
            ->where('is_deleted', 0)
            ->whereRaw('status=1 or status=5')
            ->count();

        $meter = 0;

        if ($progress != '') {
            $meter = $progress;
        } else {

            if ($level == 1) {
                if ($now >= 1) {
                    $meter = 100;
                }
            }

            if ($level == 2) {
                if ($now == 1) {
                    $meter = 65;
                }

                if ($now >= 2) {
                    $meter = 100;
                }
            }
            if ($level == 3) {
                if ($now == 1) {
                    $meter = 35;
                }

                if ($now == 2) {
                    $meter = 60;
                }

                if ($now == 3) {
                    $meter = 85;
                }

                if ($now == 4) {
                    $meter = 92;
                }

                if ($now == 5) {
                    $meter = 98;
                }

                if ($now >= 6) {
                    $meter = 100;
                }
            }

            if ($level == 4) {
                $meter = $now;
            }
        }

        foreach ($list as &$item) {
            if ($item['level'] < $level) {
                $item['jindu'] = 100;
            }
            if ($item['level'] > $level) {
                $item['jindu'] = 0;
            }
            if ($item['level'] == $level) {
                $item['jindu'] = $meter;
            }
        }

        return $list;
    }

    public function getIndexData()
    {
        $notice = IndexMsg::where(['id' => 1])->value('content');
        $hezuo = IndexMsg::where(['id' => 4])->value('content');
        $jianjie = IndexMsg::where(['id' => 2])->value('content');
        $guize = IndexMsg::where(['id' => 3])->value('content');
        $gundong = IndexMsg::where(['id' => 8])->value('content');
        $tanchunag = IndexMsg::where(['id' => 11])->value('content');

        return ['notice' => $notice, 'hezuo' => $hezuo, 'jianjie' => $jianjie, 'guize' => $guize, 'gundong' => $gundong, 'tanchunag' => $tanchunag];
    }

}