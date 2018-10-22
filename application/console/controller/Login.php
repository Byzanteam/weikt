<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/23
 * Time: 10:33
 */

namespace app\console\controller;

use app\console\Base;
use app\console\model\UserBasic;

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
    public function get_user_info () {

        // 获取用户授权码
        $code = input('code','','strip_tags,trim');

        $output = get_user_token($code, config('llapi.console_redirect_uri'));

        $data = json_decode($output,true);

        if($output == false || $data == false || !is_array($data) || array_key_exists('error',$data)){
            // 获取token失败
            echo $data['error_description'];exit;
        }

        // 获取token成功

        // 请求成功，将请求结果 存储到 session
        session('weikt_token',$data);

        $result = get_user_info($data['access_token']);

        $info = json_decode($result, true);

        // 获取用户信息失败
        if($result == false || $info == false || !is_array($info) || array_key_exists('error', $info)){

            echo $info['error_description'];exit;
        }

        // 请求成功
        // 判断用户所在组织，是否允许登录后台
        // 生成登录token
        // session 记录用户信息
        // cookie 写用户登录token

        // 获取允许登录组织
        $teacher_organ = config('llapi.teacher_organ');

        // 是否允许登录 默认false
        $is_log_in = false;

        foreach ($info['root_organization_ids'] as $k => $v){
            if(in_array($v,$teacher_organ)){
                $model = new UserBasic();

                $model->update_user_info($info);

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
