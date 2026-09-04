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

use app\common\model\Order as OrderModel;
use app\common\controller\Backend;
use app\common\constant\CT;
use app\common\service\UserService;
use app\common\service\OrderService;
use think\facade\Db;

/**
 * 订单
 *
 * @internal
 */
class Order extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new OrderModel();

        $this->view->assign('statusList', $this->model->getStatusList());
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
            // 所属代理
            $parent = '';

            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['parent.username'])) {
                $parent = $filter['parent.username'];
            }

            if ($parent) {
                $childUserIds = (new UserService())->getChildUserIdsByUsername($parent);
                if ($childUserIds) {
                    $where[] = ['uid', 'in', $childUserIds];
                }
            }

            $childWhere = [];

            if(!$this->isSuperAdmin) {
                // 查询userid
                $user_id = \app\admin\model\User::where('parent_id', $this->auth->user_id)->column('id');
                $childWhere[] = ['uid', 'IN', $user_id];
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['parent.username']);

            $total = $this->model
                ->with(['user', 'admin', 'goods'])
                ->where($where)
                ->where($childWhere)
                ->where('is_deleted', 0)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['user', 'admin', 'goods'])
                ->where($where)
                ->where($childWhere)
                ->where('is_deleted', 0)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select()
                ->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 解冻订单
     * @return void
     */
    public function unfreeze()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a', '');
            if (empty($params)) {
                $this->error(__('Parameter %s can not be empty', 'ids'));
            }
            // 验证状态
            $orders = $this->model
                ->withTrashed()
                ->whereIn('id', $params ['ids'])
                ->field('id, status, is_deleted, close_at')
                ->select();

            foreach ($orders as $order) {
                if ($order['status'] != CT::O_S_FREEZE) {
                    $this->error("order 【{$order['id']}】 status is wrong");
                }
            }

            $ids = explode(',', $params ['ids']);
            $res = CT::DEFAULT_VALUE;
            foreach ($ids as $orderId) {
                try {
                    if ((new OrderService())->unfreeze($orderId)) $res++;
                } catch(\Exception $e) {
                    $this->error($e->getMessage());
                }
            }

            if ($res) {
                $this->success();
            } else {
                $this->error(1, 'Operation failed');
            }
        }

        $this->error('bad request');
    }

    /**
     * 删除
     */
    public function del($ids = '')
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();

            try {
                foreach ($list as $k => $v) {
                    $v->is_deleted = 1;
                    $count += $v->save();
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
