<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;
use app\common\model\Admin;
use app\common\model\BankInfo;
use app\common\model\OrangeLogBalance;
use app\common\model\OrderModel;
use app\common\model\Recharge;
use app\common\model\User as UserModel;
use app\common\model\UserLevelLog;
use app\common\model\WithdrawalAddress;
use app\common\service\CnmService;
use app\common\service\UserService;
use app\common\service\WalletService;
use think\Config;
use think\db\exception\PDOException;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 会员管理.
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    protected $relationSearch = true;

    protected $searchFields = 'id,username,nickname';

    protected $selectpageFields = 'id,username,tel';

    /**
     * @var \app\common\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\User();
        $this->assign('statusList', $this->model->getStatusList());
        $template = 'special';
        $this->assign('template', $template);
        $this->assignconfig('template', $template);

    }

    /**
     * 查看.
     */
   public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $parent = '';

            $filter = json_decode($this->request->get('filter'), true);
            if (isset($filter ['parent.username']) && !empty($filter['parent.username'])) {
                $parent = $filter['parent.username'];
            }
            $top_id=$this->request->get('top_id',0);
            $member_id=$this->request->get('member_id',0);
            $username=$this->request->get('username',0);
            $jia=$this->request->get('jia','');
          if($top_id||$member_id||$username||$jia!=''){
               [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, null, ['parent.username']); 
              $where = [];
                if ($member_id!= '') {
                    $where['id'] = $member_id;
                }
                if ($username!= '') {
                    $where['username'] = $username;
                }
                if ($jia!= 'all') {
                    $where['jia'] = $jia;
                }

                if ($top_id) {
                    $where['top_parent'] = $top_id;
                }
          }else{
               [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, null, ['parent.username']); 
          }
          
             
            $childWhere = [];
            $self = [];
            $parent_total = 0;
            $total = $this->model
                ->with(['bank'])
                ->where($where);

            $list = $this->model
                ->with(['bank'])
                ->where($where);
        //   dump( $where);
        //   dump($list->select()->toArray());
        //   die();
            if ($this->isSuperAdmin) {
                if (!empty($parent)) {
                    $limit = 9;
                    $parent_total = 1;
                    //查询代理
                    $self = $this->model->where('username', $parent)->select()->toArray();
                    $childWhere['parent_id'] = $self[0]['id'];
                }
                $total = $total->where($childWhere);

                $list = $list->where($childWhere);

            } else {

                $total = $total->where('top_parent', '=', $this->auth->user_id)->whereOr('id', $this->auth->user_id);

                $list = $list->where('top_parent', '=', $this->auth->user_id)->whereOr('id', $this->auth->user_id);
            }


            $total = $total->order($sort, $order)->count();


            $list = $list->order($sort, $order)->limit($offset, $limit)->select();


            foreach ($list as $k => $v) {
                $v->hidden(['pwd', 'salt', 'pwd2', 'salt2']);

            }
            $dataList = $list->toArray();
            if ($self) {
                //让代理永远保持在第一行
                $dataList = array_merge($self, $dataList);
            }

            if ($dataList) {
                foreach ($dataList as &$item) {
                    $item ['is_online'] = (time() - $item['update_time']) < 60 ? 1 : 0;
                    //查询父级
                    $item ['parent.username'] = $this->model->where('id', $item['parent_id'])->value('username');
                     $item ['top_parent_name'] = $this->model->where('id', $item['top_parent'])->where('tel',12345678911)->value('username');
                }
            }

            $result = array("total" => $total + $parent_total, "rows" => $dataList);

            return json($result);
        }

        $invite_code = $this->model->where('id', $this->auth->user_id)->value('invite_code');

        $this->assign('invite_code', '代理邀请码：' . $invite_code);
 $this->assign('invite_code2',  $invite_code);

        return $this->view->fetch();
    }

public function findFirstNumber($string) {
    $matches = [];
    preg_match('/\d+/', $string, $matches);
    return isset($matches[0]) ? $matches[0] : null;
}

public function containsNumber($string) {
    return preg_match('/\d/', $string) > 0;
}
 

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
           // $this->token();
            $params = $this->request->post('row/a');
            if (empty($params)) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            $params = $this->preExcludeFields($params);

            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $params[$this->dataLimitField] = $this->auth->id;
            }
            
            if ($this->containsNumber($params['username'])) {
} else {
     $this->error('用户名格式错误');
}
            
