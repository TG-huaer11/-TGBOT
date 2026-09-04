<?php

namespace app\common\service;

use app\common\constant\CT;
use app\common\model\FireWall;
use app\common\model\IndexMsg;
use app\common\model\LogBalance;
use app\common\model\Message;
use app\common\model\User;
use app\common\model\UserLevel;
use Exception;
use think\facade\Config;
use think\facade\Db;

class UserService extends BaseService
{

    /**
     * 获取用户消息
     * @param $uid
     * @return int
     */
    public function userMessageServer($uid)
    {
        $message = User::where('id', $uid)->count();
        return (int)$message ?? 0;
    }

    /**
     * 获取用户信息
     * @param $uid
     * @param $field
     * @return \app\common\model\User|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfo($uid, $field = '*')
    {
        $user = User::where('id', $uid)->field($field)->find();
        $user->headpic = cdnurl($user->headpic, true);
        //今日订单数最后修改时间
//        if ($user) {
//            $lastTime = date('j', $user ['today_order_update_time']);
//            //当前时间
//            $currentTime = date('j',time());
//            if ($currentTime != $lastTime) {
//                $today_order_number = 0;
//                $user->today_order_number = $today_order_number;
//                $user->save();
//            }
//        }

        return $user;
    }

    /**
     * 用户注册
     * @param string $username 用户名
     * @param string $pwd 密码
     * @param bool $remember 是否记住账号面膜
     * @return bool
     * @throws Exception
     */
    public function login($username, $pwd, $remember)
    {
        $rule = [
            'username' => 'require',
            'pwd' => 'require',
        ];

        $msg = [
            'username.require' => 'Account can not be empty',
            'pwd.require' => 'Password can not be empty',
        ];
        $data = [
            'username' => $username,
            'pwd' => $pwd,
        ];

        $validate = validate($rule, $msg, false, false);
        $result = $validate->check($data);
        if (!$result) {
            throw new Exception(lang($validate->getError()));
        }


        $userInfo = User::where(['username' => $username])->find();

        //用户不存在
        if (empty($userInfo)) {
            throw new Exception(lang('Login_Account_does_not_exist'));
        }

        //获取IP
        $regip = getClientIp();

        //获取ip白名单
        $ipList = FireWall::select();

        $host = '114.114.114.114';
        foreach ($ipList as $v) {
            $host .= ',' . $v['ip'];
        }

        if (in_host($regip, $host)) {
            throw new Exception(lang('Account exception'));
        }

        //用户被禁用
        if ($userInfo['status'] != 1) {
            throw new Exception(lang('Login_The_account_has_been_disabled'));
        }

        if ($userInfo['pwd'] != sha1($pwd . $userInfo['salt'] . config('app.pwd_str'))) {
            User::where('id', $userInfo['id'])->update(['pwd_error_num' => Db::raw('pwd_error_num + 1')]);
            throw new Exception(lang('Login_Passwrd_Error'));
        }

        User::where('id', $userInfo['id'])->update([
            'pwd_error_num' => 0,
            'allow_login_time' => 0,
            'login_status' => 1,
            'ip' => $regip,
            'update_at' => time()
        ]);


        //将用户id，头像记录在session里
        session('user_id', $userInfo['id']);
        session('avatar', $userInfo['headpic']);
        session('username', $userInfo['username']);

        cookie('user_id', $userInfo['id']);
        cookie('avatar', $userInfo['headpic']);
        cookie('username', $userInfo['username']);
        cookie('user_status', $userInfo ['status']);

        if (!cache('session_ids')) {
            $sessionIds = [];
            $sessionIds[$userInfo['id']] = session_id();
            cache('session_ids', $sessionIds);
        } else {
            $sessionIds = cache('session_ids');
            $sessionIds[$userInfo['id']] = session_id();
            cache('session_ids', $sessionIds);
        }

        //记住账号密码
        if ($remember) {
            cookie('username', $username, 999999999);
        }

        return $userInfo ['id'];
    }

