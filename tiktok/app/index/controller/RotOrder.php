<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\IndexMsg as IndexMsgModel;
use think\App;

class RotOrder extends Frontend
{

    protected $noNeedRight = '*';

    protected $orderService = null;
    protected $userService = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->orderService = new \app\common\service\OrderService();
        $this->userService = new \app\common\service\UserService();
    }


    /**
     * 首页
     */
    public function index()
    {
        if ($this->request->isPost()) {

            $order = $this->orderService->rotOrderDataOfIndex($this->uid);

            $userData = $this->userService->rotUserDataOfIndex($this->uid);

            $logReward = (new \app\common\service\LogRewardService())->rotLogRewardDataOfIndex($this->uid);

            //分类
            $type = $this->filter($this->request->post('type/d', 1));

            $goods = (new \app\common\service\GoodsService())->rotGoodsDataOfIndex($type);

            $beizhu = (new \app\common\model\IndexMsg())->where(['id' => 9])->value('content');

            $message = (new \app\common\service\UserService())->userMessageServer($this->uid);

            return success([
                'message' => $message,
                'day_deal' => $order ['day_deal'],
                'day_d_count' => $order ['day_d_count'],
                'yes_user_yongjin' => $order ['yes_user_yongjin'],
                'user_yongjin' => $order ['user_yongjin'],
                'lv3first' => $order ['lv3first'],
                'lv3lock' => $order ['lv3lock'],
                'price' => $userData ['price'],
                'lock_deal' => $userData ['lock_deal'],
                'user_level' => $userData ['user_level'],
                'order_num' => $userData ['order_num'],
                'yes_team_num' => $logReward ['yes_team_num'],
                'today_team_num' => $logReward ['today_team_num'],
                'beizhu' => $beizhu,
                'goods' => $goods ['goods'],
                'cate' => $goods ['cate'],
            ]);

        }


        $message = (new \app\common\service\UserService())->userMessageServer($this->uid);

        $type = $this->filter($this->request->get('type/d', 1));

        $this->view->assign([
            'message' => $message,
            'type' => $type,
            'title' => 'Order'
        ]);

        return $this->view->fetch();
    }

    public function submit_lv3()
    {
        if ($this->request->isPost()) {
            try {
                $order_id = $this->orderService->submit_lv3($this->uid);
                return success(['order_id' => $order_id]);
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }
        return error('bad request');
    }


}
