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

use app\common\model\Goods as GoodeModel;
use app\common\controller\Backend;
use app\common\service\OrderService;

/**
 * 商品管理
 *
 * @internal
 */
class Goods extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new GoodeModel();
        $this->view->assign('statusList', $this->model->getStatusList());
    }

    /**
     * 指派
     */
    public function appoint($ids = '', $type = '')
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (empty($params)) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            $goodsId = $ids;
            $userId = $params ['user_id'];
            $num = $params ['number'];
            $prices = isset($params ['price']) ? $params ['price'] : 0;
            $commissions = isset($params ['commission']) ? $params ['commission'] : 0;

            $result = false;

            try {
                $conveyAssign = (new OrderService())->assign($userId, $goodsId, $num, $prices, $commissions);
                if ($conveyAssign) {
                    $result = true;
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            if ($result) {
                $this->success();
            } else {
                $this->error(__('Operation failed'));
            }
        }

        $this->view->assign('row', $row);
        $this->view->assign('type', $type);

        return $this->view->fetch();
    }
}
