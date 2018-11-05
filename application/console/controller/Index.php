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
        Db::query('ALTER TABLE `vcr_user_basic`
MODIFY COLUMN `name`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\' COMMENT \'用户姓名\' AFTER `ll_id`');
//        $_sql = file_get_contents('./weikt_webuildus.sql');
//        $_arr = explode('#', $_sql);
//
//        foreach ($_arr as $_value) {
//            DB::query($_value);
//        }
    }

}
