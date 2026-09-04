<?php
/**
 * *
 *  * ============================================================================
 *  * Created by PhpStorm.
 *  * User: Ice
 *  * 邮箱: ice@sbing.vip
 *  * 网址: https://sbing.vip
 *  * Date: 2019/9/19 下午3:20
 *  * ============================================================================.
 */

namespace app\common\controller;

use think\facade\Lang;
use think\facade\Validate;
use think\facade\View;
use think\facade\Event;
use think\facade\Config;
use app\common\library\Auth;

/**
 * 前台控制器基类.
 */
class Frontend extends BaseController
{
    /**
     * 布局模板
     *
     * @var string
     */
    protected $layout = '';

    /**
     * 无需登录的方法,同时也就不需要鉴权了.
     *
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录.
     *
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth.
     *
     * @var Auth
     */
    protected $auth = null;

    protected $template = null;
    protected $lang = null;
    protected $uid = null;

    public function _initialize()
    {
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
        $modulename = app()->http->getName();
        $controller = preg_replace_callback('/\.[A-Z]/', function ($d) {
            return strtolower($d[0]);
        }, $this->request->controller(), 1);

        $controllername = parseName($controller);
        $actionname = strtolower($this->request->action());

        // 如果有使用模板布局
        if ($this->layout) {
            View::engine()->layout('layout/' . $this->layout);
        }
        $this->auth = app()->auth;

        $template = $this->request->get('template', '');

        if ($template) {
            cookie('template', $template);
        }

        if (!$template) {
            $template = cookie('template');
        }

        if (!$template) {
            if (!$this->request->isPost()) {
                $this->redirect('/h5');
            }
        }


        // token
        $token = $this->request->server('HTTP_TOKEN',
            $this->request->request('token', \think\facade\Cookie::get('token')) ?: '');

        $user_id = session("user_id");

        if (empty($user_id)) {
            $user_id = cookie('user_id');
        }

        if (empty($user_id)) {
            $user_id = $this->request->header('User_id');
        }

        if (empty($user_id)) {
            $user_id = $this->request->header('Authorization');
        }

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //获取用户id
            $this->uid = $user_id;
            //检测是否登录
            if (!$this->uid) {
                if ($this->request->isPost()) {
                    error(__('Please login first'), 401);
                }

                $this->redirectTo('/user/login');
            }
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }

        $this->view->assign('user', $this->auth->getUser());

        // 语言检测
//        $lang = strip_tags(Lang::getLangSet());
        $site = Config::get('site');

        if ($this->request->get('lang')) {
            $lang = $this->request->get('lang');
            cookie('think_lang', $lang);
            cookie('think_var', $lang);
        } else {
            $lang = $site ['languages']['frontend'];
        }
        $this->lang = $lang;

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Event::trigger('upload_config_init', $upload);

        // 配置信息
        $config = [
            'site' => array_intersect_key($site,
                array_flip(['name', 'cdnurl', 'version', 'timezone', 'languages'])),
            'upload' => $upload,
            'modulename' => $modulename,
            'controllername' => $controllername,
            'actionname' => $actionname,
            'jsname' => 'frontend/' . str_replace('.', '/', $controllername),
            'moduleurl' => rtrim(request()->root(), '/'),
            'language' => $lang,
        ];

        Config::set(array_merge(Config::get('upload'), $upload), 'upload');

        // 配置信息后
        Event::trigger('config_init', $config);

        if ($template) {
            $this->template = $template;
        } else {
            //获取模板
            $this->template = isset($site ['frontend_template']) ? $site ['frontend_template'] : 'default';
        }

        //初始化模板
        $this->viewPath($this->template);

        // 加载当前控制器语言包
        $this->loadlang($lang, $this->request->controller());
        $this->assign('site', $site);
        $this->assign('config', $config);
        $this->assign('template', $this->template);
    }

    protected function viewPath($template = 'default')
    {
        $path = root_path() . 'app/index/view/' . (!empty($template) ? $template . '/' : '');

        //重新配置模板
        View::config(['view_path' => $path]);
    }

    /**
     * 加载语言文件.
     *
     * @param string $name
     */
    protected function loadlang($lang, $name)
    {
        Lang::load(app()->getAppPath() . 'lang/' . $lang . '.php');
    }

    /**
     * 渲染配置信息.
     *
     * @param mixed $name 键名或数组
     * @param mixed $value 值
     */
    protected function assignconfig($name, $value = '')
    {
        $this->view->config = array_merge($this->view->config ? $this->view->config : [],
            is_array($name) ? $name : [$name => $value]);
    }

    /**
     * 刷新Token
     */
    protected function token()
    {
        $token = $this->request->post('__token__');

        //验证Token
        if (!Validate::is($token, "token", ['__token__' => $token])) {
            $this->error(__('Token verification error'), '', ['__token__' => $this->request->buildToken()]);
        }

        //刷新Token
        $this->request->buildToken();
    }
}
