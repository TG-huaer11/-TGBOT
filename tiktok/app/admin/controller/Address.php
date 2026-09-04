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
class Address extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Address();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('statusList2', $this->model->getStatusList2());
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
               $address=$params['address'];
               if($type=='1'&&$address){
                   $address = explode("\n", $address);
                   foreach ($address as $v){
                           $data['name'] = $params['name'];
                           $data['type'] = 1;
                           $data['status'] = $params['status'];
                           $data['address'] = $v;
                           $data['create_time'] = time();
                           $data['update_time'] = time();
            $res = $this->model->insert($data);
                   }
               }
               else{
                     $params['create_time'] = time();
                           $params['update_time'] = time();
                 $res = $this->model->insert($params);  
               }
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
