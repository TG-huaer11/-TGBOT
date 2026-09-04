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
 * 充值配置管理
 *
 * @internal
 */
class RechargeConfig extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RechargeConfig();

        $this->view->assign('statusList', $this->model->getStatusList());
    }

}