$num=$this->findFirstNumber($params['username']);
$j=0;
$kk='';
$characters = str_split($num);
foreach ($characters as $char) {
   if($char=='0'){
       $j++;
       $kk.='0';
   }else{
       break;
   }
}

$pname=str_replace($num, "", $params['username']);
 for($i=0;$i<10;$i++){
     if($i==0){
           $username=$pname.$num;
     }else{
          $username=$pname.$kk.$num; 
     }
   
     

            //检测用户名是否重复
            $check_user = $this->model->where('username',$username)->find();
            if (!empty($check_user)) {
                $this->error('Repeat of user name');
            }
$params['tel']='';
            //检测手机号码是否重复
    
            $parent_id = 0;
            $salt = rand(0, 99999);            //生成盐
            $salt2 = rand(0, 99999);  //生成盐
            $invite_code = $this->create_invite_code(); //生成邀请码
            $pwd_str = config('app.pwd_str');
            //
            $data = [
                'tel' => $params['tel'],
                'email' => '',
                'username' =>$username,
                'parent_id' => $parent_id,
            ];
            $data['pwd'] = sha1($params['pwd'] . $salt . $pwd_str);
            $data['salt'] = $salt;
            $data['top_parent'] =  $this->auth->user_id;
            $data['create_at'] = time();
            $data['invite_code'] = $invite_code;
            $data['headpic'] = "/common/img/avatar/28.8a7e137.png";
            $data['pwd2'] = sha1($params['pwd2'] . $salt2 . $pwd_str);
            $data['salt2'] = $salt2;
             $data['jia'] = 1;
            $res = $this->model->insert($data);
                 $num++;
 }
            if ($res) {
                $this->success();
            } else {
                $this->error();
            }
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if (empty($params)) {
                $this->error(__('Parameter %s can not be empty', ''));
            }

            if (isset($params ['type']) && !empty($params ['type'])) {
                $result = false;
                try {
                    if (isset($params ['ids']) && !empty($params ['ids'])) {
                        //批量操作
                        $ids = explode(',', $params ['ids']);
                        foreach ($ids as $item) {
                            $data = ['id' => $item, 'money' => $params ['value'], 'type' => $params ['type']];
                            if ($params ['type'] == 'add') {
                                $result = $this->editData($data);
                            } else if ($params ['type'] == 'sub') {
                                $result = $this->editData($data);
                            }
                        }
                    } else {
                        //操作单条数据
                        $result = $this->editData($params);
                    }
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
                if ($result) {
                    $this->success();
                } else {
                    $this->error(__('Operation failed'));
                }


            } else {

                $row = $this->model->get($ids);
                if (!$row) {
                    $this->error(__('No Results were found'));
                }
                $adminIds = $this->getDataLimitAdminIds();
                if (is_array($adminIds)) {
                    if (!in_array($row[$this->dataLimitField], $adminIds)) {
                        $this->error(__('You have no permission'));
                    }
                }
                if ($this->request->isPost()) {
                    $params = $this->request->post('row/a');
                    if ($params) {
                        $params = $this->preExcludeFields($params);
                        $result = false;
                        Db::startTrans();

                        try {
                            //是否采用模型验证
                            if ($this->modelValidate) {
                                $name = str_replace('\\model\\', '\\validate\\', get_class($this->model));
                                $validate = is_bool($this->modelValidate) ? $name : $this->modelValidate;
                                $pk = $row->getPk();
                                if (!isset($params[$pk])) {
                                    $params[$pk] = $row->$pk;
                                }
                                validate($validate)->scene($this->modelSceneValidate ? 'edit' : $name)->check($params);
                            }
                            $result = $row->save($params);
                            Db::commit();
                        } catch (ValidateException $e) {
                            Db::rollback();
                            $this->error($e->getMessage());
                        } catch (PDOException $e) {
                            Db::rollback();
                            $this->error($e->getMessage());
                        } catch (\Exception $e) {
                            Db::rollback();
                            $this->error($e->getMessage());
                        }
                        if ($result !== false) {
                            $this->success();
                        } else {
                            $this->error(__('No rows were updated'));
                        }
                    }
                    $this->error(__('Parameter %s can not be empty', ''));
                }
            }

        }

        $row = $this->model->get($ids);

        $row ['id'] = $this->request->get('ids');
        $row ['type'] = $this->request->get('type');
        if ($row ['type'] == 'cardnum') {
            $bankInfo = $this->getBankInfo($row ['id']);
            $this->view->assign('bankInfo', $bankInfo);
        }
        if ($row ['type'] == 'address') {
            $row ['address'] = WithdrawalAddress::where('user_id', $row ['id'])->value('address');
        }
       
        
        if ($row ['type'] == 'order_model') {
            $row ['order_model'] = $this->model->where('id', $row['id'])->value('order_model');
        }
        if ($row ['type'] == 'level') {
            $row ['level'] = $this->model->where('id', $row['id'])->value('level');
        }
        if ($row ['type'] == 'joint_order_model') {
            $row ['joint_order_model'] = $this->model->where('id', $row['id'])->value('joint_order_model');
        }
        if ($row ['type'] == 'strategy') {
            if (!$row ['strategy']) {

                $strategy = [];
                $day_order_num = \app\common\model\Config::where('name', 'day_order_num')->value('value');
                for ($i = 0; $i < $day_order_num; $i++) {
                    $strategy [] = [
                        'number' => '第【' . ($i + 1) . '】单',
                        'amount' => '0',
                        'income' => '0'
                    ];
                }

                $row ['strategy'] = json_encode($strategy, JSON_UNESCAPED_UNICODE);
            } else {
                $strategy = json_decode($row ['strategy'], true);
                foreach ($strategy as $key => &$item) {
                    if ($key == ($row ['today_order_number'] - 1)) {
                        $item ['number'] = '第【' . $row ['today_order_number'] . '】单 当前';
                    } else {
                        $item ['number'] = '第【' . ($key + 1) . '】单';
                    }
                }
                $row ['strategy'] = json_encode($strategy, JSON_UNESCAPED_UNICODE);
            }
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();

    }

