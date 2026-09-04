<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use \app\common\model\UserLevel as UserLevelModel;

/**
 * 会员等级管理.
 *
 * @icon fa fa-user
 */
class UserLevel extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserLevelModel();
    }

}
