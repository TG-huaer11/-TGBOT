<?php
namespace app\middleware;

class Cors
{
    public function handle($request, \Closure $next)
    {
        header('Access-Control-Allow-Origin: *');  // 也可以替换为你的域名
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');  // 注意这里有 OPTIONS
        header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Authorization, User_id");  // 这里添加 'User-Id'
        header('Access-Control-Allow-Credentials: true');
        if($request->isOptions()){
            exit();  // 如果是 OPTIONS 请求，直接结束，不继续后面的处理
        }
        return $next($request);
    }
}