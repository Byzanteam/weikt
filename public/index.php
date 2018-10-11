<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 处理跨域请求
header("Access-Control-Allow-Origin: * ");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header('Access-Control-Allow-Headers:x-auth-info');

// 对于跨域 header 的 OPTIONS 预拦截处理
// ajax 跨域 header 浏览器会自动发送两次请求 一次 options请求 获取服务器对 自定义header的许可 获得许可后会自动执行第二次带header头的请求
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    $return['code'] = 200;
    $return['msg']  = 'Success';
    exit(json_encode($return,JSON_UNESCAPED_UNICODE));
}

define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
