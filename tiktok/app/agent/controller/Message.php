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

use app\common\model\Admin;
use app\common\model\Message as MessageModel;
use app\common\model\User;
use app\common\controller\Backend;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 消息管理
 *
 * @internal
 */
class Message extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new MessageModel();
    }

    /**
     * 充值列表
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
            // 所属代理
            $parent = '';

            $filter = json_decode($this->request->get('filter'), true);
            if (!empty($filter['parent.username'])) {
                $parent = $filter['parent.username'];
            }

            $otherWhere = [];
            if ($parent) {
                $pid = User::where('username', $parent)->value('id');
                if ($pid) {
                    $otherWhere ['user.parent_id'] = $pid;
                }
            }

            if(!$this->isSuperAdmin) {
                // 查询userid
                $user_id = \app\admin\model\User::where('top_parent', $this->auth->user_id)->column('id');
                $otherWhere[] = ['uid', 'IN', $user_id];
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams(null, ['parent.username']);

            $total = $this->model
                ->with(['user'])
                ->where($where)
                ->where($otherWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['user'])
                ->where($where)
                ->where($otherWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select()
                ->toArray();

            if ($list) {
                foreach ($list as &$v) {
                    $adminer = Admin::where('id', $v['sid'])->value('username');
                    if ($adminer) {
                        $v['adminer'] = $adminer;
                    } else {
                        $v['adminer'] = 'SYSTEM';
                    }
                    $v['parent']['username'] = '';
                    if ($v ['user']['parent_id']) {
                        $v['parent']['username'] = User::where('id', $v ['user']['parent_id'])->value('username');
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
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
                        $name     = str_replace('\\model\\', '\\validate\\', get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? $name : $this->modelValidate;
                        $pk       = $row->getPk();
                        if (!isset($params[$pk])) {
                            $params[$pk] = $row->$pk;
                        }
                        validate($validate)->scene($this->modelSceneValidate ? 'edit' : $name)->check($params);
                    }

                    $params ['create_at'] = strtotime($params ['create_at']);

                    $result = $row->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
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
        $row->create_at = date('Y-m-d H:i:s', $row->create_at);
        $this->view->assign('row', $row);

        return $this->view->fetch();
    }
}
