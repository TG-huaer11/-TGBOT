<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\App;
use think\facade\Db;

/**
 * Domain.
 *
 * @icon fa fa-user
 */
class Domain extends Backend
{
    protected $domain_path = null;

    public function _initialize()
    {
        $this->domain_path = "./domain/s{}.conf";
    }

    /**
     * 查看.
     */
    public function index()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if (file_exists($this->domain_path)) {

                $domain_source = file_get_contents($this->domain_path);
                $domain_source = substr(trim($domain_source), 12);
                $domain_source = trim($domain_source, ";");
                $domain = explode(" ", $domain_source);

                $list = [];

                foreach ($domain as $v) {
                    $list[] = [
                        'domain' => $v,
                    ];
                }
                $result = ['total' => count($list), 'rows' => $list];

                return json($result);
            } else {
                $this->error('Operation failed');
            }
        }
        return $this->view->fetch();
    }

    public function del($domain = null)
    {
        $del_domain = $this->request->get('domain', '');
        $check = $this->is_domain($del_domain);
        if ($check) {
            if (file_exists($this->domain_path)) {
                $domain_source = file_get_contents($this->domain_path);
                $domain_source = substr(trim($domain_source), 12);
                $domain_source = trim($domain_source, ";");
                $domain = explode(" ", $domain_source);
                // DEL
                $domain_str = '';
                foreach ($domain as $v) {
                    if ($v != $del_domain) {
                        $domain_str .= $v . " ";
                    }
                }
                file_put_contents($this->domain_path, 'server_name ' . trim($domain_str) . ';');
                $this->nginx_reload();
                $this->success();
            } else {
                $this->error('Config Error');
            }
        } else {
            $this->error('Not Domain');
        }
    }

    public function add()
    {

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $password = $params['password'];
            $add_domain = $params ['domain'];

            if ($password && $password != '' && $add_domain && $add_domain != '') {
                $_password = Db::name('system_config')->where('name', 'orange_domain')->value('value');
                if ($_password == $password) {
                    $check = $this->is_domain($add_domain);
                    if ($check) {
                        if (file_exists($this->domain_path)) {
                            $domain_source = file_get_contents($this->domain_path);
                            $domain_source = substr(trim($domain_source), 12);
                            $domain_source = trim($domain_source, ";");
                            $domain = explode(" ", $domain_source);
                            // ADD
                            $domain[] = $add_domain;
                            $domain_str = '';
                            foreach ($domain as $v) {
                                $domain_str .= $v . " ";
                            }
                            file_put_contents($this->domain_path, 'server_name ' . trim($domain_str) . ';');
                            $this->nginx_reload();
                            $this->success();
                        } else {
                            $this->error('Config Error');
                        }
                    } else {
                        $this->error('Not Domain');
                    }
                } else {
                    $this->error('Password Error');
                }
            } else {
                $this->error('Params Error');
            }
        }

        return $this->view->fetch();
    }

    private function is_domain($str = '')
    {
        return !empty($str) && !preg_match('/^-|-$|--|-\.|\.-/', $str) && preg_match('/^([\w-]+\.)?[\w-]+' . $this->reg_exp_suffix() . '$/', $str) ? true : false;
    }

    private function reg_exp_suffix()
    {
        return '(' . str_replace('.', '\.', implode('|', $this->allow_domain_suffix())) . ')';
    }

    private function allow_domain_suffix()
    {
        $arr = array(
            '.com',
            '.net',
            '.org',
            '.cc',
            '.info',
            '.biz',
            '.mobi',
            '.us',
            '.me',
            '.co',
            '.co.jp',
            '.edu',
            '.tv',
            '.la',
            '.xyz',
            '.club',
        );
        sort($arr);
        return array_unique(array_reverse($arr));
    }

    private function nginx_reload()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:19999/reload");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}
