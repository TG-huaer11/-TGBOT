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
class Kd extends Backend
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





   public function indexx()
    {
        //设置过滤方法
      
           $uid=0;
   

            $list = $this->model
            
                 ->where(['uid'=>110])
                
                ->select();
print_r($list);
      $this->assign('list',$list);

 
        return $this->view->fetch();
    }






    public function index()
    {
        //设置过滤方法
      
           $uid=0;
   
        
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            
                 [$where, $sort, $order, $offset, $limit] = $this->buildparams();
                 $pr=$this->request->get('filter');
              
    
    
      // $uid=110;
            $total = $this->model
                ->where($where)
                   ->where(['uid'=>$uid])
                ->order($sort, $order)
                ->count();

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
        if ($this->request->isPost()) {
              $params = $this->request->post('row/a');
            if (empty($params)) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
              // $this->error('用户名格式错误');
               $type =$params['type'];
               $day=$params['day'];
               $income=$params['income'];
               $amount=$params['amount'];
               if(strpos($amount,"-")){
                  $amount0 = explode("-", $amount);  
                  $amount=$this->random_float($amount0[0],$amount0[1]);
                   $amount=sprintf("%.2f", $amount);
               }
                 
          //    $this->error($amount);
        $c=  $this->model->where(['day'=>$day,'uid'=>0,'type'=>$type,'num'=>$params['num']])->count();
        if($c>0){
             $this->error('卡单数据已存在');
        }
             $params['amount'] = $amount;
                     $params['create_time'] = time();
                           $params['update_time'] = time();
                 $res = $this->model->insert($params);  
              
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
