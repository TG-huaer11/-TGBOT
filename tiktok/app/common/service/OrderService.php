<?php

namespace app\common\service;

use app\common\constant\CT;
use app\common\model\Config;
use app\common\model\Goods;
use app\common\model\LogReward;
use app\common\model\Order;
use app\common\model\OrderModel;
use app\common\model\OrderModelList;
use app\common\model\User;
use app\common\model\UserLevel;
use think\facade\Db;

class OrderService extends BaseService
{

    /**
     * 获取用户未完成订单数
     * @param int $uid
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getUnfinishedOrder($uid)
    {
        return Order::where(['uid' => $uid, 'status' => 0, 'is_deleted' => 0, 'close_time' => ['>=', time()]])->count();
    }

    /**
     * 指派订单
     */
    public function assign($userId, $goodsId, $num, $prices = 0, $commissions = 0)
    {
        $goods = Goods::field('id, goods_price')->find($goodsId);
        if (!$goods) {
            throw new \Exception('goods does not exist');
        }
        $user = User::field('deal_status, balance, level')->find($userId);
        if (!$user) {
            throw new \Exception('user does not exist');
        }

        $level = $user['level'];
        if (!$user['level']) $level = \app\common\constant\CT::STATUS_ENABLE;
        $userLevel = UserLevel::field('bili')->where('level', $level)->find();
        if (!$userLevel) {
            throw new \Exception('level does not exist');
        }
        if ($num <= CT::DEFAULT_VALUE) {
            throw new \Exception('the quantity less than equal zero');
        }

        Db::startTrans();
        try {
            $orderId = getSn('UB');

            // 交易佣金按照会员等级
            if ($prices == 0) {
                $goodsTotal = bcmul($goods['goods_price'], $num, 2);
            } else {
                $goodsTotal = bcmul($prices, $num, 2);
            }
            if ($commissions == 0) {
                $commission = bcmul($goodsTotal, $userLevel['bili'], 2);
            } else {
                $commission = $commissions;
            }

            $res = Order::create([
                'id' => $orderId,
                'uid' => $userId,
                'goods_id' => $goodsId,
                'goods_price' => $goods['goods_price'],
                'level' => $level,
                'num' => $num,
                'total' => $goodsTotal,
                'commission' => $commission,
                'status' => CT::O_S_ASSIGN,
                'c_status' => CT::C_S_NOT_ISSUED,
                'assign_at' => time(),
            ]);
            if (!$res) {
                $msg = "后台指派商品【{$goodsId}】，数量【{$num}】，给用户【{$userId}】生成指派订单，失败！";
                throw new \Exception($msg);
            }

            $res = (new LogAssignService())->record($userId, $goodsId, $goods['goods_price'], $num, $goodsTotal, $commission);
            if (!$res) {
                $msg = "后台指派商品【{$goodsId}】，数量【{$num}】，给用户【{$userId}】生成指派日志，失败！";
                throw new \Exception($msg);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 获取订单
     * @return array
     */
    public function getPageJoinList($status, $user_id = 0, $date = null)
    {

        $where = [];
        $where[] = ['receive_at', '>', 0];
        $where[] = ['is_deleted', '=', CT::DEFAULT_VALUE];
        $status = strtolower($status);
        switch ($status) {
            case 'pending':
            case '1':
                $where[] = ['status', '=', 0];
                break;
            case 'completed':
            case '2':
                $where[] = ['status', '=', 1];
                break;
            case 'freezing':
            case '3':
                $where[] = ['status', '=', 5];
                break;
            default:
                break;
        }
        if (empty($user_id)) {
            $user_id = session('user_id');
        }
        $where[] = ['uid', '=', $user_id];
        if (!empty($date)) {
            $start = strtotime($date . '-01 00:00:00');
            $lastDay = date('Y-m-t', $start);
            $end = strtotime($lastDay . ' 23:59:59');
            $where[] = ['create_at', 'between', [$start, $end]];
        }

        $list = Order::where($where)
            ->order('create_at desc')
            ->paginate()
            ->toArray();

        if (!empty($list ['data'])) {
            foreach ($list ['data'] as &$item) {

//                if ($item ['close_at'] > 0 && $item ['close_at'] < time()) {
//                    if ($item ['status'] == 0) {
//                        Order::where('id', $item ['id'])->update(['status' => 5]);
//                        $item ['status'] = 5;
//                    }
//
//                }

                $goods_price = explode(',', $item ['goods_price']);
                foreach (explode(',', $item ['goods_id']) as $key => $value) {
                    $goods = Goods::where('id', 'in', $value)->field('goods_name,shop_name,goods_pic')->find();
                    $item ['goods'][$key] = '';
                    if ($goods) {
                        $item ['goods'][$key] = $goods->toArray();
                        if (isset($goods_price[$key])) {
                            $item ['goods'][$key]['goods_price'] = $goods_price[$key];
                        }
                        $item ['goods'][$key]['goods_pic'] = cdnurl($goods ['goods_pic'], true);
                    }


                }

                $item ['current_time'] = time();
                $item ['create_at'] = format_datetime($item ['create_at'], 'Y-m-d H:i:s');
                $item ['expected'] = bcadd($item ['commission'], $item ['total'], 2);
            }
        }

        return $list;

    }

    /**
     * 订单完成
     * @param int $userId
     * @param int $orderId
     * @param int $completeStatus 完成状态：1=用户完成，3=系统强制完成
     */
    public function completeOrder($userId, $orderId, $completeStatus)
    {
        $order = Order::where(['id' => $orderId])->find();
        
        if ($order->isEmpty()) {
            throw new \Exception('order does not exist');
        }
        
        if ($userId && $order['uid'] != $userId) {
            throw new \Exception('parameter error, please confirm the order number');
        }
        if ($order['status'] != CT::O_S_PENDING_PAYMENT) {
            throw new \Exception('order status is wrong');
        }

        $user = User::where('id', $userId)->find();
        if ($user->isEmpty()) {
            throw new \Exception('user does not exist');
        }
        if (bccomp($user['balance'], $order['total'], 2) == -1) {
            return ['code' => 402, 'msg' => __('LuckySingle',[bcsub($order['total'], $user ['balance'], 2),$order ['commission']])];
        }

        //后台开启抢单模式
        $order_model = Config::where('name', 'order_model')->value('value');
        $order_model = false;
        if ($order_model) {
            Db::startTrans();
            try {
                //查询用户模板信息
                $order_child_model = OrderModel::where(['parent_id' => $user ['order_model'], 'status' => 1])->select();
                if (!$order_child_model) {
                    $msg = "模板【{$user ['order_model']}】,不存在";
                    throw new \Exception($msg);
                }

                // 用户 冻结本金+佣金
                $changeAmount = $order['total'] * -1;

                $freezeBalance = bcadd($order['total'], $order['commission'], 2);
                $res = (new WalletService())->balanceChange(
                    $userId,
                    $changeAmount,
                    CT::B_L_T_TRADE,
                    [
                        "oid" => $orderId,
                        "freeze_balance" => $freezeBalance,
                    ]
                );

                (new CnmService())->order($orderId, $userId, 2, $order['total']);
                if (!$res) {
                    $msg = "用户【{$userId}】完成订单，订单【{$orderId}】余额冻结【{$changeAmount}】冻结金额【{$freezeBalance}】，失败！";
                    throw new \Exception($msg);
                }

                // 更新订单状态
                $res = Order::update(
                    [
                        'end_at' => time(),
                        'close_at' => time(),
                        'status' => $completeStatus,
                    ],
                    [
                        'id' => $orderId,
                        'status' => CT::O_S_PENDING_PAYMENT,
                    ]
                );
                if (!$res) {
                    $msg = "用户【{$userId}】完成订单，订单【{$orderId}】订单完成更新，失败！";
                    throw new \Exception($msg);
                }

                $update ['deal_status'] = CT::D_S_STOP;
                $update ['deal_time'] = CT::STATUS_DISABLE;

                $order_number = $user ['order_number'];
                $order_number++;
                $update ['order_number'] = $order_number;

                $order_model_list = OrderModelList::where(['model_id' => $order_child_model[$user['order_child_model_number']]['id'], 'status' => 1])->select();

                if ($order_number >= count($order_model_list)) {

                    //进下一个抢单子模板
                    $order_child_model_number = $user ['order_child_model_number'];
                    $order_child_model_number++;
                    $update ['order_child_model_number'] = $order_child_model_number;
                    $update ['order_number'] = 0;
                    // 最后一笔订单 订单完成 冻结返回余额 & 返佣上级 & 用户升级
                    $where = [['uid', '=', $userId], ['c_status', '=', CT::C_S_NOT_ISSUED], ['status', 'in', [CT::O_S_PENDING_PAYMENT, CT::O_S_COMPLETE]]];
                    $pendingOrders = Order::where($where)->select()->toArray();;
                    foreach ($pendingOrders as $pendingOrder) {
                        // 用户当前等级订单结算 & 返佣上级
                        $this->dealReward($user, $pendingOrder);
                        if ($pendingOrder['status'] == CT::O_S_PENDING_PAYMENT) {
                            $data = [
                                'end_at' => time(), //最后一笔订单的完成状态
                                'close_at' => time(),
                                'status' => $completeStatus,
                                'c_status' => CT::C_S_ISSUED,
                            ];
                        } else {
                            $data = [
                                // 'end_at'   => time(), //不要动老订单的完成状态
                                'close_at' => time(),
                                'status' => $completeStatus,
                                'c_status' => CT::C_S_ISSUED,
                            ];

                        }
                        // 更新订单状态
                        $res = Order::where(['id' => $pendingOrder['id'], 'status' => $pendingOrder['status']])->update($data);
                        if (!$res) {
                            $msg = "用户【{$userId}】完成订单，订单【{$pendingOrder['id']}】订单完成更新，失败！";
                            throw new \Exception($msg);
                        }
                    }
                }

                $res = User::where('id', $userId)->update($update);
                if (!$res) {
                    $msg = "用户【{$userId}】账户交易状态改为停止交易，失败！";
                    throw new \Exception($msg);
                }
                Db::commit();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                throw new \Exception($e->getMessage());
            }
        } else {
//            if ($user['level'] == CT::STATUS_ENABLE) {
//                return $this->firstLevelCompleteOrder($user, $order, $completeStatus);
//            } else {

            return $this->otherLevelCompleteOrder($user, $order, $completeStatus);
//            }
        }
    }

    /**
     * 会员等级第一级完成订单
     */
    private function firstLevelCompleteOrder($user, $order, $completeStatus)
    {
        $userId = $user['id'];
        $orderId = $order['id'];
        $commissionAmount = $order['commission'];

        Db::startTrans();
        try {
            // 记录两条冻结&返冻变动日志
            (new BalanceLogService())->record($userId, $order['total'], CT::B_L_T_TRADE, CT::B_L_S_EXPENSES, ["oid" => $order['id']]);
            (new CnmService())->order($orderId, $userId, 2, $order['total']);

            (new BalanceLogService())->record($userId, $order['total'], CT::B_L_T_TRADE, CT::B_L_S_INCOME, ["oid" => $order['id']]);
            (new CnmService())->order($orderId, $userId, 1, $order['total']);

            // 加佣金
            $res = (new WalletService())->balanceChange($user['id'], $commissionAmount, CT::B_L_T_REBATE, ["oid" => $order['id']]);
            (new CnmService())->yongjin($orderId, $userId, $commissionAmount);

            if (!$res) {
                $msg = "用户【{$userId}】初始等级完成订单，订单【{$orderId}】完成返佣更新【{$commissionAmount}】，失败！";
                throw new \Exception($msg);
            }

            // 更新订单状态
            $res = Order::where(['id' => $orderId, 'status' => CT::O_S_PENDING_PAYMENT])->update(
                [
                    'end_at' => time(),
                    'close_at' => time(),
                    'status' => $completeStatus,
                    'c_status' => CT::C_S_ISSUED,
                ]
            );
            if (!$res) {
                $msg = "用户【{$userId}】完成订单，订单【{$orderId}】订单完成更新，失败！";
                throw new \Exception($msg);
            }

            // 记录订单返佣
            LogReward::insert(['oid' => $orderId, 'uid' => $userId, 'num' => $commissionAmount, 'create_at' => time(), 'type' => CT::R_L_S_ISSUED]);

            // 用户交易状态更新 & 升级
            $res = User::where('id', $userId)->update([
                'deal_status' => CT::D_S_STOP,
                'deal_time' => CT::STATUS_DISABLE,
                'level' => Db::raw('level+1'),
            ]);
            if (!$res) {
                $msg = "用户【{$userId}】账户交易状态改为停止交易，失败！";
                throw new \Exception($msg);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 会员其他等级完成订单
     */
    private function otherLevelCompleteOrder($user, $order, $completeStatus)
    {
        $userId = $user['id'];
        $orderId = $order['id'];

        $level = UserLevel::where('level', $user['level'])->find();
       
        if ($level->isEmpty()) {
            throw new \Exception('user level error, please contact customer service');
        }

        Db::startTrans();
        try {
            $today_order_number = $user ['today_order_number'];
            // 获取最后交易完成的订单
            $lastPayOrder = Order::where(['uid' => $userId, 'status' => 1])->order('close_at', 'desc')->find();

            $lastTime = 0;
            if ($lastPayOrder) {
                //订单最后完成时间
                $lastTime = date('j', $lastPayOrder ['close_at']);
            }

            //当前时间
            $currentTime = date('j', time());
            if ($this->verifyLastOrder($user, $level)) {

                // var_dump(1111);
                // 最后一笔订单 订单完成 冻结返回余额 & 返佣上级 & 用户升级
//                $where = [['uid', '=', $userId], ['level', '=', $user['level']], ['status', 'in', [CT::O_S_PENDING_PAYMENT, CT::O_S_FREEZE]]];
                $where = [['uid', '=', $userId], ['status', 'in', [CT::O_S_PENDING_PAYMENT, CT::O_S_FREEZE]]];

                $pendingOrders = Order::where($where)->select();
                if (count($pendingOrders) == 1) {
                    $today_order_number++;
//                    if ($currentTime != $lastTime) {
//                        $today_order_number = 1;
//                    }
                }
                foreach ($pendingOrders as $pendingOrder) {
                    // 用户当前等级订单结算 & 返佣上级
                    $this->dealReward($user, $pendingOrder);
                    if ($pendingOrder['status'] == CT::O_S_PENDING_PAYMENT) {
                        $data = [
                            'end_at' => time(), //最后一笔订单的完成状态
                            'close_at' => time(),
                            'status' => $completeStatus,
                            'c_status' => CT::C_S_ISSUED,
                        ];
                    } else {
                        $data = [
                            // 'end_at'   => time(), //不要动老订单的完成状态
                            'close_at' => time(),
                            'status' => $completeStatus,
                            'c_status' => CT::C_S_ISSUED,
                        ];
                    }
                    // 更新订单状态
                    $res = Order::where(['id' => $pendingOrder['id'], 'status' => $pendingOrder['status']])->update($data);
                    if (!$res) {
                        $msg = "用户【{$userId}】完成订单，订单【{$pendingOrder['id']}】订单完成更新，失败！";
                        throw new \Exception($msg);
                    }
                }

            } else {
                // 非最后订单 订单冻结
                $res = Order::where(['id' => $orderId])->update(['status' => CT::O_S_FREEZE, 'end_at' => time()]);
                if (!$res) {
                    $msg = "用户【{$userId}】完成订单，订单【{$orderId}】订单冻结更新，失败！";
                    throw new \Exception($msg);
                }
                $today_order_number++;
//                if ($currentTime != $lastTime) {
//                    $today_order_number = 1;
//                }

                // 用户 冻结本金+佣金
                $changeAmount = $order['total'] * -1;
                $freezeBalance = bcadd($order['total'], $order['commission'], 2);
                $res = (new WalletService())->balanceChange(
                    $userId,
                    $changeAmount,
                    CT::B_L_T_TRADE,
                    [
                        "oid" => $orderId,
                        "freeze_balance" => $freezeBalance,
                    ]
                );
                (new CnmService())->order($orderId, $userId, 2, $order['total']);
                if (!$res) {
                    $msg = "用户【{$userId}】完成订单，订单【{$orderId}】余额冻结【{$changeAmount}】冻结金额【{$freezeBalance}】，失败！";
                    throw new \Exception($msg);
                }

//                // 用户交易状态更新
//                $res = User::where('id', $userId)->update([
//                    'deal_status' => CT::D_S_STOP,
//                    'deal_time' => CT::STATUS_DISABLE,
//                ]);
//                if (!$res) {
//                    $msg = "用户【{$userId}】账户交易状态改为停止交易，失败！";
//                    throw new \Exception($msg);
//                }
            }

            // 用户交易状态更新 & 升级
            $res = User::where('id', $userId)->update([
                'deal_status' => CT::D_S_STOP,
                'deal_time' => CT::STATUS_DISABLE,
                'today_order_number' => $today_order_number,
                'today_order_update_time' => time(),
//                    'level' => Db::raw('level+1'),
            ]);


            if (!$res) {
                $msg = "用户【{$userId}】账户交易状态改为停止交易，失败！";
                throw new \Exception($msg);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 验证当前订单是否是用户最后一笔待完成订单
     */
    public function verifyLastOrder($user, $level)
    {
        return true;
        $levelOrderNum = $level['order_num'];
        $where = [
            ['uid', '=', $user['id']],
            ['level', '=', $user['level']],
            ['status', 'in', [CT::O_S_COMPLETE, CT::O_S_FREEZE]],
        ];

        $count = Order::where($where)->count();
        if ($count == $levelOrderNum - 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 交易结算并返佣
     */
    public function dealReward($user, $order)
    {
        $userId = $user['id'];
        $orderId = $order['id'];
        $orderAmount = $order['total'];
        $commissionAmount = $order['commission'];
       
        
        if (empty($commissionAmount)) {
            return true;
        }

        if ($order['status'] == CT::O_S_PENDING_PAYMENT) {
            // 等待完成单 返冻结佣金

            // 记录两条冻结&返冻变动日志
//            (new BalanceLogService())->record($userId, $orderAmount, CT::B_L_T_TRADE, CT::B_L_S_EXPENSES, ["oid" => $orderId]);
//            (new CnmService())->order($orderId, $userId, 2, $orderAmount);
//            (new BalanceLogService())->record($userId, $orderAmount, CT::B_L_T_TRADE, CT::B_L_S_INCOME, ["oid" => $orderId]);
//            (new CnmService())->order($orderId, $userId, 1, $orderAmount);


            // 增加返佣记录
            $commissionLogData = ["oid" => $orderId];
            $res = (new WalletService())->balanceChange($userId, $commissionAmount, CT::B_L_T_REBATE, $commissionLogData);
            (new CnmService())->yongjin($orderId, $userId, $commissionAmount);
            //解决BUG，如果佣金小于
            
            
            //查询用户上级
            if ($user ['parent_id']) {
                $parent = User::get($user->parent_id);
                if ($parent) {
                    // 查询上级等级信息
                    $parent_level = UserLevel::get($parent->level);
                    if ($parent_level) {
                        if($parent_level ['commission'] != 0){
                            //返佣比列不为0 计算返佣金额
                            if (isset($parent_level ['commission']) && $parent_level ['commission']) {
                                $parent_commission_amount = bcmul($commissionAmount, $parent_level ['commission'], 5);
                                if ($parent_commission_amount&&$parent_commission_amount>0.01) {
                                    // 增加返佣记录
                                    $res = (new WalletService())->balanceChange($parent->id, $parent_commission_amount, CT::B_L_T_REBATE, $commissionLogData);
                                    (new CnmService())->yongjin($orderId, $parent->id, $parent_commission_amount, 7);
                                }
                            }
                        }
                       
                    }
                }
            }

            if (!$res) {
                $msg = "用户【{$userId}】完成订单，订单【{$orderId}】完成返佣更新【{$commissionAmount}】，失败！";
                throw new \Exception($msg);
            }
        } else {
            // 冻结单 返冻结佣金&订单总额

            $balanceLogData = ["oid" => $orderId, "freeze_balance" => $orderAmount * -1];
            $res = (new WalletService())->balanceChange($userId, $orderAmount, CT::B_L_T_TRADE, $balanceLogData);
            (new CnmService())->order($orderId, $userId, 1, $orderAmount);
            if (!$res) {
                $msg = "用户【{$userId}】完成订单，订单【{$orderId}】返还订单金额【{$orderAmount}】，失败！";
                throw new \Exception($msg);
            }

            // 增加返佣记录
            $commissionLogData = ["oid" => $orderId, "freeze_balance" => $commissionAmount * -1];
            $res = (new WalletService())->balanceChange($userId, $commissionAmount, CT::B_L_T_REBATE, $commissionLogData);
            (new CnmService())->yongjin($orderId, $userId, $commissionAmount);
            if (!$res) {
                $msg = "用户【{$userId}】完成订单，订单【{$orderId}】完成返佣更新【{$commissionAmount}】，失败！";
                throw new \Exception($msg);
            }
        }

        // 记录订单返佣
        LogReward::insert(['oid' => $orderId, 'uid' => $userId, 'num' => $commissionAmount, 'create_at' => time(), 'type' => CT::R_L_S_ISSUED]);

        return true;
    }

    /**
     * 处理抢单页面的订单数据
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function rotOrderDataOfIndex($uid)
    {
        $where = [
            ['uid', '=', session('user_id')],
            ['create_at', 'between', strtotime(date('Y-m-d')) . ',' . time()],
        ];

        $yes1 = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $yes2 = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));

        $day_deal = Order::where($where)->whereIn('status', [1, 3, 5])->sum('commission');
        $day_deal = round($day_deal, 2);
        $day_d_count = Order::where($where)->where('is_deleted', 0)->whereIn('status', [0, 1, 3, 5])->count();
        $yes_user_yongjin = Order::where(['uid' => $uid, 'status' => 1])->where('create_at', 'between', [$yes1, $yes2])->sum('commission');
        $user_yongjin = Order::where(['uid' => $uid, 'status' => 1])->sum('commission');
        $lv3lock = Order::where(['uid' => $uid, 'level' => 3, 'is_deleted' => 0])->count();
        $lv3first = 0;

        return ['day_deal' => $day_deal, 'day_d_count' => $day_d_count, 'yes_user_yongjin' => $yes_user_yongjin, 'user_yongjin' => $user_yongjin, 'lv3lock' => $lv3lock, 'lv3first' => $lv3first];
    }

    public function submit_lv3($uid)
    {
        $lv3lock = Order::where(['uid' => $uid, 'level' => 3, 'is_deleted' => 0])->count();
        if ($lv3lock != 0) {
            return false;
        }

        $user = User::where('id', $uid)->find();
        $orderId = getSn('UB');
        $goodsId = input('gid');
        $goods = Goods::where(['id' => $goodsId, 'shop_name' => '120'])->find();
        $goodsTotal = bcmul($goods['goods_price'], 1, 2);
        $commission = bcmul($goodsTotal, $goods['goods_info'], 2);

        Order::insert([
            'id' => $orderId,
            'uid' => $uid,
            'goods_id' => $goodsId,
            'goods_price' => $goods['goods_price'],
            'level' => $user['level'],
            'num' => 1,
            'total' => $goodsTotal,
            'commission' => $commission,
            'status' => -1,
            'c_status' => 0,
            'assign_at' => time(),
            'create_at' => time(),
        ]);

        return $orderId;
    }

    /**
     * 后台调用：手动解冻
     */
    public function unfreeze($orderId)
    {
        $order = Order::findOrEmpty($orderId);
        if ($order->isEmpty()) {
            throw new \Exception('order does not exist');
        }
        if ($order['status'] != CT::O_S_FREEZE) {
            throw new \Exception('order status is wrong');
        }

        $userId = $order['uid'];
        Db::startTrans();
        try {
            //获取订单有效时间
            $order_effective_time = \app\common\model\Config::where('name', 'order_effective_time')->value('value');

            // 订单状态
            $res = Order::where(['id' => $orderId])->update([
                'end_at' => 0,
                'close_at' => time() + (intval($order_effective_time) * 60),
                'status' => CT::O_S_PENDING_PAYMENT,
                'c_status' => CT::C_S_NOT_ISSUED,
            ]);
            if (!$res) {
                $msg = "后台手动解冻：订单【{$order['id']}】订单状态更新为已完成，失败！";
                throw new \Exception($msg);
            }

//            // 返冻结佣金&订单总额
//            $orderAmount = $order['total'];
//            $balanceLogData = ["oid" => $orderId, "freeze_balance" => $orderAmount * -1];
//            $res = (new WalletService())->balanceChange($userId, $orderAmount, CT::B_L_T_TRADE, $balanceLogData);
//            if (!$res) {
//                $msg = "后台手动解冻：用户【{$userId}】完成订单，订单【{$orderId}】返还订单金额【{$orderAmount}】，失败！";
//                throw new \Exception($msg);
//            }
//
//            // 增加返佣记录
//            $commissionAmount = $order['commission'];
//            $commissionLogData = ["oid" => $orderId, "freeze_balance" => $commissionAmount * -1];
//            $res = (new WalletService())->balanceChange($userId, $commissionAmount, CT::B_L_T_REBATE, $commissionLogData);
//            if (!$res) {
//                $msg = "后台手动解冻：用户【{$userId}】完成订单，订单【{$orderId}】完成返佣更新【{$commissionAmount}】，失败！";
//                throw new \Exception($msg);
//            }
//
//            // 记录订单返佣
//            LogReward::insert(['oid' => $orderId, 'uid' => $userId, 'num' => $commissionAmount, 'create_at' => time(), 'type' => CT::R_L_S_ISSUED]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }

    }

}