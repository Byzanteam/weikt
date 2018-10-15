<?php
namespace app\api;

use think\Controller;
use think\Request;

class Base extends Controller
{

    // 豁免访问权限验证的数组（全小写）
    public $excuse_arr = [
        'v1/index/index',
        'v1/common/generate_config',
        'v1/login/user_login',
    ];

    // 用户信息
    public $userinfo;

    // 请求对象
    protected $request;


    public function __construct()
    {
        parent::__construct();
        $this->request = Request::instance();
    }



}