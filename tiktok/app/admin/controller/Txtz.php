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
class Txtz extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Txtz();
    
         $this->view->assign('lang', $this->model->lang());
    }
     public  function random_float($min, $max) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}









    public function index()
    {
        //设置过滤方法
      
          
   

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
             
                ->order($sort, $order)
                ->count();
//file_put_contents('11.txt',$uid);
            $list = $this->model
               ->where($where)
         
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list   = $list->toArray();

       

            $result = ['total' => $total, 'rows' => $list];

            return json($result);
            
        }

 
        return $this->view->fetch();
    }
 

     public function add()
    {
       
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
