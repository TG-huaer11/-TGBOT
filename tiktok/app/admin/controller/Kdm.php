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

use app\common\controller\Backend;

/**
 * 首页配置
 *
 * @internal
 */
class Kdm extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Kd();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('type', $this->model->getType());
         $this->view->assign('day', $this->model->getdays());
    }
     public  function random_float($min, $max) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}









    public function index()
    {
        //设置过滤方法
      
           $uid=$this->request->request('uid');
         
        if($uid){
        session('uuid',$uid);
        }
   

        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            
                 [$where, $sort, $order, $offset, $limit] = $this->buildparams();
                 $pr=$this->request->get('filter');
              
    $uid= session('uuid');
    
      // $uid=110;
            $total = $this->model
               ->where($where)
                   ->where(['uid'=>$uid])
                ->order($sort, $order)
                ->count();
//file_put_contents('11.txt',$uid);
            $list = $this->model
               ->where($where)
                 ->where(['uid'=>$uid])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list   = $list->toArray();

            foreach ($list as &$item) {
            //    $item ['fee'] = $item ['gfee'] . '+' . $item ['fee'] . '%';
            }

            $result = ['total' => $total, 'rows' => $list];

            return json($result);
            
        }

 
        return $this->view->fetch();
    }
 

     public function add()
    {
         $uid= session('uuid');
           $this->view->assign('uid', $uid);
        if ($this->request->isPost()) {
            
              $params = $this->request->post('row/a');
          /*    $params['type']=1;
              $params['day']=3;
              $params['income']=4;
              $params['amount']=5;
              $params['num']=5;*/
            if (empty($params)) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
              // $this->error('用户名格式错误');
               $type =1;
               $day=$params['day'];
               $incomes=$params['income'];
               $amounts=$params['amount'];
               $nums=$params['num'];
      // 处理数据
    foreach ($nums as $key => $num) {
        $amount = $amounts[$key];
        $income = $incomes[$key];
         $num0 = $nums[$key];
         if($num0==0){
            continue; 
         }
        // 这里可以将数据保存到数据库或其他处理
      //  echo "第几单: $num, 金额: $amount, 佣金比例: $income<br>";
       $c=  $this->model->where(['day'=>$day,'uid'=>$params['uid'],'type'=>$type,'num'=>$num0])->count();
        if($c>0){
             continue;
        }
             $params['day']=$day;
              $params['num']=$num0;
              $params['income']=$income;
             $params['amount'] = $amount;
                     $params['create_time'] = time();
                           $params['update_time'] = time();
                 $res = $this->model->insert($params);  
        
    }
       
      //   $this->error('卡单数据已存在'.$params['uid']);
        
              
               if ($res) {
                $this->success();
            } else {
                $this->error();
            }
        }else{
              return $this->view->fetch();
        }
        
    }
}
