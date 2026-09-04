<?php

namespace app\admin\controller\auth;

use app\common\model\AdminTotp as AdminTotpModel;
use app\common\controller\Backend;
use OTPHP\TOTP;
use think\exception\ValidateException;
use think\facade\Db;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * 验证器管理.
 */
class AdminTotp extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new AdminTotpModel();
    }

    /**
     * 查看
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
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            foreach ($list as &$item) {
                $item ['qrcode'] = $this->buildBase64Image($item ['secret']);
            }

            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace('\\model\\', '\\validate\\', get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        validate($validate)->scene($this->modelSceneValidate ? 'edit' : $name)->check($params);
                    }
                    $otp = TOTP::create();
                    $secret = $otp->getSecret();
                    $params ['secret'] = $secret;
                    $result = $this->model->save($params);
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
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
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
                // 非userID=1禁止编辑ID=1
                if ($ids == 1 && $this->auth->id != 1) {
                    $this->success();
                }
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
        $this->view->assign('row', $row);

        return $this->view->fetch();
    }

    /**
     * 重置
     */
    public function reset($ids = null)
    {
        if ($this->request->isPost()) {
            // 非userID=1禁止编辑ID=1
            if ($ids == 1 && $this->auth->id != 1) {
                $this->success();
            }

            $result = false;
            try {
                // 生成新的
                $otp = TOTP::create();
                $secret = $otp->getSecret();
                $result = $this->model->where('id', $ids)->update(['secret' => $secret]);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            if ($result) {
                $this->success();
            } else {
                $this->error(__('Operation failed'));
            }


        }
        $this->error();
    }

    /**
     * 生成base64位图
     */
    public function buildBase64Image($secret)
    {
        $options = new QROptions([
            'version' => 5,                             //二维码版本
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,      //生成图片
            'eccLevel' => QRCode::ECC_L,                 //错误级别
            'scale' => 10,                                   //二维码大小
        ]);
        $qrcode = new QRCode($options);


        //将二维码直接生成base64格式的图片
        return $qrcode->render($secret);
    }

}
