<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\Address;
use app\common\model\Banner;
use app\common\service\UserService;
use app\common\service\BannerService;
use app\common\service\IndexService;

class Index extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';


    //服务层
    protected $userService = null;
    protected $bannerService = null;
    protected $indexService = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->userService = new UserService();
        $this->bannerService = new BannerService();
        $this->indexService = new IndexService();
    }

    public function index()
    {
        if ($this->request->isPost()) {
            //获取用户消息
            $message = $this->userService->userMessageServer($this->uid);
            //获取用户信息
            $user = $this->userService->getUserInfo($this->uid);

            $banner = $this->bannerService->getList();

            $reward = $this->indexService->getHomeRewardList();

            $cate = $this->indexService->getHomeCateLevelList($user ['id'], $user ['level']);

            $indexData = $this->indexService->getIndexData();
            $uid = $this->uid;

            $user ['commission'] = \app\common\model\Order::where('status', 1)->where('uid', $uid)->sum('commission');


            return success([
                'message' => $message,
                'notice' => $indexData ['notice'],
                'hezuo' => $indexData ['hezuo'],
                'jianjie' => $indexData ['jianjie'],
                'guize' => $indexData ['guize'],
                'gundong' => $indexData ['gundong'],
                'tanchunag' => $indexData ['tanchunag'],
                'banner' => $banner,
                'cate' => $cate,
                'info' => $user,
                'list' => $reward,
            ]);

        }

        $tg = $this->bannerService->getTelegram();

        $ws = $this->bannerService->getWhatsApp();

        //获取用户消息
        $message = $this->userService->userMessageServer($this->uid);

        $this->view->assign([
            'title' => __('Index'),
            'tg' => $tg,
            'ws' => $ws,
            'message' => $message,
        ]);

        return $this->view->fetch();
    }

}
