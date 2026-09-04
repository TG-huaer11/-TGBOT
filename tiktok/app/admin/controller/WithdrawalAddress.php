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

use app\admin\model\User;
use app\common\controller\Backend;
use think\facade\Db;

/**
 * 首页配置
 *
 * @internal
 */
class WithdrawalAddress extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\WithdrawalAddress();
    }

    /**
     * 查看
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
           
              $filter = json_decode($this->request->get('filter'), true);
               $otherWhere  = [];
               $top_id=$this->request->get('top_id',0);
            $member_id=$this->request->get('member_id',0);
            $username=$this->request->get('username',0);
            $jia=$this->request->get('jia','all');
          if($top_id||$member_id||$username||$jia!=''){
             [$where, $sort, $order, $offset, $limit] = $this->buildparams(); 
              $where = [];
if ($member_id!= 0) {
    $where['user_id'] = $member_id;
}
if ($username!= '') {
    $user_id=Db::name('user')->where(['username'=>$username])->value('id');
    $where['user_id'] = $user_id;
}
if ($jia!= 'all') {
     
          $idArray =Db::name('user')->where(['jia'=>$jia])->column('id');
   
      $where[] = ['user_id', 'IN', $idArray];
}

if ($top_id) {
  $idArray2 = User::where('top_parent', $top_id)->column('id');
  
 $where[] = ['user_id', 'IN', $idArray2];
}
          }else{
            [$where, $sort, $order, $offset, $limit] = $this->buildparams(); 
          }
                  
        
            
           
file_put_contents('123.txt',json_encode($where));
           

            if(!$this->isSuperAdmin) {
                // 查询userid
                $user_id = User::where('top_parent', $this->auth->user_id)->column('id');
                $otherWhere[] = ['user_id', 'IN', $user_id];
            }


            $total = $this->model
                ->with(['user'])
                ->where($where)
                ->where($otherWhere)
      
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['user'])
                ->where($where)
                ->where($otherWhere)
          
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list   = $list->toArray();
            
               if ($list) {
                foreach ($list as &$item) {
                    $pp=Db::name('user')->where(['id'=>$item['user_id']])->value('top_parent');
                     $jia=Db::name('user')->where(['id'=>$item['user_id']])->value('jia');
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
            }
            
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = '')
    {
        if ($ids) {
            $pk       = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $user_id = $this->request->get('user_id');
            if (empty($user_id)) {
                $list = $this->model->where($pk, 'in', $ids)->select();
            } else {
                $list = $this->model->where('user_id', 'in', $user_id)->select();
            }

            $count = 0;
            Db::startTrans();

            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                Db::commit();
            } catch (\PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
