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
 * 抢单模板分组管理
 *
 * @internal
 */
class OrderModel extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\OrderModel();

        $parent_id = $this->request->get('parent_id', 0);
        $is_child = 0;
        if ($parent_id) {
            $is_child = 1;
        }

        $this->view->assign('parent_id', $parent_id);
        $this->view->assign('is_child', $is_child);
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('typeList', $this->model->getTypeList());
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

            $otherWhere = [];
            if (!isset($filter ['parent_id'])) {
                if (isset($filter ['is_child']) && $filter ['is_child']) {
                    $otherWhere ['parent_id'] = -1;
                } else {
                    $otherWhere ['parent_id'] = 0;
                }
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['is_child']);
            $total = $this->model
                ->where($where)
                ->where($otherWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($otherWhere)
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