public function deluser(){
    $ids=input('ids');
    $re=Db::name('user')->delete($ids);
     $this->success();
}
    /**
     * 提款用户组
     */
    public function withdrawal_user_group()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $otherWhere ['is_deposit'] = 1;
            if (!$this->isSuperAdmin) {
                $otherWhere['parent_id'] = $this->auth->user_id;
            }

            $list = $this->model
                ->where($where)
                ->where($otherWhere)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加提款用户组
     */
    public function withdrawal_user_group_add()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            if (empty($username)) {
                $this->error(__('Parameter %s can not be empty', 'username'));
            }

            $user = $this->model->where('username', $username)->find();
            if (empty($user)) {
                $this->error('用户不存在');
            }

            if ($user->is_deposit == 0) {
                $this->error('该用户已被禁止提现');
            }

            $result = $this->model->where('id', $user->id)->update(['is_deposit' => 1]);

            if ($result) {
                $this->success();
            } else {
                $this->error();
            }
        }
    }

    /**
     * 添加提款用户组
     */
    public function withdrawal_user_group_del()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->get('id');
            if (empty($id)) {
                $this->error(__('Parameter %s can not be empty', 'id'));
            }

            $result = $this->model->where('id', $id)->update(['is_deposit' => 0]);

            if ($result) {
                $this->success();
            } else {
                $this->error();
            }
        }
    }


    // 用户流水
    public function finance()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $this->model = new OrangeLogBalance();

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            if ($list) {
                foreach ($list as $v) {
                    if ($v['action'] == 2 && $v['adminer'] != "") {
                        $v['number'] = '';
                    } else {
                        $v['number'] = bcadd($v['number'], 0, 2);
                    }

                    if ($v ['i'] == 1) {
                        $v ['number'] = '+ ' . $v ['number'];
                    } else {
                        $v ['number'] = '- ' . $v ['number'];
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        //uid
        $uid = $this->request->get('uid') ?? 0;
        $this->assignconfig('uid', $uid);

        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    /**
     * 递归创建邀请码
     */
    private function create_invite_code()
    {
        $str = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $rand_str = substr(str_shuffle($str), 0, 6);
        $num = $this->model->where('invite_code', $rand_str)->count();
        if ($num) {
            return $this->create_invite_code();
        } else {
            return $rand_str;
        }
    }

    /**
     * 数据操作
     */
    private function editData($params)
    {
        $result = false;
        switch ($params ['type']) {
            case 'userinfo':
                //修改用户信息
                $user_id = $params ['id'];
                unset($params ['type'], $params ['id']);
                $result = $this->model->where('id', $user_id)->update($params);
                break;
            case 'joint_order_model':
                //修改联单模板
                $result = $this->model->where('id', $params ['id'])->update(['joint_order_model' => $params ['joint_order_model']]);
                break;
            case 'strategy':
                //修改策略
             
                $result = $this->model->where('id', $params ['id'])->update(['strategy' => $params ['strategy']]);
                break;
            case 'today_order_number':
                //修改策略
                   $data['index_day']=$params['day'];
                    $data['index_orders']=60;//config('site.day_order_num');
                    $data['today_order_number']=0;
                 //    throw new \Exception('一天只能修改一次用户等级'.config('site.day_order_num'));
                $result = $this->model->where('id', $params ['id'])->update($data);
                break;
            case 'level':
                //查询等级修改记录
                $log = UserLevelLog::where('uid', $params ['id'])->where('create_time', 'today')->find();
                if ($log) {
                    throw new \Exception('一天只能修改一次用户等级');
                }
                //查询用户数据
                $user = $this->model->where('id', $params ['id'])->find();

                //修改用户等级
                $result = $this->model->where('id', $params ['id'])->update(['level' => $params ['level']]);
                if ($result) {
                    $log = [
                        'uid' => $params ['id'],
                        'before' => $user ['level'],
                        'after' => $params ['level'],
                        'admin_id' => $this->auth->id,
                        'create_time' => time(),
                    ];
                    UserLevelLog::create($log);
                }
                break;
            case 'send':
                //发消息
                $id = $params ['id'];
                $content = $params ['content'];
                $title = $params ['title'];
                $result = (new UserService())->sendMsg($id, $this->auth->id, $title, $content, 1);
                break;
            case 'clean':
                // 清除时间
                $id = $params ['id'];
                //清除
                $result = $this->model->where('id', $id)->update(['withdraw_long' => 0]);
                if (!$result) {
                    throw new \Exception('清除失败');
                }
                break;
            case 'login':

                $pwd_str = config('app.pwd_str');

                $id = $params ['id'];
                $data = [];

                //登录密码
                if (isset($params ['pwd']) && !empty($params ['pwd'])) {
                    $salt = rand(0, 99999);            //生成盐
                    $pwd = $params['pwd'];
                    $data['pwd'] = sha1($pwd . $salt . $pwd_str);
                    $data['salt'] = $salt;
                }

                //交易密码
                if (isset($params ['pwd2']) && !empty($params ['pwd2'])) {
                    $salt2 = rand(0, 99999);  //生成盐
                    $data['pwd2'] = sha1($params['pwd2'] . $salt2 . $pwd_str);
                    $data['salt2'] = $salt2;
                }

                if (empty($data)) {
                    $this->error('请输入需要修改的密码');
                }

                $result = $this->model->where('id', $id)->update($data);
                break;
            case 'upname':
                //更换代理
                $upname = $params['upname'];
                $id = $params['id'];
                if ($upname == '') {
                    $data['parent_id'] = '';
                    $result = $this->model->where('id', $id)->update($data);
                }
                $pid = $this->model->where('username', $upname)->value('id');
                if ($pid) {
                    $data['parent_id'] = $pid;
                    $result = $this->model->where('id', $id)->update($data);
                }
                break;
                 case 'top_parent':
                //更换代理线
                $top_parent = $params['top_parent'];
                $id = $params['id'];
             
                    $result = $this->model->where('id', $id)->update(['top_parent'=>$top_parent]);
             
             
                break;
            case 'add':
                $id = $params ['id'];

                //彩金
                $money = abs($params['money']);

                //获取当前登录管理添加彩金上限
                $moneyLimit = Admin::where('id', $this->auth->id)->value('money');

                $numAll = Recharge::where('uid', $id)->where('type', 2)->select()->toArray();
                $all = array_sum(array_column($numAll, 'num'));
                $addAll = $money + $all;

                if ($addAll < $moneyLimit) {
                    $db = (new WalletService())->balanceRecharge($id, $money);

                    $orderNo = getSn('CJ');

                    $db2 = Recharge::insert([
                        'id' => $orderNo,
                        'uid' => $id,
                        'type' => 2,
                        'num' => $money,
                        'remark' => '彩金(+)',
                        'adminer' => $this->auth->id,
                        'create_at' => time(),
                    ]);
                    if ($db && $db2) {
                        (new CnmService())->caijin($id, $money, $orderNo, $this->auth->username);
                        $result = true;
                    }
                } else {
                    throw new \Exception('当前管理员超出赠送彩金限制');
                }

                break;
            case 'sub':
                // 扣款
                $money = abs($params['money']);
                $id = $params['id'];

                //生成订单编号
                $orderNo = getSn('CJ');

                $balance = UserModel::where('id', $id)->value('balance');
                $comp = bccomp($balance, $money);
                if ($comp == -1 || $comp == 0) {
                    $money = $balance;
                }
                $db = (new WalletService())->balanceWithdraw($id, $money);

                $db2 = Recharge::insert([
                    'id' => $orderNo,
                    'uid' => $id,
                    'type' => 2,
                    'num' => $money,
                    'remark' => '扣款(-)',
                    'adminer' => $this->auth->id,
                    'create_at' => time(),
                ]);
                if ($db && $db2) {
                    (new CnmService())->koukuan($id, $money, $orderNo, $this->auth->username);
                    $result = true;
                }
                break;
            case 'cardnum':
                //银行卡
                $id = $params['id'];
                $bank = isset($params['bankname']) ? $params['bankname'] : '';
                $ifsc = isset($params['ifsc']) ? $params['ifsc'] : '';
                $cardnum = $params['cardnum'];
                $name = $params['realname'];
                $db1 = BankInfo::where('uid', $id)->find();

                if ($db1) {
                    $result = BankInfo::where('uid', $id)->update(['bankname' => isset($bank) ? $bank : '', 'cardnum' => $cardnum, 'username' => $name, 'ifsc' => isset($ifsc) ? $ifsc : '',]);
                } else {
                    $result = BankInfo::insert(['uid' => $id, 'bankname' => isset($bank) ? $bank : '', 'cardnum' => $cardnum, 'username' => $name, 'tel' => '', 'site' => '', 'status' => 1, 'ifsc' => isset($ifsc) ? $ifsc : '',]);
                }
                break;
            case 'address':
                //银行卡
                $id = $params['id'];
                $address = $params['address'];
                $db1 = WithdrawalAddress::where('user_id', $id)->find();

                if ($db1) {
                    $result = WithdrawalAddress::where('user_id', $id)->update(['address' => $address]);
                } else {
                    $result = WithdrawalAddress::insert(['user_id' => $id, 'address' => $address, 'create_time' => time(), 'update_time' => time()]);
                }
                break;
            case 'status':
                // 状态
                $id = $params ['id'];
                $status = $params ['status'];

                $update ['status'] = $status == 1 ? 2 : 1;
                $result = $this->model->where('id', $id)->update($update);
                break;
                   case 'jia':
                // 状态
                $id = $params ['id'];
                $status = $params ['jia'];

                $update ['jia'] = $status == 1 ? 0 : 1;
                $result = $this->model->where('id', $id)->update($update);
                break;
            case 'order_model':
                $id = $params ['id'];
                $update['order_model'] = $params ['order_model'];
                $update['order_number'] = 0;
                $update['order_child_model_number'] = 0;

                $model_change_num = $this->model->where('id', $id)->value('order_model_change_num');
                $model_change_num = $model_change_num . ',' . $params ['order_model'];
                $update['order_model_change_num'] = $model_change_num;

                $vip = OrderModel::where('id', $params ['order_model'])->value('name');
                if (strripos('_' . $vip, 'vip')) {
                    $update ['level'] = str_replace('vip', '', $vip);
                }

                $result = $this->model->where('id', $id)->update($update);
        }
        return $result;
    }

    /**
     * 获取用户银行卡信息
     */
    public function getBankInfo($id)
    {
        $data = BankInfo::where('uid', $id)->find();
        if (!$data) {
            $data ['cardnum'] = '';
            $data ['ifsc'] = '';
            $data ['username'] = '';
        }
        return $data;
    }
}
