<?php

namespace app\admin\controller;

use app\common\model\User;
use app\common\model\Recharge;
use app\common\model\Deposit;
use app\common\controller\Backend;
use think\facade\Db;
/**
 * 代理管理.
 *
 * @icon fa fa-user
 */
class Agent extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new User();

        $parent_id = input('parent_id', 0);
        $this->assignconfig('parent_id', $parent_id);
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


            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['time'])) {
                $time = explode(' - ', $filter['time']);
                $start_time = strtotime($time [0]);
                $end_time = strtotime($time [1]);
            } else {
                $start_time = strtotime(date('Y-m-d 00:00:00'));
                $end_time = strtotime(date('Y-m-d 23:59:59'));
            }

            // 条件集合
            $where_deposit = [];
            $where_user = [];
            $where_lottery = [];

            if ($this->isSuperAdmin) {
                $childWhere['parent_id'] = 0;
            } else {
                $childWhere['parent_id'] = $this->auth->user_id;
            }

            // 条件设置
            // 提现条件
            $where_deposit[] = [
                'end_at', 'between', [$start_time, $end_time]
            ];
            // 彩金专用条件
            $where_lottery[] = [
                'create_at', 'between', [$start_time, $end_time]
            ];
            // 注册时间
            $where_user[] = [
                'create_at', 'between', [$start_time, $end_time]
            ];

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['time']);

            $total = $this->model
                ->where($where)
                ->where($childWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($childWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $v) {
                $v->hidden(['pwd', 'salt', 'pwd2', 'salt2']);
            }

            $list = $list->toArray();

            $data = [];
            foreach ($list as $item) {
                // 该用户的全部下级的数据查询
                $child = $this->child_user($item['id'], 5); //1, ['regtime' => [$post['start'], $post['end']]]
                $child[] = $item['id'];
                $childs = array_chunk($child, 3000);
                $register = 0;
                $recharges = 0;
                $jackpots = 0;
                $deposits = 0;
                foreach ($childs as $k => $chunk) {
                    $n = User::where('id', 'in', $chunk)->where($where_user)->count('id');
                    $register = bcadd($register, $n);
                    $r = Recharge::where($where_deposit)
                        ->where('status', 2)
                        ->where('type', 1)
                        ->where('uid', 'in', $chunk)
                        ->fieldRaw("cast(SUM(real_num) AS DECIMAL(12, 2)) db_recharge")
                        ->select();
                    $recharges = bcadd($recharges, $r[0]['db_recharge'], 2);

                    $j = Recharge::where($where_lottery)
                        ->where('type', 2)
                        ->where('uid', 'in', $chunk)
                        ->where('remark', '彩金(+)')
                        ->fieldRaw("cast(SUM(num) AS DECIMAL(12, 2)) db_jackpot")
                        ->select();
                    $jackpots = bcadd($jackpots, $j[0]['db_jackpot'], 2);

                    $d = Deposit::where($where_deposit)
                        ->where('status', 2)
                        ->where('uid', 'in', $chunk)
                        ->fieldRaw("cast(SUM(real_num) AS DECIMAL(12, 2)) db_deposit")
                        ->select();
                    $deposits = bcadd($deposits, $d[0]['db_deposit'], 2);
                }
                $data [] = [
                    'uid' => $item ['id'],
                    'username' => $item ['username'],
                    'register' => $register,
                    'recharge' => $recharges,
                    'lottery' => $jackpots,
                    'deposit' => $deposits
                ];
            }

            $result = array("total" => $total, "rows" => $data);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 查看.
     */
    public function child()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['time'])) {
                $time = explode(' - ', $filter['time']);
                $start_time = strtotime($time [0]);
                $end_time = strtotime($time [1]);
            } else {
                $start_time = strtotime(date('Y-m-d 00:00:00'));
                $end_time = strtotime(date('Y-m-d 23:59:59'));
            }

            // 条件集合
            $where_deposit = [];
            $where_user = [];
            $where_lottery = [];

            // 条件设置
            // 提现条件
            $where_deposit[] = [
                'end_at', 'between', [$start_time, $end_time]
            ];
            // 彩金专用条件
            $where_lottery[] = [
                'create_at', 'between', [$start_time, $end_time]
            ];
            // 注册时间
            $where_user[] = [
                'create_at', 'between', [$start_time, $end_time]
            ];

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['time']);

            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $v) {
                $v->hidden(['pwd', 'salt', 'pwd2', 'salt2']);
            }

            $list = $list->toArray();

            $data = [];
            foreach ($list as $item) {
                // 该用户的全部下级的数据查询
                $child = $this->child_user($item['id'], 5); //1, ['regtime' => [$post['start'], $post['end']]]
                $child[] = $item['id'];
                $childs = array_chunk($child, 3000);
                $register = 0;
                $recharges = 0;
                $jackpots = 0;
                $deposits = 0;
                foreach ($childs as $k => $chunk) {
                    $n = User::where('id', 'in', $chunk)->where($where_user)->count('id');
                    $register = bcadd($register, $n);
                    $r = Recharge::where($where_deposit)
                        ->where('status', 2)
                        ->where('type', 1)
                        ->where('uid', 'in', $chunk)
                        ->fieldRaw("cast(SUM(real_num) AS DECIMAL(12, 2)) db_recharge")
                        ->select();
                    $recharges = bcadd($recharges, $r[0]['db_recharge'], 2);

                    $j = Recharge::where($where_lottery)
                        ->where('type', 2)
                        ->where('uid', 'in', $chunk)
                        ->where('remark', '彩金(+)')
                        ->fieldRaw("cast(SUM(num) AS DECIMAL(12, 2)) db_jackpot")
                        ->select();
                    $jackpots = bcadd($jackpots, $j[0]['db_jackpot'], 2);

                    $d = Deposit::where($where_deposit)
                        ->where('status', 2)
                        ->where('uid', 'in', $chunk)
                        ->fieldRaw("cast(SUM(real_num) AS DECIMAL(12, 2)) db_deposit")
                        ->select();
                    $deposits = bcadd($deposits, $d[0]['db_deposit'], 2);
                }
                $data [] = [
                    'uid' => $item ['id'],
                    'username' => $item ['username'],
                    'register' => $register,
                    'recharge' => $recharges,
                    'lottery' => $jackpots,
                    'deposit' => $deposits
                ];
            }

            $result = array("total" => $total, "rows" => $data);

            return json($result);
        }

        return $this->view->fetch();
    }

    // 获取下级会员
    private function child_user($uid, $num = 1, $lv = 1, $time = [])
    {
        $data = [];
        $where = [];

        if (isset($time['regtime'][0]) && isset($time['regtime'][1]) && $time['regtime'][0] != '' && $time['regtime'][1] != '') {
            $st = strtotime($time['regtime'][0]);
            $et = strtotime($time['regtime'][1]);
            $wher[] = [
                'create_at', 'between', [$st, $et]
            ];
            $where[] = [
                'create_at', 'between', [$st, $et]
            ];
            $where2[] = [
                'create_at', 'between', [$st, $et]
            ];
            $where3[] = [
                'create_at', 'between', [$st, $et]
            ];
        }

        if ($num == 1) {
            $where[] = ['parent_id', '=', $uid];
            $data = User::where('parent_id', $uid)->field('id')->column('id');
        } else if ($num == 2) {
            //二代
            $ids1 = User::where('parent_id', $uid)->field('id')->column('id');
            $ids1 ? $where[] = ['parent_id', 'in', $ids1] : $where[] = ['parent_id', 'in', [-1]];
            $data = User::where($where)->field('id')->column('id');
            $data = $lv ? array_merge($ids1, $data) : $data;
        } else if ($num == 3) {
            //三代

            $ids1 = User::where('parent_id', $uid)->field('id')->column('id');
            $ids1 ? $wher[] = ['parent_id', 'in', $ids1] : $wher[] = ['parent_id', 'in', [-1]];
            $ids2 = User::where($wher)->field('id')->column('id');
            $ids2 ? $where[] = ['parent_id', 'in', $ids2] : $where[] = ['parent_id', 'in', [-1]];
            $data = User::where($where)->field('id')->column('id');
            $data = $lv ? array_merge($ids1, $ids2, $data) : $data;
        } else if ($num == 4) {
            //四带
            $ids1 = User::where('parent_id', $uid)->field('id')->column('id');
            $ids1 ? $wher[] = ['parent_id', 'in', $ids1] : $wher[] = ['parent_id', 'in', [-1]];
            $ids2 = User::where($wher)->field('id')->column('id');
            $ids2 ? $where2[] = ['parent_id', 'in', $ids2] : $where2[] = ['parent_id', 'in', [-1]];
            $ids3 = User::where($where2)->field('id')->column('id');
            $ids3 ? $where[] = ['parent_id', 'in', $ids3] : $where[] = ['parent_id', 'in', [-1]];
            $data = User::where($where)->field('id')->column('id');
            $data = $lv ? array_merge($ids1, $ids2, $ids3, $data) : $data;
        } else if ($num == 5) {
            //四带
            $ids1 = User::where('parent_id', $uid)->field('id')->column('id');
            $ids1 ? $wher[] = ['parent_id', 'in', $ids1] : $wher[] = ['parent_id', 'in', [-1]];
            $ids2 = User::where($wher)->field('id')->column('id');
            $ids2 ? $where2[] = ['parent_id', 'in', $ids2] : $where2[] = ['parent_id', 'in', [-1]];
            $ids3 = User::where($where2)->field('id')->column('id');
            $ids3 ? $where3[] = ['parent_id', 'in', $ids3] : $where3[] = ['parent_id', 'in', [-1]];
            $ids4 = User::where($where3)->field('id')->column('id');
            $ids4 ? $where[] = ['parent_id', 'in', $ids4] : $where[] = ['parent_id', 'in', [-1]];
            $data = User::where($where)->field('id')->column('id');

            $data = $lv ? array_merge($ids1, $ids2, $ids3, $ids4, $data) : $data;
        }

        return $data;
    }
    
    
    
    
   
    /**
     * 删除
     */
  public function delw()
    {
       $ids = input('get.ids');
        if ($ids) {
            $result = Db::name('user')->where('id', 'in', $ids)->delete();
            if ($result) {
                $referer =  $this->request->header('HTTP_REFERER');
            $url = $referer? $referer : url('index'); 
                return $this->success('删除成功', $url);
            } else {
                return json(['code' => 0, 'msg' => '删除失败']);
            }
        }
        return json(['code' => 0, 'msg' => '参数1错误'.$ids]);
    }
    
    
    
    
    
    
    
    
}
