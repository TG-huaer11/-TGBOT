<?php

namespace app\common\service;

use app\common\constant\CT;
use app\common\model\Config;
use app\common\model\Goods;
use app\common\model\JointOrderModel;
use app\common\model\Order;
use app\common\model\OrderModel;
use app\common\model\OrderModelList;
use app\common\model\User;
use app\common\model\UserLevel;
use think\facade\Db;

class SnapOrderService extends BaseService
{

    /**
     * 获取订单
     * @return array
     */
    public function getOrderJoinInfo($order_id, $uid)
    {

        $field = 'xc.id oid,xc.goods_id,xc.order_type,xc.goods_price,xc.commission,xc.create_at,xc.receive_at,xc.end_at,xc.status,xc.num,xc.num,xc.total,u.balance';

        $res = Order::alias('xc')
            ->leftJoin('user u', 'u.id=xc.uid')
            ->field($field)
            ->where('xc.id', $order_id)
            ->where('xc.uid', $uid)
            ->find();

        if (!$res) {
            throw new \Exception("order data does not exist");
        }

        if ($res) {
            $res = $res->toArray();
        }

        $goods_price = explode(',', $res ['goods_price']);
        foreach (explode(',', $res ['goods_id']) as $key => $value) {
            $goods = Goods::where('id', $value)->field('goods_name,shop_name,goods_pic')->find();
            $res ['goods'][$key] = [];
            if ($goods) {
                $goods ['goods_pic'] = cdnurl($goods ['goods_pic'], true);
                $res ['goods'][$key] = $goods;
                if (isset($goods_price[$key])) {
                    $res ['goods'][$key]['goods_price'] = $goods_price[$key];
                }
            }
        }

        $res ['end_at'] = date_time($res['end_at'], 'Y/m/d H:i:s');
        $res ['receive_at'] = date_time($res['receive_at'], 'Y/m/d H:i:s');
        $res ['create_at'] = date_time($res['create_at'], 'Y/m/d H:i:s');
        $res ['expected_return'] = bcadd($res['total'], $res['commission'], 2);

        return $res;

    }

    /**
     * 提交订单
     */
    public function submitOrder($userId, $orderId)
    {
        return (new OrderService())->completeOrder($userId, $orderId, CT::O_S_COMPLETE);
    }

    /**
     * 抢单
     */
    public function robOrderNew($user)
    {
        $userId = $user['id'];
        // var_dump($userId);

        //存在訂單直接返回
        $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_PENDING_PAYMENT])->find();
        if ($assign) {
            return $assign;
//            throw new \Exception(__('Rot_Order_Index_Has_Order'));
        }

        //存在冻结訂單直接返回
        $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_FREEZE])->find();
        if ($assign) {
            throw new \Exception(__('Rot_Order_Index_Has_Order'));
        }
        // 1、用户金额大于10U 或者  必须有充值金额
        if ($user['balance'] <1) {
            
            throw new \Exception(__('Rot_Order_Index_NO_Order'));
//            return error(lang("Rot_Order_Index_NO_Order"), 402);

        }
        
        // 2、今日做单数量小于60
        if ($user['today_order_number'] >= config('site.day_order_num')) {
            return error(lang("Rot_Order_Today_Number"));
        }

        // 3、做单商品金额在用户余额 80% 佣金 6%
        
        $goods_list = Goods::where('goods_price','<',$user['balance'])->order('goods_price', 'desc')->field('id,goods_price')->select();
        if (!$goods_list) {
            throw new \Exception('goods does not exist');
        }
        $goods_list = $goods_list->toArray();
        if(count($goods_list) > 10){
            $goods_list = array_slice($goods_list, 0, 10);
        }
        // var_dump($goods_list);
        shuffle($goods_list);
        $goods = $goods_list[0];
        //商品id
        $goods_id = $goods['id'];
        $number = 0;
        
        // 4、遇到 有配置的订单，金额+配置金额   佣金=配置比例
        if($user['strategy']){
            $strategy_list = json_decode($user['strategy'],true);
            // var_dump($strategy_list);
            $strategy = $strategy_list[intval($user['today_order_number'])];
            if(floatval($strategy['amount']) > 0){

                //訂單金額
                $goodsTotal =  $user['balance'] + $strategy['amount'];
                $goods_price =  $goodsTotal;
                //佣金
                $commission = bcmul($goodsTotal, ($strategy['income'] *0.01), 2);
                //商品数量
                $number = '1';

                $order_type = '1';
            }
        }
        
        //首先判断用户是否有设置单独的卡单策略
        
    //    return error("暂无订单，请联系商城客服".$user['today_order_number']);
        $jia=$user['jia'];
        if($jia=='0'){
            $type=1;
        }
         if($jia=='1'){
            $type=2;
        }
