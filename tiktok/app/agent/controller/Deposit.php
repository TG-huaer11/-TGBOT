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

use app\common\model\Admin;
use app\common\model\AdminTotp;
use app\common\model\BankInfo;
use app\common\model\BankList;
use app\common\model\PayOut;
use app\common\model\Deposit as DepositModel;
use app\common\model\User;
use app\common\controller\Backend;
use app\common\service\CnmService;
use app\common\service\UserService;
use OTPHP\TOTP;
use think\facade\Db;

/**
 * 取款管理
 *
 * @internal
 */
class Deposit extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new DepositModel();
    }

    /**
     * 提款列表
     */
    public function index()
    {
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $parent = '';

            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['parent.username'])) {
                $parent = $filter['parent.username'];
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['parent.username']);

            $childWhere = [];
            if (!empty($parent)) {
                //查询代理
                $self = $this->model->where('username', $parent)->find()->toArray();
                $childWhere['user.parent_id'] = $self['id'];
            }

            if(!$this->isSuperAdmin) {
                // 查询userid
                $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
                $childWhere[] = ['uid', 'IN', $user_id];
            }

            $total = $this->model
                ->with(['user', 'bank', 'pay'])
                ->where($where)
                ->where($childWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['user', 'bank', 'pay'])
                ->where($where)
                ->where($childWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                if (empty($item ['user']['parent_id'])) {
                    continue;
                }
                //查询父级
                $item ['parent.username'] = User::where('id', $item['user']['parent_id'])->value('username');

                $item ['bank_list'] = '';
                if (!empty($item ['bank'])) {
                    if (isset($item ['bank']['bankname'])) {
                        $item ['bank_list'] = BankList::where('number', $item ['bank']['bankname'])->find();
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 提款订单操作
     */
    public function edit($ids = null, $status = null)
    {
        if ($this->request->isPost()) {

            $info = $this->model->where('id', $ids)->find();

            if ($status != 8) {
                if ($status == 33) {
                    //检查状态是否为1处理中
                    if ($info ['status'] != 4) {
                        $this->error('订单状态错误');
                    }
                } else {
                    //检查状态是否为1处理中
                    if ($info ['status'] != 1) {
                        $this->error('订单状态错误');
                    }
                }

            }

            $params = $this->request->post('row/a');

            $result = false;
            $msg = '';
            switch ($status) {
                case 2:
                    // 人工出款
//                    $totp = $params ['totp'];
                    $remark = $params ['remark'];

//                    $totp_secret = Admin::where('id', $this->auth->id)->value('totp_secret');
//                    $secret = AdminTotp::where('id', $totp_secret)->value('secret');
//                    $otp = TOTP::create($secret);
//                    $totp_res = $otp->verify($totp);
                    $totp_res = true;
                    if ($totp_res) {
//                        if ($info ['status'] !== 4) {
//                            $this->error('订单状态错误');
//                        }
                        // 更新状态: 提款成功
                        $db_res = Db::name('deposit')
                            ->where('id', $ids)
                            ->update([
                                'status' => 2,
                                'end_at' => time(),
                                'type' => 'system',
                                'adminer' => $this->auth->id,
                                'remark' => $remark,
                            ]);
                        if ($db_res) {
                            // 发送短消息
                            (new CnmService())->despoit($ids, $info['uid'], 2, $info['num'], $remark, '', $this->auth->username);
                            (new UserService())->sendMsg($info['uid'], $this->auth->id, 'Withdrawal Notice', $remark, 1);
                            // 成功
                            $result = true;
                        } else {
                            // 失败
                            $msg = __('Operation failed');
                        }
                    } else {
                        // 失败
                        $msg = __('Operation failed');
                    }
                    break;
                case 3:
                    // 驳回订单
                    $remark = $this->request->post('remark', '');
                    $withdraw_time = $this->request->post('withdraw_time', 0);
                    //事务开始
                    Db::startTrans();
                    try {
                        // 退回余额
                        $balance_now = User::where('id', $info['uid'])->value('balance');                      // 当前余额
                        $balance_new = bcadd($balance_now, $info['num'], 2);                                               // 退回余额
                        $balance_res = User::where('id', $info['uid'])->update(['balance' => $balance_new]);   // 更新数据

                        (new CnmService())->despoit($ids, $info['uid'], 1, $info['num'], $remark, '', $this->auth->username);

                        // 修改状态: 提款失败
                        $db_status = $this->model
                            ->where('id', $ids)
                            ->update([
                                'status' => 3,               // 状态3: 提款失败
                                'end_at' => time(),          // 时间戳
                                'adminer' => $this->auth->id,     // 操作员
                                'remark' => $remark,         // 备注
                            ]);

                        // 修改禁止提款时间
                        $bantimes = User::where('id', $info['uid'])
                            ->update([
                                'withdraw_time' => time(),               // 时间戳
                                'withdraw_long' => $withdraw_time * 60,  // 秒, 实际单位: 分钟
                            ]);

                        // 状态检查
                        if ($balance_res && $db_status && $bantimes) {
                            // 成功, 提交事务
                            Db::commit();
                            (new UserService())->sendMsg($info['uid'], $this->auth->id, 'Withdrawal Notice', $remark, 1); // 发送短消息
                            $result = true;
                        } else {
                            // 失败, 事务回滚
                            Db::rollback();
                            $msg = __('Operation failed');
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    break;
                case 4:
                    // 三方出款

                    // 检查金额限制
                    $payment = PayOut::where('name2', $params ['pay'])->find();
                    if ($info['real_num'] < $payment['min']) {
                        $msg = '提现金额小于当前选择金额的最小限额';
                        break;
                    }

                    if ($info['real_num'] > $payment['max']) {
                        $msg = '提现金额大于当前选择金额的最大限额';
                        break;
                    }
                    Db::startTrans();
                    try {
                        // 修改状态 出款中
                        $res = $this->model
                            ->where('id', $ids)
                            ->update([
                                'status' => 4,
                                'end_at' => time(),
                                'type' => $params ['pay'],
                                'adminer' => $this->auth->id,
                                'fee' => $payment['fee'],
                                'gfee' => $payment['gfee'],
                            ]);

                        // 修改成功
                        if ($res) {

                            // 二次查询状态
                            $db_check = $this->model
                                ->where('id', $ids)
                                ->field('status')
                                ->find();

                            if ($db_check && isset($db_check['status']) && $db_check['status'] == 4) {

                                (new CnmService())->despoit($info['id'], $info['uid'], 2, $info['num'], $params ['pay'], '', $this->auth->username);

                                // 发送订单
                                $bank_info = BankInfo::where('uid', $info['uid'])->find();
                                $class = '\app\pay\\' . $params ['pay'];

                                //判断金流是否接入
                                if (!class_exists($class)) {
                                    $msg = '未接入' . $params ['pay'] . '金流';
                                    Db::rollback();
                                    break;
                                }

                                $payClass = new $class();

                                //判断金流类是否接入了出金接口
                                if (!method_exists($payClass, 'withdraw')) {
                                    $payResult = [
                                        'res' => false,
                                        'data' => "The channel not support query!"
                                    ];
                                } else {
                                    $params = [
                                        'order_sn' => $info ['id'],
                                        'amount' => $info ['real_num'],
                                        'name' => $bank_info ['username'],
                                        'account' => $bank_info ['cardnum'],
                                        'ifsc' => $bank_info ['ifsc'],
                                        'country' => ''
                                    ];
                                    $pay = PayOut::where('name2', $params ['pay'])->find();
                                    $payResult = $payClass->withdraw($params, $pay);
                                }
                                // 记录下日志
                                $this->model
                                    ->where('id', $ids)
                                    ->update([
                                        'pay_log' => json_encode($payResult['data'])
                                    ]);

                                if ($payResult['res'] == true) {
                                    $result = true;
                                    Db::commit();
                                } else {
                                    // 失败
                                    $msg = $payResult['data'];
                                    Db::rollback();
                                }
                            } else {
                                // 失败
                                $msg = __('Operation failed');
                                Db::rollback();
                            }
                        } else {
                            // 失败
                            $msg = __('Operation failed');
                            Db::rollback();
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    break;
                case 8:
                    // 查询三方
                    $pay = PayOut::where('name2', $info['type'])->find()->toArray();
//                    $pay ['connection'] = ;

                    $class = '\app\pay\\' . $info['type'];

                    //判断金流是否接入
                    if (!class_exists($class)) {
                        $msg = '未接入' . $info ['pay'] . '金流';
                        break;
                    }

                    $payClass = new $class();

                    //判断金流类是否接入了出金接口
                    if (!method_exists($payClass, 'query_payout')) {
                        $payResult = [
                            'res' => false,
                            'data' => "The channel not support query!"
                        ];
                    } else {
                        $payResult = $payClass->query_payout($info['id'], $pay);
                    }
                    $msg = $payResult ['data'];
                    // 结果判断
                    if ($payResult['res'] == true) {
                        // 成功
                        $result = true;
                    }
                    break;
                case 33:
                    // 驳回订单(出款中驳回)
                    $remark = $params ['remark'];
                    $withdraw_time = $params ['withdraw_time'];
                    //事务开始
                    Db::startTrans();
                    try {
                        // 退回余额

                        // 当前余额
                        $balance_now = User::where('id', $info['uid'])->value('balance');
                        $balance_new = bcadd($balance_now, $info['num'], 2);
                        // 更新数据// 退回余额
                        $balance_res = User::where('id', $info['uid'])->update(['balance' => $balance_new]);

                        (new CnmService())->despoit($ids, $info['uid'], 1, $info['num'], $remark, '', $this->auth->username);

                        // 修改状态: 提款失败
                        $db_status = $this->model
                            ->where('id', $ids)
                            ->update([
                                'status' => 3,               // 状态3: 提款失败
                                'end_at' => time(),          // 时间戳
                                'adminer' => $this->auth->id,     // 操作员
                                'remark' => $remark,         // 备注
                            ]);

                        // 修改禁止提款时间
                        $bantimes = User::where('id', $info['uid'])
                            ->update([
                                'withdraw_time' => time(),               // 时间戳
                                'withdraw_long' => $withdraw_time * 60,  // 秒, 实际单位: 分钟
                            ]);

                        // 状态检查
                        if ($balance_res && $db_status && $bantimes) {
                            // 成功, 提交事务
                            Db::commit();
                            (new UserService())->sendMsg($info['uid'], $this->auth->id, 'Withdrawal Notice', $remark, 1); // 发送短消息
                            $result = true;
                        } else {
                            // 失败, 事务回滚
                            Db::rollback();
                            $msg = __('Operation failed');
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    break;
                default:
                    $msg = __('Operation failed');
                    break;
            }

            if ($result) {
                $this->success();
            } else {
                $this->error($msg);
            }

        }

        $row = $this->model->where('id', $ids)->find();
        $row->address_info = json_decode($row->address_info, true);
        $this->view->assign('row', $row);
        $this->view->assign('status', $status);

        return $this->view->fetch();
    }


}
