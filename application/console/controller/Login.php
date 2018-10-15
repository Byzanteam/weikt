<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/23
 * Time: 10:33
 */

namespace app\console\controller;

use app\console\Base;

class Login extends Base
{

    /**
     * 对接了了登录步骤：
     * 1.进入登录页面的时候，调用【获取授权码】接口（GET /oauth/authorize）获取用户授权码
     * 2.在回调地址，用获取到的【授权码】获取【access_token】（POST /oauth/token）
     * 3.使用【access_token】调用【获取用户信息】接口（GET /api/v1/user）获取用户信息
     * 4.用户信息获取成功，session存储用户信息，重定向到后台首页
     */


    /**
     * 登录第一步：获取授权码，获取成功，到回调接口接受 code
     */
    public function index()
    {

        // 拼接 获取授权码 api 地址
        $url = config('llapi.formal_url').'/oauth/authorize';

        // 拼接 client_id
        $url .= '/?client_id='.config('llapi.client_id');

        // 拼接 response_type
        $url .= '&response_type='.config('llapi.response_type');

        // 拼接 回到地址 redirect_uri
        $url .= '&redirect_uri='.urlencode(config('llapi.console_redirect_uri'));

        // 跳转 了了登录 扫码页面
        $this->redirect($url);

    }


    /**
     * 接受授权码，获取 access_token 并 获取用户信息
     */
    public function get_user_info()
    {

        // 获取用户授权码
        $code = input('code','','strip_tags,trim');

        // 拼接 获取 token api 的地址
        $url = config('llapi.formal_url').'/oauth/token';

        $param['client_id'] = config('llapi.client_id');
        $param['client_secret'] = config('llapi.client_secret');
        $param['code'] = $code;
        $param['grant_type'] = 'authorization_code';
        $param['redirect_uri'] = config('llapi.console_redirect_uri');

        // 初始化 curl
        $ch = curl_init();

        // curl 设置
        curl_setopt($ch, CURLOPT_URL, $url);    # 需要获取的URL地址
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);  # 设置 请求超时时间 单位秒
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 设置获取到的内容不直接输出，而是返回存储到一个变量中
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # 禁止 cURL 验证对等证书

        curl_setopt($ch, CURLOPT_POST, true);   # 设置是否启用POST方式请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));    # POST 请求的参数 如果传递的是数组话的 Content-Type头会被设置为 multipart/form-data

        $output = curl_exec($ch);

        // 结束curl请求
        curl_close($ch);

        $data = json_decode($output,true);

        if($output == false || $data == false || !is_array($data) || array_key_exists('error',$data)){
            // 获取token失败
        }

        // 获取token成功

        // 请求成功，将请求结果 存储到 session
        session('weikt_token',$data);

        // =============================================================================================================
        // 使用得到的 token 获取用户信息

        // 拼接api地址
        $url = config('llapi.formal_url').'/api/v1/user';
        // 拼接token
        $url .= '/?access_token='.$data['access_token'];

        // 初始化 curl
        $ch = curl_init();

        // curl 设置
        curl_setopt($ch, CURLOPT_URL, $url);    # 需要获取的URL地址
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);  # 设置 请求超时时间 单位秒
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 设置获取到的内容不直接输出，而是返回存储到一个变量中
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # 禁止 cURL 验证对等证书

        $result = curl_exec($ch);

        // 结束curl请求
        curl_close($ch);

        // 结果 json 字符串转数组
        $info = json_decode($result,true);

        // 判断请求结果
        if($result == false || $info == false || !is_array($info) || array_key_exists('error',$info)){
            // 获取用户信息失败
        }

        // 请求成功
        // 判断用户所在组织，是否允许登录后台
        // 生成登录token
        // session 记录用户信息
        // cookie 写用户登录token

        // 获取允许登录组织
        $teacher_organ = config('llapi.teacher_organ');
        // 获取用户所在组织
        $user_organ = $info['root_organization_ids'];
        // 是否允许登录 默认false
        $is_log_in = false;

        foreach ($user_organ as $k => $v){
            if(in_array($v,$teacher_organ)){
                $is_log_in = true;
            }
        }

        if($is_log_in){
            // 允许登录
            // 生成登录token
            $token = strtoupper(md5('weikt_'.md5($info['id'].time())));

            // session 存储 用户信息
            session($token,$info);

            // 写入登录cookie
            cookie('USER-TOKEN', $token ,$this->login_exp);
            cookie('USER-LOGIN-TIME', time());

            // 登录成功，跳转到后台首页
            $this->redirect('/console/Index/index');
        }
    }
}
