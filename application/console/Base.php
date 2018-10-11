<?php
namespace app\console;

use think\Controller;
use think\Request;

class Base extends Controller
{

    // 请求对象
    protected $request;

    // 请求控制
    protected $controller;

    // 请求方法
    protected $method;

    //

    // 登录过去时间
    protected $login_exp = 3600;

    // 用户信息
    protected $userinfo;

    // 不需要登录验证的请求
    protected $isLoginArr = [
        'console/login/index',
        'console/login/get_user_info',
        'console/error/error_msg'
    ];


    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 入口方法
     */
    public function _entrance(Request $request, $controller, $method)
    {
        // 参数赋值
        $this->request = $request;
        $this->controller = $controller;
        $this->method = $method;

        // 获取请求地址
        $path = strtolower($this->request->path());

        if(!in_array($path,$this->isLoginArr)) {
            // 判断用户是否登录
            if($this->is_login()) {

                // 用户登录，执行请求
                return $this->$method();

            }else{
                $this->redirect('console/Login/index');
            }
        }


        if($path == 'console/login/index' || $path == 'console/login/get_user_info')
        {
            if($this->is_login()) {
                $this->redirect('console/Index/index');
            }
        }

        return $this->$method();


    }


    /**
     * 判断用户是否登录
     */
    private function is_login()
    {
        // 获取用户登录token
        $token = cookie('USER-TOKEN');

        if(!empty($token)) {

            // 使用 token 从 session 中获取用户信息
            if(session($token)) {
                // 用户信息存在，返回true
                $this->userinfo = session($token);
                return true;
            }

            return false;
        }

        return false;
    }




}