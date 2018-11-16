<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\console\model\UserBasic;

class Login extends Base
{

    /**
     * 对接了了登录步骤：
     * 1.进入登录页面的时候，调用【获取授权码】接口（GET /oauth/authorize）获取用户授权码
     * 2.在回调地址，用获取到的【授权码】获取【access_token】（GET /oauth/token）
     * 3.使用【access_token】调用【获取用户信息】接口（GET /api/v1/user）获取用户信息
     * 4.用户信息获取成功，session存储用户信息，重定向到后台首页
     */

    public function __construct () {
        parent::__construct();
    }

    /*  用户登录   */
    public function user_login () {

        $code = input('code','','strip_tags,trim'); // 用户授权码

        if (!empty($code)) {

            // 获取用户token
            if ($token = get_user_token($code, config('llapi.api_redirect_uri'))) {

                // 获取用户信息
                if ($user_info = get_user_info($token)) {

                    $userModel = new UserBasic();

                    if ($login_token = $userModel->update_user_info($user_info, false)) {
                        $url = SITE_URL . '/view/index.html#/?login_token=' . $login_token;

                        // 用户登录成功
                        $this->redirect($url);
                    }
                }

                echo '<div style="font-size: 20px; color: red; text-align: center; padding-top: 10%;">用户登录失败</div>';
                exit;
            }

            echo '<div style="font-size: 20px; color: red; text-align: center; padding-top: 10%;">授权认证失败</div>';
            exit;
        } else {
            // 拼接 获取授权码 api 地址
            $url = config('llapi.formal_url') . '/oauth/authorize/?';

            // 拼接 client_id
            $url .= 'client_id=' . config('llapi.client_id');

            // 拼接 response_type
            $url .= '&response_type=' . config('llapi.response_type');

            // 拼接 回到地址 redirect_uri
            $url .= '&redirect_uri=' . config('llapi.api_redirect_uri');

            $url .= '&_ns_id=200&limit_wechat_user=true';

            $this->redirect($url);
        }
    }

}

