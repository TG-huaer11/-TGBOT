<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\Address;
use app\common\model\BankInfo;
use app\common\model\Deposit;
use app\common\model\Goods;
use app\common\model\UserLevel;
use app\common\model\Recharge;
use app\common\model\RechargeConfig;
use app\common\model\WithdrawalAddress;
use Exception;
use think\facade\Config;
use think\facade\Request;
use think\facade\Db;

/**
 * 会员中心.
 */
class User extends Frontend
{
    protected $noNeedLogin = ['login', 'register', 'third', 'forget', 'config'];
    protected $noNeedRight = ['*'];

    //服务类
    protected $userService = null;
    protected $bannerService = null;

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $this->userService = new \app\common\service\UserService();
        $this->bannerService = new \app\common\service\BannerService();
    }

    public function getGoods()
    {
        $number = $this->request->post('number', 10);
        $list = (new \app\common\model\Goods)->field('id,goods_pic,goods_name,goods_price')->limit(0, 100)->select()->toArray();
        shuffle($list);
        $goods = array_slice($list, 0, $number);
        foreach ($goods as &$item) {
            $item ['goods_pic'] = cdnurl($item ['goods_pic'], true);
        }
        return success($goods);
    }

    public function getTodayCommission()
    {
        $where ['uid'] = $this->uid;

        $where ['action'] = 5;
        $amount = Db::table('orange_log_balance')->where($where)->order('id', 'desc')->sum('number');
        $amount = bcmul($amount, 1, 2);
        return success($amount);
    }

    public function getLevel()
    {
        $level = UserLevel::select();
        foreach ($level as &$item) {
            $item->pic = cdnurl($item->pic, true);
        }
        return success($level);
    }

    public function getUserLevel()
    {
        $level = \app\admin\model\User::where('id', $this->uid)->value('level');
        $level = \app\common\model\UserLevel::get($level);
        return success($level);
    }

    public function config()
    {

        $keyword = $this->request->post('keyword');

        $keyword = explode(',', $keyword);

        $site = config('site');

        $data = [];
        foreach ($keyword as $item) {
            if (!isset($site [$item])) {
                $data [$item] = '';
                continue;
            }

            $data [$item] = $site [$item];

            if ($item == 'index_video') {
                $data [$item] = cdnurl($site [$item], true);
            }

        }

        return success($data);
    }

    public function editHeadPic()
    {
        if ($this->request->isPost()) {

            $headpic = $this->request->post('headpic');

            if (empty($headpic)) {
                return error('error');
            }

            $headpic = preg_replace('/.*?(\/uploads+)/is', '$1', $headpic);

            $user = \app\common\model\User::get($this->uid);
            if (empty($user)) {
                return error('error');
            }

            $user->headpic = $headpic;
            $result = $user->save();

            if (!$result) {
                return error('error');
            }

            return success([], 'success');
        }
    }

    public function getRechargeLog()
    {
        if ($this->request->isPost()) {

            $where ['uid'] = $this->uid;

            $where ['status'] = 2;

            $list = Recharge::where($where)->order('create_at', 'desc')->paginate()->toArray();

            foreach ($list ['data'] as &$item) {
                $item ['create_at'] = date('Y-m-d H:i:s', $item ['create_at']);
            }

            return success(['data' => $list ['data'], 'pages' => $list ['last_page']]);
        }
    }

    public function getWithdrawLog()
    {
        if ($this->request->isPost()) {

            $where ['uid'] = $this->uid;

            $list = Deposit::where($where)->order('create_at', 'desc')->paginate()->toArray();

            foreach ($list ['data'] as &$item) {
                $item ['create_at'] = date('Y-m-d H:i:s', $item ['create_at']);
            }

            return success(['data' => $list ['data'], 'pages' => $list ['last_page']]);
        }
    }

    public function getAccountLog()
    {
        if ($this->request->isPost()) {

            $status = $this->request->post('status', 0);
            $status1 = $this->request->post('status1', 0);

            $where ['uid'] = $this->uid;

            if ($status1) {
                $where ['action'] = $status1;
            }
            if ($status) {
                $where ['i'] = $status;
            }

            $list = Db::table('orange_log_balance')->where($where)->order('id', 'desc')->paginate(10000)->toArray();

            foreach ($list ['data'] as &$item) {
                $item ['created'] = date('Y-m-d H:i:s', $item ['created']);

                $item ['remark'] = __('Bill ' . $item ['action']);

            }

            return success(['data' => $list ['data'], 'pages' => $list ['last_page']]);
        }
    }

    public function getAddress()
    {
        if ($this->request->isPost()) {
              $uid = $this->uid;
           $user = $this->userService->getUserInfo($uid);
           if($user['jia']=='0'){
               $address = Address::where(['status' => 1,'type'=>1])->orderRaw('rand()')->select(); 
           }else{
              $address = Address::where(['status' => 1,'type'=>0])->order('id desc')->select();  
           }
           
            return success($address);
        }
    }

    public function getUserAddress()
    {
        if ($this->request->isPost()) {
             $lang = $this->request->request('lang');
             $re=Db::name('txtz')->where(['lang'=>$lang])->find();
            $address = WithdrawalAddress::where(['user_id' => $this->uid])->find();
            $address['notice']=$re['content'];
            return success($address);
        }
    }

    public function getUserBank()
    {
        if ($this->request->isPost()) {
            $bank = BankInfo::where(['uid' => $this->uid])->find();
            return success($bank);
        }
    }

    /**
     * 会员中心.
     */
    public function index()
    {

        //获取用户消息
        $message = $this->userService->userMessageServer($this->uid);

        $uid = $this->uid;

        $user = $this->userService->getUserInfo($uid);
        $url = SITE_URL . '/user/register/invite_code/' . $user['invite_code'];

        $this->view->assign([
            'url' => $url,
            'title' => __('User center'),
            'message' => $message,
        ]);

        return $this->view->fetch();
    }

    /**
     * 注册会员.
     */
    public function register()
    {
        if ($this->request->isPost()) {
            $params['country_code'] = $this->filter($this->request->post('country_code'));
            $params['tel'] = $this->filter($this->request->post('tel', ''));
//            $params['email'] = $this->filter(str_replace(" ", '', $this->request->post('email', '')));
            $params['username'] = $this->filter(strtolower(str_replace(" ", '', $this->request->post('username', ''))));
            $params['verify_code'] = $this->filter($this->request->post('verify', ''));
            $params['password'] = $this->filter($this->request->post('pwd', ''));
            $params['transaction_password'] = $this->filter($this->request->post('deposit_pwd', ''));
            $params['invite_code'] = $this->filter($this->request->post('invite_code', ''));
            $params['regip'] = getClientIp();
            $params['sign'] = md5($params['tel'] . md5("Snapdeal@register"));
            $params['plat_form'] = $this->filter($this->request->post('plat_form', ''));

            try {
                $user_id = $this->userService->register($params);
                return success(['url' => url('index/index'), 'user_id' => $user_id], __('Logged in successful'));
            } catch (Exception $e) {
                return error($e->getMessage());
            }
        }

        $param = Request::param();
        $invite_code = $this->filter(isset($param['invite_code']) ? trim($param['invite_code']) : '');

        //获取默认国家区号
        $default_country_code = (new \app\common\service\IndexService())->getDefaultCountryCode();

        $this->view->assign([
            'default_country_code' => $default_country_code,
            'invite_code' => $invite_code,
            'title', __('Register')
        ]);

        return $this->view->fetch();
    }

    /**
     * 会员登录.
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $username = $this->filter($this->request->post('username'));
            $pwd = $this->filter($this->request->post('pwd'));
            $remember = $this->filter($this->request->post('remember'));

            try {
                $user_id = $this->userService->login($username, $pwd, $remember);
                return success(['url' => url('index/index'), 'user_id' => $user_id], __('Logged in successful'));
            } catch (Exception $e) {
                return error($e->getMessage());
            }
        }

        $tg = $this->bannerService->getTelegram();

        $ws = $this->bannerService->getWhatsApp();

        $this->view->assign([
            'title' => __('Login'),
            'tg' => $tg,
            'ws' => $ws
        ]);

        return $this->view->fetch();
    }

    public function set_pwd()
    {
        if ($this->request->isPost()) {
            $new_pwd = $this->request->post('new_pwd', 0);
            $old_pwd = $this->request->post('old_pwd', 0);
            $type = $this->request->post('type', 1);

            $uid = $this->uid;
            $user = \app\common\model\User::get($uid);

            //判断密码是否正确

            if ($type == 1) {
                $password = sha1($old_pwd . $user->salt . config('app.pwd_str'));
                if ($password !== $user ['pwd']) {
                    return error(__('Login_Passwrd_Error'));
                }
                $user->salt = rand(0, 99999);
                $user->pwd = sha1($new_pwd . $user->salt . config('app.pwd_str'));
            } else {
                $password = sha1($old_pwd . $user->salt2 . config('app.pwd_str'));
                if ($password !== $user ['pwd2']) {
                    return error(__('Login_Passwrd_Error'));
                }
                $user->salt2 = rand(0, 99999);
                $user->pwd2 = sha1($new_pwd . $user->salt2 . config('app.pwd_str'));
            }

            $result = $user->save();
            if ($result) {
                return success(__('Bank_Success'));
            }

            return error(__('Fail'));
        }
    }

    /**
     * 注销登录.
     */
    public function logout()
    {
        session('user_id', null);
        $this->redirectTo('/user/login');
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post('oldpassword');
            $newpassword = $this->request->post('newpassword');
            $renewpassword = $this->request->post('renewpassword');
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword|' . __('Old password') => 'require|length:6,30',
                'newpassword|' . __('New password') => 'require|length:6,30',
                'renewpassword|' . __('Renew password') => 'require|length:6,30|confirm:newpassword',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'oldpassword' => $oldpassword,
                'newpassword' => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__' => $token,
            ];
            $validate = validate($rule, $msg, false, false);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);

                return false;
            }

            $ret = $this->auth->changepwd($newpassword, $oldpassword);
            if ($ret) {
                $this->success(__('Reset password successful'), url('user/login'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('title', __('Change password'));

        return $this->view->fetch();
    }

    /**
     * 获取用户西信息
     * @return null
     */
    public function getUserInfo()
    {
        if ($this->request->isPost()) {
            $field = $this->filter($this->request->post('field'));
            $info = $this->userService->getUserInfo($this->uid, $field);

            return success($info);
        }

        return error(lang('bad request'));

    }

    /**
     * 用户消息界面
     * @return null
     */
    public function msg()
    {

        if ($this->request->isPost()) {
            $data = $this->userService->getUserMessage($this->uid);

            return success($data);
        }
        //获取用户消息
        $message = $this->userService->userMessageServer($this->uid);
        $this->view->assign([
            'title' => 'Message',
            'message' => $message
        ]);

        return $this->view->fetch();

    }

    /**
     * 个人信息
     * @return mixed
     */
    public function personal()
    {

        $this->view->assign([
            'title' => 'Personal'
        ]);

        return $this->view->fetch();
    }

    /**
     * 设置头像
     * @return mixed
     */
    public function set_header()
    {
        if ($this->request->isPost()) {
            $pic = $this->filter($this->request->post('pic/s'));
            $res = $this->userService->set_header($this->uid, $pic);
            if ($res !== false) {
                return json(['code' => 1, 'msg' => 'Success!']);
            } else {
                return json(['code' => 0, 'msg' => 'Failed!']);
            }
        }
        $this->view->assign([
            'title' => 'set header'
        ]);

        return $this->view->fetch();
    }

    /**
     * 银行卡操作
     * @return null
     */
    public function bind_bank()
    {

        if ($this->request->isPost()) {
            $data = (new \app\common\service\BankInfoService())->getBankInfo($this->uid);
            return success($data);
        }

        $template = (new \app\common\service\BankInfoService())->getBankInfoTemplate($this->uid);

        $this->view->assign([
            'title' => __("Bank_Title")
        ]);

        return $this->view->fetch($template);
    }

    /**
     * 提现地址绑定操作
     * @return null
     */
    public function bind_address()
    {

        if ($this->request->isPost()) {
            $data = (new \app\common\service\WithdrawalAddressService())->getWithdrawalAddress($this->uid);
            return success($data);
        }

        $template = (new \app\common\service\WithdrawalAddressService())->getWithdrawalAddressTemplate($this->uid);

        $this->view->assign([
            'title' => __("Withdrawal_Address_Title")
        ]);

        return $this->view->fetch($template);
    }

    /**
     * 银行卡
     * @return null
     */
    public function bindBank()
    {
        if ($this->request->isPost()) {
            $params ['uid'] = $this->uid;
            $params ['username'] = $this->filter($this->request->post('username/s'));
            $params ['bankname'] = $this->filter($this->request->post('bankname/s', ''));
            $params ['cardnum'] = $this->filter($this->request->post(' /s', ''));
//            $params ['site'] = $this->filter($this->request->post('zhihang/s', ''));
//            $params ['ifsc'] = $this->filter($this->request->post('ifsc/s', ''));
//            $params ['address'] = $this->filter($this->request->post('address/s', ''));

            try {
                $res = (new \app\common\service\BankInfoService())->bindBank($params);
                if ($res) {
                    return success('Success!');
                } else {
                    return error('Failed!');
                }
            } catch (Exception $e) {
                return error($e->getMessage());
            }

        }

        return error('bad request');
    }

    /**
     * 删除银行卡
     * @return null
     */
    public function bind_bank_del()
    {
        if ($this->request->isPost()) {

            $code = (new \app\common\service\BankInfoService())->delBank($this->uid);
            if ($code) {
                return success('');
            } else {
                return error('');
            }
        }

        return error('bad request');
    }

    /**
     * 绑定 USDT(TRC-20)地址
     * @return null
     */
    public function bindWithdrawalAddress()
    {
        if ($this->request->isPost()) {
            $params ['uid'] = $this->uid;
            $params ['name'] = $this->filter($this->request->post('name/s'));
            $params ['address'] = $this->filter($this->request->post('address/s'));

            try {
                $res = (new \app\common\service\WithdrawalAddressService())->bindWithdrawalAddress($params);
                if ($res) {
                    return success('Success!');
                } else {
                    return error('Failed!');
                }
            } catch (Exception $e) {
                return error($e->getMessage());
            }

        }

        return error('bad request');
    }

    /**
     * 删除 USDT(TRC-20)地址
     * @return null
     */
    public function bind_address_del()
    {
        if ($this->request->isPost()) {

            $code = (new \app\common\service\WithdrawalAddressService())->delWithdrawalAddress($this->uid);
            if ($code) {
                return success('');
            } else {
                return error('');
            }
        }

        return error('bad request');
    }

    /**
     * @return null
     */
    public function account()
    {

        if ($this->request->isPost()) {
            $uid = $this->uid;

            $type = $this->filter($this->request->post('type', 0));

            $page = $this->filter($this->request->post('page', 1));

            $list = (new \app\common\service\LogBalanceService())->getPageList($uid, $type, $page);

            return success($list);
        }

        $this->view->assign([
            'title' => __("Caiwu_Account")
        ]);

        return $this->view->fetch();
    }

    public function invite()
    {

        $uid = $this->uid;

        $user = $this->userService->getUserInfo($uid);

        $url = SITE_URL . '/user/register/invite_code/' . $user['invite_code'];

        $this->view->assign([
            'title' => 'Invite',
            'url' => $url,
            'invite_code' => $user['invite_code'],
            'pic' => '/upload/qrcode/user/' . ($uid % 20) . '/' . $uid . '.png'
        ]);

        return $this->view->fetch();
    }

    public function proposal()
    {
        $this->view->assign([
            'title' => 'Proposal',
        ]);

        return $this->view->fetch();
    }

    /**
     * 充值记录
     */
    public function recharge_admin()
    {
        if ($this->request->isPost()) {

            $page = $this->filter($this->request->post('page'));
            $list = (new \app\common\service\RechargeService())->getPageList($this->uid, $page);
            return success($list);
        }

        return $this->view->fetch();
    }

    /**
     * 提现记录
     */
    public function deposit_admin()
    {
        if ($this->request->isPost()) {
            $page = $this->filter($this->request->post('page'));
            $list = (new \app\common\service\DepositService())->getPageList($this->uid, $page);

            return success($list);
        }

        return $this->view->fetch();
    }

    //提现
    public function deposit()
    {
        if ($this->request->isPost()) {
            $data = (new \app\common\service\DepositService())->deposit($this->uid);

            return success($data);
        }

        $this->view->assign([
            'title' => __("Withdraw_Title")
        ]);

        return $this->view->fetch();
    }

    //发起提现
    public function do_deposit()
    {

        if ($this->request->isPost()) {
            //交易密码
            $pwd2 = $this->filter($this->request->post('paypassword'));
            $num = $this->filter($this->request->post('num', 0));
            $bkid = $this->filter($this->request->post('bk_id', 0));
            $addressid = $this->filter($this->request->post('adddress_id', 0));
            $type =2;

    $uid = $this->uid;
            $user = \app\common\model\User::get($uid);

  if ($user['today_order_number'] < config('site.day_order_num')) {
        return error(lang("You can withdraw cash only after completing today's task"));
        }









            try {
                if ($type == 1) {
                    (new \app\common\service\DepositService())->do_deposit_bank($this->uid, $pwd2, $num, $bkid);

                } else {
                    
                    (new \app\common\service\DepositService())->do_deposit_address($this->uid, $pwd2, $num, $bkid);
                }
                return success('', 'Success!');
            } catch (Exception $e) {
                return error($e->getMessage());
            }

        }
        return error('bad request');
    }


    public function recharge()
    {
        if ($this->request->isPost()) {
            $data = (new \app\common\service\RechargeService())->recharge($this->uid);
            return success($data);
        }

        $this->view->assign([
            'title' => __("Recharge_Title"),
        ]);

        return $this->view->fetch();
    }

    public function recharge_config()
    {
        if ($this->request->isPost()) {
            $data = RechargeConfig::where('status', 1)->order('id', 'asc')->column('amount');
            return success($data);
        }

    }


    public function recharge_do_before()
    {

        if ($this->request->isPost()) {
            $num = $this->filter($this->request->post('price', 0));
            $type = $this->filter($this->request->post('type', 'card'));
            $pay_type = $this->filter($this->request->post('pay_type', 1));
            $pay_image = $this->filter($this->request->post('pay_image'));

            if (!$num) {
                return error(lang("Recharge_Enter_Amount"));
            }

            $uid = $this->uid;

            try {
                $res = (new \app\common\service\RechargeService())->recharge_do_before($uid, $num, $type, $pay_type, $pay_image);
                return success($res);
            } catch (Exception $e) {
                return error($e->getMessage());
            }
        }
        return error('bad request');
    }

    // 忘记密码
    public function forget()
    {
        //获取默认国家区号
        $default_country_code = (new \app\common\service\IndexService())->getDefaultCountryCode();
        $this->view->assign([
            'title' => 'forget',
            'default_country_code' => $default_country_code
        ]);

        return $this->view->fetch();
    }

    public function editUserInfo()
    {
        if ($this->request->isPost()) {
            $uid = $this->uid;
            $user = \app\common\model\User::get($uid);
            if (empty($user)) {
                return error(__('Please login first'), 401);
            }

            $params = $this->request->post();

            $user->save($params);
        }
    }

    // 修改登录密码
    public function edit_pwd()
    {
        $this->view->assign([
            'title' => __("Edit_Pwd_Title"),
        ]);

        return $this->view->fetch();
    }

    // 修改提现密码
    public function edit_deposit_pwd()
    {
        $this->view->assign([
            'title' => __("Edit_Deposit_Pwd_Title"),
        ]);

        return $this->view->fetch();
    }

    public function userData()
    {
        if ($this->request->isPost()) {
            $uid = $this->uid;

            $user = \app\common\model\User::where('id', $uid)->field('username,balance')->find();

            $user ['commission'] = \app\common\model\Order::where('status', 1)->where('uid', $uid)->sum('commission');

            return success($user);
        }
    }
}
