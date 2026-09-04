<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\OrangeLogBalance;

class Order extends Frontend
{
    protected $noNeedRight = '*';
    protected $layout = '';


    //服务层
    protected $userService = null;
    protected $orderService = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->userService = new \app\common\service\UserService();
        $this->orderService = new \app\common\service\OrderService();
    }

    public function getCommission()
    {
        $commission = OrangeLogBalance::where(['uid' => $this->uid,'action' => 5, 'i' => 1])->sum('number');
        $commission = round($commission,2);
        return success($commission);
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $status = $this->filter($this->request->post('status'));
            $page = $this->filter($this->request->post('page', 1));

            $orderList = $this->orderService->getPageJoinList($status, $this->uid);

            return success(['data' => $orderList ['data'], 'pages' => $orderList ['last_page']]);
        }

        //获取用户消息
        $message = $this->userService->userMessageServer($this->uid);
        $status = $this->filter($this->request->get('status'));

        $this->view->assign([
            'title' => __('Order'),
            'status' => $status,
            'message' => $message,
        ]);

        return $this->view->fetch();
    }

}
