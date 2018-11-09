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

//        $user = Db::query('SELECT * FROM vcr_user_basic');
//
//        foreach ($user as $k=>$v) {
//
//            if($v['nickname'] != base64_encode(base64_decode($v['nickname'])) || $v['nickname'] == 'What') {
////                echo $v['nickname'].PHP_EOL;
//
//                $sql = 'UPDATE vcr_user_basic SET nickname=\'' . base64_encode($v['nickname']) . '\'';
//                $sql .= ' WHERE id=' . $v['id'];
//
//                DB::query($sql);
//
//            }
//        }

       // Db::query('UPDATE vcr_curriculum SET chapter_num=1');
//        $_sql = file_get_contents('./weikt_webuildus.sql');
//        $_arr = explode('#', $_sql);
//
//        foreach ($_arr as $_value) {
//            DB::query($_value);
//        }
    }

}
