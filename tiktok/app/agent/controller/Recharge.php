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

use app\common\constant\CT;
use app\common\model\Admin;
use app\common\model\AdminTotp;
use app\common\model\Recharge as RechargeModel;
use app\common\model\User;
use app\common\controller\Backend;
use app\common\service\CnmService;
use app\common\service\WalletService;
use OTPHP\TOTP;
use think\facade\Db;

/**
 * 提现
 *
 * @internal
 */
class Recharge extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RechargeModel();
    }

    /**
     * 充值列表
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
                ->with(['user', 'pay', 'admin'])
                ->where($where)
                ->where($childWhere)
                ->where('type','<>',2)
                ->order('create_at', $order)
                ->count();

            $list = $this->model
                ->with(['user', 'pay', 'admin'])
                ->where($where)
                ->where($childWhere)
                ->where('type','<>',2)
                ->order('create_at', $order)
                ->limit($offset, $limit)
                ->select()
                ->toArray();
            foreach ($list as &$item) {
                if (empty($item ['user']['parent_id'])) {
                    continue;
                }
                //查询父级
                $item ['parent.username'] = User::where('id', $item['user']['parent_id'])->value('username');
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 关闭订单 or 人工补单
     */
    public function edit($ids = null, $type = null)
    {
        if ($this->request->isPost()) {
 $id = $this->request->post('id', '');
  $real_num = $this->request->post('real_num', 0);
 if($id){
   $ids=$id;  
 }
            $info = $this->model->where('id', $ids)->find();
            //检查状态是否为1处理中
            if ($info['status'] != 1) {
                $this->error('订单状态错误');
            }
             // $this->error('订单状态错误22');
            $res = false;
            $res1 = false;
            $totp_res = false;
            //事务开始
            Db::startTrans();
            try {
                if ($type == 'close') {
                    //关闭订单
                    $res = $this->model
                        ->where('id', $ids)
                        ->update([
                            'end_at' => time(),
                            'status' => 3,
                            'adminer' => $this->auth->id,
                            'remark' => 'Close Order'
                        ]);
                    $res1 = true;
                } else if ($type == 'order') {
                    //人工补单
                    $remark = $this->request->post('remark', '');
                    $totp = $this->request->post('totp', '');
                    $totp_secret = Admin::where('id', $this->auth->id)->value('totp_secret');
                    $secret = AdminTotp::where('id', $totp_secret)->value('secret');
//                    $otp = TOTP::create($secret);
//                    $totp_res = $otp->verify($totp);

                    $totp_res = true;
                    if ($totp_res) {
                        $res = $this->model
                            ->where('id', $ids)
                            ->update([
                                'end_at' => time(),
                                'status' => 2,
                                'adminer' => $this->auth->id,
                                'real_num' => $real_num,
                                'remark' => $remark
                            ]);
                        $res1 = (new WalletService())->balanceChange($info['uid'], $real_num, CT::B_L_T_RECHARGE);
                        (new CnmService())->recharge($ids, $info['uid'], $real_num, $remark, '', $this->auth->username);
                    }
                } else {
                    $this->error(__('Bad request'));
                }

                if ($res && $res1) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($res && $res1) {
                $this->success();
            } else {
                $msg = '操作失败';
                if (!$totp_res) {
                    $msg = 'TOTP校验失败';
                }
                $this->error($msg);
            }
        }
        return $this->view->fetch();
    }


}
