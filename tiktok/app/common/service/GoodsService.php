<?php

namespace app\common\service;

use app\common\model\Goods;
use app\common\model\GoodsCate;

class GoodsService extends BaseService
{

    public function rotGoodsDataOfIndex($type)
    {
        $cate = GoodsCate::alias('c')
            ->leftJoin('user_level u', 'u.id=c.level_id')
            ->field('c.name,c.cate_info,c.cate_pic,u.name as levelname,u.pic,u.level,u.bili,u.order_num')
            ->find($type);

        $goods = Goods::where('shop_name', '120')->select();

        return ['cate' => $cate, 'goods' => $goods];
    }
}