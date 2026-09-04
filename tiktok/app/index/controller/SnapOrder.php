<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\constant\CT;
use Exception;
use think\App;

/**
 * Snap 抢单
 * Class Index
 * @package app\index\controller
 */
class SnapOrder extends Frontend
{

    protected $noNeedRight = '*';

    /**
     * 获取单笔订单详情
     */
    public function order_info()
    {
        if (request()->isPost()) {
            $orderId = $this->filter($this->request->post('id'));

            try {

                $res = (new \app\common\service\SnapOrderService())->getOrderJoinInfo($orderId, $this->uid);

                return success($res);
            } catch (Exception $e) {
                return error($e->getMessage());
            }


        }

        return error('bad request');
    }

    /**
     * 订单提交
     */
    public function submit_order()
    {

        if ($this->request->isPost()) {
            $orderId = $this->filter($this->request->post('oid'));

            try {
                $res = (new \app\common\service\SnapOrderService())->submitOrder($this->uid, $orderId);
                if (is_array($res) && $res ['code'] != 1) {
                    return error($res ['msg'], $res ['code']);
                } else {
                    return success('', "");
                }

            } catch (Exception $e) {
                return error($e->getMessage());
            }
        }
        return error('operation failed');

    }

    /**
     * 抢单
     */
    public function rob_order()
    {
        if ($this->request->isPost()) {

            
            $userInfo = (new \app\common\service\UserService())->getUserInfo($this->uid);
            if (empty($userInfo)) {
                throw new \Exception('user does not exist');
            }
            
              if ($userInfo['index_orders']=='0') {
                return error("There is no order yet, please contact the mall customer service");
            }


            if ($userInfo['status'] != CT::STATUS_ENABLE) {
                return error("the account has been disabled");
            }
            if ($userInfo['deal_status'] == CT::D_S_FREEZE) {
                return error("the account transaction function has been frozen");
            }
            
//            if ($userInfo['deal_status'] == CT::D_S_TRADING) {
//                return error(lang("Rot_Order_Index_There"));
//            }

            try {
                $assignOrder = (new \app\common\service\SnapOrderService())->robOrderNew($userInfo);
                
                if ($assignOrder) {
                    return success($assignOrder, "successfully grabbed the order");
                } else {
                    return error("order failed! please try again later");
                }
            } catch (Exception $e) {
                return error($e->getMessage());
            }
        }
        return error('bad request');

    }

}