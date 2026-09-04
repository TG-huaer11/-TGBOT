<?php
/**
 * *
 *  * ============================================================================
 *  * Created by PhpStorm.
 *  * User: Ice
 *  * 邮箱: ice@sbing.vip
 *  * 网址: https://sbing.vip
 *  * Date: 2019/9/19 下午3:33
 *  * ============================================================================.
 */

namespace app\admin\controller;

use app\common\model\Recharge;
use app\common\model\User;
use app\common\controller\Backend;

/**
 * 彩金
 *
 * @internal
 */
class Lottery extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Recharge();
    }

    /**
     * 彩金列表
     */
    public function index()
    {
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $parent = '';

            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['parent.username'])) {
                $parent = $filter['parent.username'];
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['parent.username']);

            $childWhere = [];
            if (!empty($parent)) {
                //查询代理
                $self = $this->model->where('username', $parent)->find()->toArray();
                $childWhere['user.parent_id'] = $self['id'];
            }

            $childWhere  = [];

//            if(!$this->isSuperAdmin) {
//                $childWhere ['user.top_parent'] = $this->auth->user_id;
//            }

            if(!$this->isSuperAdmin) {
                // 查询userid
                $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
                $childWhere[] = ['uid', 'IN', $user_id];
            }
            $otherWhere ['type'] = 2;
            $otherWhere ['remark'] = '彩金(+)';

            $total = $this->model
                ->with(['user', 'admin'])
                ->where($where)
                ->where($childWhere)
                ->where($otherWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['user', 'admin'])
                ->where($where)
                ->where($childWhere)
                ->where($otherWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                if (empty($item ['user']['parent_id'])) {
                    continue;
                }
                //查询父级
                $item ['parent.username'] = User::where('id', $item['user']['parent_id'])->value('username');
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

}
