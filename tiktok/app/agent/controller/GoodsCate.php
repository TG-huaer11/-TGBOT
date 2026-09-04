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

use app\common\model\GoodsCate as GoodsCateModel;
use app\common\controller\Backend;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 商品管理
 *
 * @internal
 */
class GoodsCate extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new GoodsCateModel();
        $this->view->assign('statusList', $this->model->getStatusList());
    }
}
