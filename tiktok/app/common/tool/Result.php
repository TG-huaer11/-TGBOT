<?php
/**
 * JSON返回Result 工具类
 */

namespace app\common\tool;

use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Response;

class Result
{

    /**
     * 公共状态码
     */
    public static $error = 400;                                     // 错误返回码
    public static $success = 1;                                     // 成功返回码


    public static $error_log = 500;                                 // 写入日志的状态码
    public static $status_invalid = 501;                            // 无效请求,
    public static $status_unknown_error = 502;                      // 未知错误,
    public static $status_service_not_use = 503;                    // 服务暂不可用,
    public static $status_unknown_method = 504;                     // 未知的方法,
    public static $status_unknown_ip = 505;                         // 请求来自未经授权的IP地址,

    /**
     * 用户相关
     */
    public static $insufficient_balance = 10001;

    /**
     * @param int|HttpException $statusCode 状态码 或者 HttpException对象实例
     * @param string $msg
     * @param array | object $data
     * @param array $extend
     * @return void
     */
    public static function ok($statusCode, string $msg, $data, array $extend)
    {
        // 业务异常
        if ($statusCode instanceof HttpException) self::fail($statusCode);

        $res = [];
        if (!$msg) {
            $msg = self::getMsgByCode($statusCode);
            if (!$msg) {
                $statusCode = self::$status_invalid;
                $msg        = self::getMsgByCode($statusCode);
            }
        }

        $res['data'] = $data;
        $res['code'] = $statusCode;
        $res['info'] = $msg;
        $extend && $res = array_merge($res, $extend);

        // 过滤null输出json
        $response = Response::create(self::nullFilter($res), 'json');
        throw  new HttpResponseException($response);
    }

    /**
     * @param $e
     * @return void
     */
    public static function fail($e, $msg = '')
    {
        if ($e instanceof HttpException) {
            self::fail(['code' => self::$error, 'info' => $e->getMessage()]);
        }

        if (is_array($e)) {
            abort($e['code'], __($e['msg']));
        }

        if (is_numeric($e)) {
            $code = $e;
            if (!$msg) {
                $msg = self::getMsgByCode($code);
            }
            abort($code, __($msg));
        }

        $msg = $e;
        if (!$msg) {
            $msg = self::getMsgByCode($msg);
        }

        abort(self::$error, __($msg));
    }

    /**
     * 过滤Null值
     * @param array $arr 要过滤的数据
     * @return mixed
     */
    public static function nullFilter(array $arr): array
    {
        foreach ($arr as $key => &$val) {
            if (is_array($val)) {
                $val = self::nullFilter($val);
            } else {
                if ($val === null) {
                    $arr[$key] = '';
                }
            }
        }
        return $arr;
    }

    /**
     * 得到 code 对应 msg的数组
     * @param int $code 状态码
     * @return string
     */
    public static function getMsgByCode(int $code): string
    {
        $msgArr = self::getMsgArr();
        return $msgArr[$code]['msg'] ?? '';
    }

    /**
     * 得到 code 对应 msg的数组
     */
    public static function getMsgArr(): array
    {
        return [
            self::$success                => ['msg' => 'request succeeded'],
            self::$error                  => ['msg' => 'request failed'],
            self::$error_log              => ['msg' => 'system error, please contact customer service'],
            self::$status_unknown_error   => ['msg' => 'unknown mistake'],
            self::$status_service_not_use => ['msg' => 'service temporarily unavailable'],
            self::$status_unknown_method  => ['msg' => 'unknown method'],
            self::$status_unknown_ip      => ['msg' => 'the request came from an unauthorized ip address'],
        ];
    }
}