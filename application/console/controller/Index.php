<?php
namespace app\console\controller;

use app\console\Base;

class Index extends Base
{


    /**
     * 登录后台整理框架
     */
    public function index()
    {
        $this->assign('userinfo', $this->userinfo);
        return $this->fetch('/console/index/index');
    }

    /**
     * 首页页面
     */
    public function main()
    {
        return $this->fetch('/console/index/main');
    }


}