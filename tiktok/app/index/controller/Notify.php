<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\facade\Request;

class Notify extends Frontend
{
    protected $noNeedRight = '*';
    protected $noNeedLogin = '*';

    public function recharge()
    {
        $pay = Request::instance()->param('pay');
        if($pay) {
            $class = "\app\pay\\" . $pay;
            $payClass = new $class();
            $params = Request::instance()->param();
//            $params ['connection'] = $this->connection;
            return $payClass->notify_recharge($params);
        }
        return false;
    }

    public function withdraw()
    {
        $pay = Request::instance()->param('pay');
        if($pay) {
            $class = "\app\pay\\" . $pay;
            $payClass = new $class();
            $params = Request::instance()->param();
//            $params ['connection'] = $this->connection;
            return $payClass->notify_withdraw($params);
        }
        return false;
    }

}
