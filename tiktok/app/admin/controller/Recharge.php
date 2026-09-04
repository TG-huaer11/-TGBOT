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
use app\common\service\UserService;

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

 $childWhere = [];
            $filter = json_decode($this->request->get('filter'), true);
       



            if(!$this->isSuperAdmin) {
                // 查询userid
                $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
                $childWhere[] = ['uid', 'IN', $user_id];
            }
            
            
               $top_id=$this->request->get('top_id',0);
            $member_id=$this->request->get('member_id',0);
            $username=$this->request->get('username',0);
            $jia=$this->request->get('jia','all');
             $status=$this->request->get('status','0');
          if($top_id||$member_id||$username||$jia!=''){
             [$where, $sort, $order, $offset, $limit] = $this->buildparams(); 
              $where = [];
if ($member_id!= 0) {
    $where['uid'] = $member_id;
}
if ($status!= 0) {
    $where['status'] = $status;
}
if ($username!= '') {
    $user_id=Db::name('user')->where(['username'=>$username])->value('id');
    $where['uid'] = $user_id;
}
if ($jia!= 'all') {
     
          $idArray =Db::name('user')->where(['jia'=>$jia])->column('id');
   
      $where[] = ['uid', 'IN', $idArray];
}

if ($top_id) {
  $idArray2 = User::where('top_parent', $top_id)->column('id');
  
 $where[] = ['uid', 'IN', $idArray2];
}
          }else{
       [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, null, ['parent.username']);  
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
                 $pp=Db::name('user')->where(['id'=>$item['uid']])->value('top_parent');
                     $jia=Db::name('user')->where(['id'=>$item['uid']])->value('jia');
                     if($jia=='0'){
                         $jia='真人';
                     }else{
                         $jia='假人';
                     }
                    if($pp){
                        $ppname=Db::name('admin')->where(['user_id'=>$pp])->value('username');
                      $item ['top_parent_name'] = $ppname; 
                      $item ['jia'] = $jia;  
                    }
                     
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
  $remark = $this->request->post('remark', '');
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
                            'remark' => $remark
                        ]);
                          (new UserService())->sendMsg($info['uid'], $this->auth->id, 'Recharge Notice', $remark, 1); // 发送短消
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
                
                //自动升级等级
                // 计算用户累计充值金额
            $totalAmount = Db::name('recharge')
                ->where('uid', $info['uid'])
                ->where('status', 2)
                ->sum('num');

            // 查询用户等级表，找到满足条件的最高等级
            $userLevel = Db::name('user_level')
                ->where('num', '<=', $totalAmount)
                ->order('num', 'desc')
                ->find();
$user= Db::name('user')->find($info['uid']);
            if ($userLevel && $user['level'] != $userLevel['id']) {
                // 更新用户等级
                Db::name('user')
                    ->where('id', $user['id'])
                    ->update(['level' => $userLevel['id']]);
            }
                
                
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
