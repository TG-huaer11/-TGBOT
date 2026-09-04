<?php

namespace app\common\service;

use app\common\model\Banner;

class BannerService extends BaseService
{

    /**
     * 获取轮播图
     * @return array
     */
    public function getList()
    {
        $banners = Banner::select();
        $banner = [];
        foreach ($banners as $v) {
            if ($v['image'] != '') {
                $banner[] = $v;
            }
        }

        return $banner;
    }

    public function getTelegram()
    {
        return Banner::where(['title' => 'telegram'])->value('url');
    }

    public function getWhatsApp()
    {
        return Banner::where(['title' => 'whatsapp'])->value('url');
    }

}