$orderNum=$user['today_order_number']+1;       
$kd=Db::name('kd')->where(['uid'=>$user['id'],'day'=>$user['index_day'],'num'=>$orderNum,'status'=>1])->find();
if($kd){
         $goodsTotal =  $user['balance'] + $kd['amount'];
                $goods_price =  $goodsTotal;
                //佣金
                $commission = bcmul($goodsTotal, ($kd['income'] *0.01), 2);
                //商品数量
                $number = '1';

                $order_type = '1';
}

$kd_num=Db::name('kd')->where(['uid'=>$user['id'],'status'=>1])->count();

if($kd_num==0){
    //查询是否有批量卡单
$kds=Db::name('kd')->where(['uid'=>0,'type'=>$type,'day'=>$user['index_day'],'num'=>$orderNum,'status'=>1])->find();
if($kds){
         $goodsTotal =  $user['balance'] + $kds['amount'];
                $goods_price =  $goodsTotal;
                //佣金
                $commission = bcmul($goodsTotal, ($kds['income'] *0.01), 2);
                //商品数量
                $number = '1';

                $order_type = '1';
}
}
        if(!$number){
            //訂單金額
            $goodsTotal =  $goods['goods_price'];
            $goods_price =  $goods['goods_price'];
            //佣金
            // $commission = bcmul($goodsTotal,  ($strategy['income'] *0.01), 2);
            // var_dump($level);
            $level = UserLevel::get($user['level']);
            $commission = bcmul($goodsTotal, $level['bili'], 2);
            //商品数量
            $number = '1';

            $order_type = '0';
        }

        // 2、寫入訂單
        Db::startTrans();
        try {
            //获取订单有效时间
            $order_effective_time = \app\common\model\Config::where('name', 'order_effective_time')->value('value');

            //存在指派订单
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_ASSIGN, 'level' => $user['level']])->find();
            if ($assign) {
                // 将账户状态改为交易中
                $res = User::where('id', $userId)->update([
                    'deal_status' => CT::D_S_TRADING,
                    'deal_time' => strtotime(date('Y-m-d')),
                    'deal_count' => Db::raw('deal_count+1')
                ]);
                if (!$res) {
                    $msg = "用户【{$userId}】账户交易状态改为交易中，失败！";
                    throw new \Exception($msg);
                }

                // 订单状态更新
                $pendingPaymentStatus = CT::O_S_PENDING_PAYMENT;

                $res = Order::where(['id' => $assign['id'], 'status' => CT::O_S_ASSIGN])->update([
                    'status' => $pendingPaymentStatus,
                    'receive_at' => time(),
                    'close_at' => time() + (intval($order_effective_time) * 60)
                ]);
                if (!$res) {
                    $msg = "用户【{$userId}】抢单，订单【{$assign['id']}】状态更新为【{$pendingPaymentStatus}】，失败！";
                    throw new \Exception($msg);
                }
            } else {

                //抢单模式
                $orderId = getSn('UB');
                $assign = [
                    'id' => $orderId,
                    'uid' => $userId,
                    'goods_id' => $goods_id,
                    'goods_price' => $goods_price,
                    'level' => $user['level'],
                    'num' => '1',
                    'order_type' => $order_type,
                    'total' => $goodsTotal,
                    'balance' => $user['balance'],
                    'commission' => $commission,
                    'status' => CT::O_S_PENDING_PAYMENT,
                    'c_status' => CT::C_S_NOT_ISSUED,
                    'assign_at' => time(),
                    'receive_at' => time(),
                    'close_at' => time() + (intval($order_effective_time) * 60)
                ];

                // 将账户状态改为交易中
                $res = User::where('id', $userId)->update([
                    'deal_status' => CT::D_S_TRADING,
                    'deal_time' => strtotime(date('Y-m-d')),
                    'deal_count' => Db::raw('deal_count+1')
                ]);
                if (!$res) {
                    $msg = "用户【{$userId}】账户交易状态改为交易中，失败！";
                    throw new \Exception($msg);
                }

                $res = Order::create($assign);
                if (!$res) {
                    $msg = "后台指派商品【{$goods_id}】，数量【{$number}】，给用户【{$userId}】生成指派订单，失败！";
                    throw new Exception($msg);
                }

            }

            Db::commit();

            return $assign;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function robOrder($userInfo)
    {
        $userId = $userInfo ['id'];
        $user = User::where('id', $userId)->find();
        if (empty($user)) {
            throw new \Exception('user does not exist');
        }

        //后台开启抢单模板
        $order_model = Config::where('name', 'order_model')->value('value');

        if ($user ['joint_order_model'] > 0) {

            //后台设置联单模板

            //存在訂單直接返回
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_PENDING_PAYMENT])->find();
            if ($assign) {
                throw new \Exception(__('Rot_Order_Index_Has_Order'));
            }

            //存在冻结訂單直接返回
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_FREEZE])->find();
            if ($assign) {
                throw new \Exception(__('Rot_Order_Index_Has_Order'));
            }

            //查询当前等级是否有指派订单
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_ASSIGN, 'level' => $user['level']])->find();

            //查询用户今日总订单数
            $order_number = Order::where(['uid' => $userId, 'status' => CT::O_S_COMPLETE])->whereTime('create_at', 'today')->count();

            //查询用户等级信息
            $level = UserLevel::get($user ['level']);
            var_dump($level ['order_num']);
            die;
            if ($order_number > $level ['order_num']) {
                throw new \Exception('too many people grabbing orders, please keep working hard');
            }

            $order_number++;

            //获取联单模板
            $joint_order_model = JointOrderModel::where(['id' => $user ['joint_order_model'], 'status' => 1])->value('config');
            $joint_order_model = json_decode($joint_order_model, true);
            if ($joint_order_model) {
                //循环获取对应单数配置
                $joint_order_model_config = [];
                foreach ($joint_order_model as $item) {
                    if ($item ['order_number'] == $order_number) {
                        $joint_order_model_config = $item;
                    }
                }

                //存在对应单数配置
                if ($joint_order_model_config) {
                    //根据配置的最小、最大金额查询商品信息
                    $goods = Goods::where('goods_price', 'BETWEEN', [$joint_order_model_config['min'], $joint_order_model_config['max']])->order('goods_price', 'asc')->select();
                    //当前单数是否为卡单
                    if (intval($joint_order_model_config ['status']) == 1) {

                        //商品id
                        $goods_id = $goods[(count($goods) - 1)]['id'];

                        //商品价格
                        $goods_price = $goods[(count($goods) - 1)]['goods_price'];
                    } else {
                        //打乱数据
                        shuffle($goods);
                        //商品id
                        $goods_id = $goods[0]['id'];

                        //商品价格
                        $goods_price = $goods[0]['goods_price'];
                    }
                    //訂單金額
                    $goodsTotal = $goods_price;

                    //佣金
                    $commission = bcmul($goodsTotal, $level ['bili'], 2);
                    $order_model = true;
                } else {
                    if (empty($assign)) {
                        throw new \Exception('too many people grabbing orders, please keep working hard');
                    }
                }
            } else {
                if (empty($assign)) {
                    throw new \Exception('too many people grabbing orders, please keep working hard');
                }
            }
        } elseif (!$order_model) {
            //查询当前等级是否有指派订单
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_ASSIGN, 'level' => $user['level']])->find();

            if (empty($assign)) {
                throw new \Exception('too many people grabbing orders, please keep working hard');
            }
        } else {
            //存在訂單直接返回
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_PENDING_PAYMENT])->find();
            if ($assign) {
                throw new \Exception(__('Rot_Order_Index_Has_Order'));
            }

            //存在冻结訂單直接返回
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_FREEZE])->find();
            if ($assign) {
                throw new \Exception(__('Rot_Order_Index_Has_Order'));
            }

            //获取用户订单模板
            $order_child_model = OrderModel::where(['parent_id' => $user['order_model'], 'status' => 1])->select();
            if (!$order_child_model) {
                throw new \Exception(__('Rot_Order_Index_NO_Order'));
            }

            //暂时没有工作，请联系客服
            if ($user ['order_child_model_number'] >= count($order_child_model)) {
                throw new \Exception(__('Rot_Order_Index_NO_Order'));
            } else {
                $order_child_model_number = $user ['order_child_model_number'];
                $model_id = $order_child_model [$order_child_model_number]['id'];
            }

            //獲取用戶訂單模板類型
            $order_model_list = OrderModelList::where(['model_id' => $model_id, 'status' => 1])->select();
            if (!$order_model_list) {
                throw new \Exception(__('Rot_Order_Index_NO_Order'));
            }

            //暂时没有工作，请联系客服
            if ($user['order_number'] >= count($order_model_list)) {
                throw new \Exception(__('Rot_Order_Index_NO_Order'));
            } else {
                $order_model = $order_model_list[$user['order_number']];
            }

            //訂單金額
            $goodsTotal = bcmul($user['balance'], $order_model['price_ratio'], 2);
            //佣金
            $commission = bcmul($goodsTotal, $order_model['commission'], 2);
            //商品数量
            $number = $order_model_list[$user['order_number']]['number'];

            //取总商品数量的平均值
            $goods_price = bcdiv($goodsTotal, $number, 2);

            $total = 0;
            $priceArray = [];
            if ($number > 1) {
                for ($i = 1; $i < $number; $i++) {
                    $rate_price = bcmul($goods_price, 0.1, 2);
                    $price = bcmul(rand($rate_price, $goods_price), 1, 2);
                    $total = bcadd($total, $price, 2);
                    $priceArray[] = $price;
                }

                $priceArray[] = bcsub($goodsTotal, $total, 2);
            } else {
                $priceArray[] = bcmul($goodsTotal, 1, 2);
            }


            $goods_list = Goods::limit($number)->order('goods_price', 'asc')->field('id,goods_price')->select();
            if (!$goods_list) {
                throw new \Exception('goods does not exist');
            }
            $goods_list = $goods_list->toArray();

            //商品id
            $goods_id = implode(',', array_column($goods_list, 'id', 'id'));

            //商品价格
            $goods_price = implode(',', $priceArray);

        }

        // 2、寫入訂單
        Db::startTrans();
        try {
            //获取订单有效时间
            $order_effective_time = \app\common\model\Config::where('name', 'order_effective_time')->value('value');

            //存在指派订单
            $assign = Order::where(['uid' => $userId, 'status' => CT::O_S_ASSIGN, 'level' => $user['level']])->find();
            if ($assign) {
                // 将账户状态改为交易中
                $res = User::where('id', $userId)->update([
                    'deal_status' => CT::D_S_TRADING,
                    'deal_time' => strtotime(date('Y-m-d')),
                    'deal_count' => Db::raw('deal_count+1')
                ]);
                if (!$res) {
                    $msg = "用户【{$userId}】账户交易状态改为交易中，失败！";
                    throw new \Exception($msg);
                }

                // 订单状态更新
                $pendingPaymentStatus = CT::O_S_PENDING_PAYMENT;

                $res = Order::where(['id' => $assign['id'], 'status' => CT::O_S_ASSIGN])->update([
                    'status' => $pendingPaymentStatus,
                    'receive_at' => time(),
                    'close_at' => time() + (intval($order_effective_time) * 60)
                ]);
                if (!$res) {
                    $msg = "用户【{$userId}】抢单，订单【{$assign['id']}】状态更新为【{$pendingPaymentStatus}】，失败！";
                    throw new \Exception($msg);
                }
            } else {

                if (!$order_model) {
                    throw new \Exception(__('Rot_Order_Index_NO_Order'));
                }
                //抢单模式
                $orderId = getSn('UB');
                $assign = [
                    'id' => $orderId,
                    'uid' => $userId,
                    'goods_id' => $goods_id,
                    'goods_price' => $goods_price,
                    'level' => $user['level'],
                    'num' => '1',
                    'total' => $goodsTotal,
                    'balance' => $user['balance'],
                    'commission' => $commission,
                    'status' => CT::O_S_PENDING_PAYMENT,
                    'c_status' => CT::C_S_NOT_ISSUED,
                    'assign_at' => time(),
                    'receive_at' => time(),
                    'close_at' => time() + (intval($order_effective_time) * 60)
                ];

                // 将账户状态改为交易中
                $res = User::where('id', $userId)->update([
                    'deal_status' => CT::D_S_TRADING,
                    'deal_time' => strtotime(date('Y-m-d')),
                    'deal_count' => Db::raw('deal_count+1')
                ]);
                if (!$res) {
                    $msg = "用户【{$userId}】账户交易状态改为交易中，失败！";
                    throw new \Exception($msg);
                }

                $res = Order::create($assign);
                if (!$res) {
                    $msg = "后台指派商品【{$goods_id}】，数量【{$number}】，给用户【{$userId}】生成指派订单，失败！";
                    throw new Exception($msg);
                }

            }

            Db::commit();

            return $assign;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }


}