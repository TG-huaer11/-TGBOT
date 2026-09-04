<?php

namespace app\common\tool;

class Random
{

    public static function randomString($length)
    {
        $set    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < $length; $i++) $result .= $set[rand(0, strlen($set) - 1)];
        return $result;
    }

    public static function shuffleAssoc($list)
    {
        if (!is_array($list)) return $list;
        $keys = array_keys($list);
        shuffle($keys);
        $random = array();
        foreach ($keys as $key) {
            $random[$key] = self::shuffleAssoc($list[$key]);
        }
        return $random;
    }

}