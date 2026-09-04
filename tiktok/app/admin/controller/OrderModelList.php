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
use think\facade\Db;

/**
 * 抢单模板管理
 *
 * @internal
 */
class OrderModelList extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\OrderModelList();

        $model_id = $this->request->get('model_id', 0);
        $this->view->assign('model_id', $model_id);
        $this->view->assign('statusList', $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter', '{}'), true);

            if (!isset($filter ['model_id'])) {
                return json([]);
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->with(['ordermodel'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['ordermodel'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

}
