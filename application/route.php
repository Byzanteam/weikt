<?php

use \think\Route;
use \think\Request;


// =====================================================================================================================
// 路由全不匹配时的处理路由
//Route::miss('Error/index');


// =====================================================================================================================
// 后台访问路由控制
Route::any("/",function(Request $request){

    $controller = 'index';
    $namespace_path = "app\\console\\controller\\".ucwords($controller);
    return (new $namespace_path)->_entrance($request,$controller,'index');

});

Route::any("console",function(Request $request){

    $controller = 'index';
    $namespace_path = "app\\console\\controller\\".ucwords($controller);
    return (new $namespace_path)->_entrance($request,$controller,'index');

});

Route::any("console/:controller/",function(Request $request, $controller){

    $namespace_path = "app\\console\\controller\\".ucwords($controller);
    return (new $namespace_path)->_entrance($request,$controller,'index');

});


Route::any("console/:controller/:method",function(Request $request, $controller, $method){

    $namespace_path = "app\\console\\controller\\".ucwords($controller);
    return (new $namespace_path)->_entrance($request,$controller,$method);

});

// =====================================================================================================================
// api 访问路由控制
/**
 * 1.获取客户端的请求对象
 * 2.获取请求的版本号、控制器、方法
 * 3.判断请求控制器文件、类、方法是否存在
 * 4.判断请求方法是否在豁免数组中
 * 5.对非豁免请求进行权限验证（暂时未定）
 * 6.实例化控制器，执行请求
 */
Route::group('api',function(){

    Route::any(':version/:controller/:method',function(){

        // 获取本次请求实例
        $request = Request::instance();

        // 获取用户信息备用
        $user_ip = $request->ip();

        // 获取用户请求的基本信息，版本号、控制器、方法，用来检验请求是否有效

        $version = strtolower($request->param('version'));
        $controller = strtolower($request->param('controller'));
        $method     = $request->param('method');

        if(empty($version) || empty($controller) || empty($method)){
            return json(['code' => -1, 'msg' => '缺少必要参数']);
        }

        // 验证请求控制器、方法是否存在
        $namespace_path = check_file($version,$controller,$method);

        if(!$namespace_path){
            return json(['code' => -1, 'msg' => '请求错误，该请求无效！']);
        }

        // 实例化控制器
        $controller_obj = new $namespace_path;

        // 获取豁免权限验证的操作方法数组
        $excuse = $controller_obj->excuse_arr;

        // 判断本次请求是否在豁免数组中
        if(!in_array($version.'/'.$controller.'/'.$method, $excuse)){

            // 非豁免请求，进行权限验证，获取头部 x-auth-info
            $auth = $request->header('x-auth-info');
            if(empty($auth)){
                return json(['code' => -1, 'msg' => '请求错误，无访问权限']);
            }

            // 判断 login_token 是否正确
            if(!session($auth)){
                return json(['code' => -1, 'msg' => '请求错误，无访问权限']);
            }

            // 获取用户ID
            $user_id = session($auth);
            // 获取用户信息
            $userinfo = db('user_basic')->where(['id' => $user_id])->find();

            if(empty($userinfo)){
                return json(['code' => -1, 'msg' => '请求错误，用户不存在']);
            }

            $controller_obj->userinfo = $userinfo;

        }

        // 检测完毕，执行请求
        return $controller_obj->$method();



    });




});


/**
 * 验证请求的控制器文件、类、方法是否存在
 * @param $version  本次请求的版本号
 * @param $controller 本次请求的控制器
 * @param $method 本次请求的方法
 * @return 成功返回控制器命名空间地址
 */
function check_file($version, $controller, $method)
{

    // 拼接控制器类文件路径
    $controller_path = APP_PATH.'api/'.$version.'/controller/'.ucfirst($controller).'.php';

    // 判断控制器类文件是否存在
    if(is_file($controller_path)){

        // 文件存在，拼接类命名空间
        $namespace_path = 'app\\api\\'.$version.'\\controller\\'.ucfirst($controller);
        // 获取类方法数组
        $fun_arr = get_class_methods($namespace_path);

        if(!empty($fun_arr)){

            // 判断请求的方法是否存在
            if(in_array($method,$fun_arr)){
                return $namespace_path;
            }
        }
    }

    return false;
}



function check_auth()
{

}