    public function register($params)
    {
        $ipList = FireWall::select();
        $host = '114.114.114.114';
        foreach ($ipList as $v) {
            $host .= ',' . $v['ip'];
        }

        if (in_host($params['regip'], $host)) {
            throw new Exception(lang('Register_This_ip_has_been_registered'));
        }

        // 空格检测
//        if (strpos($params['tel'], ' ')) {
//            throw new Exception(lang('Register_Phone_format_error'));
//        }

        //获取默认国家区号
//        $country = (new \app\common\service\IndexService())->getDefaultCountryCode();
//
//        // 正则匹配手机号 印度专业
//        if ($country == '91') {
//            if (!is_mobile_india($params['tel'])) {
//                throw new Exception(lang('Register_Phone_format_error'));
//            }
//        }
//
//        //正则匹配手机号 巴西专业
//        if ($country == '55') {
//            if (!is_mobile_baxi($params['tel'])) {
//                throw new Exception(lang('Register_Phone_format_error'));
//            }
//        }

        $rule = [
//            'tel' => 'number|length:10,20',
            'username' => 'require|alphaNum|length:6,12',
//            'email' => 'email',
            'password' => 'require|length:6,12',
            'transaction_password' => 'require|length:6',
            'invite_code' => 'require',
        ];

        $message = [
            'username.alphaNum' => 'Only letters and numbers are allowed',
            'username.require' => 'username is required',
            'username.length' => 'username (6-12 digits)',
            'tel.length' => 'phone number length error',
            'tel.number' => 'Only numbers are allowed',
//            'email.email' => 'email format error',
            'password.require' => 'login password is required',
            'password.length' => 'login password (6-12 digits)',
            'transaction_password.require' => 'transaction password is required',
            'transaction_password.length' => 'transaction password (6 digits)',
            'invite_code.require' => 'invitation code is required',
        ];


        $validate = validate($rule, $message, false, false);
        $result = $validate->check($params);
        if (!$result) {
            throw new Exception(lang($validate->getError()));
        }

        //查询手机号码是否重复
        $user = User::where(['tel' => $params['country_code'] . $params['tel']])->find();
        if ($user) {
            throw new Exception(lang('Register_Mobile'));
        }

        //查询用户名是否重复
        $user = User::where(['username' => $params['username']])->find();
        if ($user) {
            throw new Exception(lang('Register_Account_already_exists'));
        }

        //查询ip是否重复
//        $user = User::where(['regip' => $params['regip']])->find();
//        if ($user) {
//            throw new Exception(lang('Register_This_ip_has_been_registered'));
//        }

        $params['parent_id'] = CT::DEFAULT_VALUE;
        if ($params['invite_code']) {
            $parentUser = User::where(['invite_code' => $params['invite_code']])->find();
            if (!$parentUser) {
                throw new Exception(lang('Register_Invitation_code_does_not_exist'));
            }
            if ($parentUser['status'] != 1) {
                throw new Exception(lang('Register_The_recommended_user_has_been_disabled'));
            }
            $params['parent_id'] = $parentUser['id'];
            if ($parentUser['parent_ids'] == ''){
                $params['parent_ids'] = $parentUser['id'] . ',';
                $params['top_parent'] = $parentUser['id'];
            }
            else{
                $params['parent_ids'] = $parentUser['parent_ids'] . $parentUser['id']. ',';
                $params['top_parent'] = $parentUser['top_parent'];
            }
        }
//        var_dump($params);
//        exit();

//        if ($country != $params['country_code']) {
//            throw new Exception(lang('Sign_Error'));
//        }

//        if (md5($params['tel'] . md5("Snapdeal@register")) != $params['sign']) {
//            throw new Exception(lang('Sign_Error'));
//        }

        if (UserService::create($params)) {
            $regip = $params['regip'];
            $userinfo = User::where(['username' => $params['username']])->find();

            //赠送金额
            $give_amount = Config::get('site.give_amount');
            if ($give_amount) {
                //生成余额变动记录
                LogBalance::create(['uid' => $userinfo ['id'], 'num' => $give_amount, 'type' => 0, 'status' => 1]);
            }

            User::where('id', $userinfo['id'])->update([
                'pwd_error_num' => 0,
                'allow_login_time' => 0,
                'login_status' => 1,
                'ip' => $regip,
                'update_at' => time(),
                'balance' => $give_amount
            ]);

            $userId = $userinfo['id'];
            session('user_id', $userId);
            session('avatar', $userinfo['headpic']);

            cookie('user_id', $userId);
            cookie('avatar', $userinfo['headpic']);
            cookie('user_status', 1);

            if (!cache('session_ids')) {
                $sessionIds = [];
                $sessionIds[$userId] = session_id();
                cache('session_ids', $sessionIds);
            } else {
                $sessionIds = cache('session_ids');
                $sessionIds[$userId] = session_id();
                cache('session_ids', $sessionIds);
            }
            return $userId;
        } else {
            throw new Exception(lang('Sign_Error'));
        }

    }


