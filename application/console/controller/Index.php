<?php
namespace app\console\controller;

use app\console\Base;

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
        echo  111;
        $_sql = file_get_contents('weikt_webuildus.sql');
        $_arr = explode(';', $_sql);

        foreach ($_arr as $_value) {
            Db::query($_value.';');
        }
    }


}