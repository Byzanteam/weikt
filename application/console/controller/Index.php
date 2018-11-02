<?php
namespace app\console\controller;

use app\console\Base;
use think\Db;

class Index extends Base
{


    /**
     * 登录后台整理框架
     */
    public function index () {
        $this->assign('userinfo', $this->userinfo);
        return $this->fetch('/console/index/index');
    }

    /**
     * 首页页面
     */
    public function main () {
        return $this->fetch('/console/index/main');
    }

    public function sql () {
//print_r(Db::query('SHOW TABLES'));exit;
        $_sql = file_get_contents('./weikt_webuildus.sql');
        $_arr = explode('#', $_sql);

        foreach ($_arr as $_value) {
            DB::query($_value);
        }
    }


}