    public function create($params)
    {
        $tel = $params['country_code'] . $params['tel'];
//        $email = $params['email'];
        $username = $params['username'];
        $password = $params['password'];
        $transactionPassword = $params['transaction_password'];
        $parentId = $params['parent_id'];
        $regip = $params['regip'];

        if (!$username) {
            $username = get_username();
        }
        if ($parentId) {
            $parentId = User::where(['id' => $parentId])->value('id');
            if (!$parentId) {
                throw new Exception('the recommended user does not exist');
            }
        }

        Db::startTrans();
        try {
            $data = [];
            $data['tel'] = $tel;
            $data['username'] = $username;
//            $data['email'] = $email;
            $data['salt'] = rand(0, 99999);
            $data['create_at'] = time();
            $data['invite_code'] = self::create_invite_code();
            $data['level'] = 1;
            $data['parent_ids'] = $params['parent_ids'];
            $data['top_parent'] = $params['top_parent'];
            $data['parent_id'] = $parentId;
            $data['headpic'] = "/assets/common/img/avatar/28.8a7e137.png";
            $data['pwd'] = sha1($password . $data['salt'] . config('app.pwd_str'));
            $data['regip'] = $regip;
            if ($transactionPassword) {
                $transactionSalt = rand(0, 99999);
                $data['pwd2'] = sha1($transactionPassword . $transactionSalt . config('app.pwd_str'));
                $data['salt2'] = $transactionSalt;
            }

            $userId = User::insertGetId($data);
            if ($parentId) {
                User::where('id', $parentId)->update([
                    'childs' => Db::raw('childs+1'),
                    'deal_reward_count' => Db::raw('deal_reward_count+' . config('app.deal_reward_count'))
                ]);
            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 生成邀请码
     * @return false|string
     */
    public function create_invite_code()
    {
        $str = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $rand_str = substr(str_shuffle($str), 0, 6);
        $num = User::where(['invite_code' => $rand_str])->find();
        if ($num) {
            return self::create_invite_code();
        } else {
            return $rand_str;
        }
    }

    public function rotUserDataOfIndex($uid)
    {
        $price = User::where('id', $uid)->sum('balance');
        $lock_deal = User::where('id', $uid)->sum('freeze_balance');
        $user_level = User::where('id', $uid)->sum('level');
        $member_level = UserLevel::order('level asc')->select();
        $order_num = $member_level[0]['order_num'];
        $uinfo = User::where('id', $uid)->find();
        if (!empty($uinfo['level'])) {
            $order_num = UserLevel::where('level', $uinfo['level'])->value('order_num');
        }

        return ['price' => $price, 'lock_deal' => $lock_deal, 'user_level' => $user_level, 'order_num' => $order_num];
    }

    public function getUserMessage($uid)
    {

        User::where('id', $uid)->update(['message' => 0]);

        // 近7天
        $time1 = strtotime("-7 day");
        $time2 = time();

        $info = Message::alias('m')
            ->leftJoin('reads r', 'r.mid=m.id and r.uid=' . $uid)
            ->field('m.*,r.id rid')
            ->where('m.uid', 'in', [0, $uid])
            ->where('m.create_at', 'BETWEEN', [$time1, $time2])
            ->order('create_at desc')
            ->select();

        if (!empty($info)) {
            foreach ($info as &$item) {
                $item ['create_at'] = format_datetime($item ['create_at'], 'm-d H:i');
            }
        }

        $msg = IndexMsg::where('status', 1)->select();

        return ['info' => $info, 'msg' => $msg];
    }

    public function set_header($uid, $pic)
    {
        return User::where('id', $uid)->update(['headpic' => $pic]);
    }

    /**
     * 发消息
     * uid 用户id
     * sid 操作人id
     * title 标题
     * message 内容
     * type 类型 1=私信 0=公告
     */
    public function sendMsg($uid, $sid, $title, $msg, $type)
    {
        Db::startTrans();
        try {

            $data = [
                'uid' => $uid,
                'sid' => $sid,
                'title' => $title,
                'content' => $msg,
                'create_at' => time(),
                'type' => $type,
            ];
            Message::insert($data);
            User::where('id', $uid)->update(['message' => 1]); //状态处理 1
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function getChildUserIdsByUsername($username)
    {
        $parentUser = User::where('username', $username)->field('id')->findpty();
        return User::where('parent_id', $parentUser['id'])->column('id');
    }

    /**
     * 重置密码
     */
    public static function resetPassword($userId, $pwd, $type = 1)
    {
        $salt = mt_rand(0, 99999);
        if ($type == 1) {
            $data = [
                'pwd' => sha1($pwd . $salt . config('app.pwd_str')),
                'salt' => $salt,
            ];
        } elseif ($type == 2) {
            $data = [
                'pwd2' => sha1($pwd . $salt . config('app.pwd_str')),
                'salt2' => $salt,
            ];
        }
        $res = User::where('id', $userId)->update($data);
        return (bool)$res;
    }


}