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

use app\common\model\Banner as BannerModel;
use app\common\model\IndexMsg;
use app\common\controller\Backend;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 首页配置
 *
 * @internal
 */
class Banner extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new BannerModel();
    }

    /**
     * @return mixed
     * @throws \Exception
     * 首页公告
     */
    public function notice()
    {
        $this->model = new IndexMsg;
        $row = $this->model->get('1');
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
        return $this->view->fetch();
    }
}
