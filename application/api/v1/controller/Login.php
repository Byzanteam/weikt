<?php
namespace app\api\v1\controller;

use app\api\Base;

class Login extends Base
{

    /**
     * 对接了了登录步骤：
     * 1.进入登录页面的时候，调用【获取授权码】接口（GET /oauth/authorize）获取用户授权码
     * 2.在回调地址，用获取到的【授权码】获取【access_token】（GET /oauth/token）
     * 3.使用【access_token】调用【获取用户信息】接口（GET /api/v1/user）获取用户信息
     * 4.用户信息获取成功，session存储用户信息，重定向到后台首页
     */

    public function __construct()
    {
        parent::__construct();
    }

    /*  用户登录   */
    public function user_login () {
        $code = input('code','','strip_tags,trim'); // 用户授权码
        if(!empty($code)) {

            // 获取用户token
            if($token = $this->get_user_token($code)) {
                // 获取用户信息
                if($login_token = $this->get_user_info($token)){
                    $url = SITE_URL . '/view/index.html#/?login_token=' . $login_token;
                    // 用户登录成功
                    $this->redirect($url);
                }
                return json(['code' => 404, 'msg' => '用户登录失败', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '授权认证失败', 'data' => []]);
        } else {
            // 拼接 获取授权码 api 地址
            $url = config('llapi.formal_url').'/oauth/authorize/?';

            // 拼接 client_id
            $url = $url.'client_id='.config('llapi.client_id');

            // 拼接 response_type
            $url = $url.'&response_type='.config('llapi.response_type');

            // 拼接 回到地址 redirect_uri
            $url = $url.'&redirect_uri='.config('llapi.api_redirect_uri');

            $this->redirect($url);
        }
    }


    /**
     * 获取用户access_token
     * @param code 用户授权码
     */
    protected function get_user_token($code)
    {
        // 获取到授权码，通过授权码 获取 token
        // 拼接 获取 token api 的地址
        $url = config('llapi.formal_url').'/oauth/token';
        // 整理请求参数
        $param['client_id'] = config('llapi.client_id');
        $param['client_secret'] = config('llapi.client_secret');
        $param['code'] = $code;
        $param['grant_type'] = 'authorization_code';
        $param['redirect_uri'] = config('llapi.api_redirect_uri');

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
        // 结果 json 转 数组
        $data = json_decode($output,true);
        // 判断请求是否成功
        if($output != false && $data != false && is_array($data) && !array_key_exists('error',$data) && array_key_exists('access_token',$data)) {
            // 获取成功，返回 token 字符串
            return $data['access_token'];
        }

        // 失败fasle
        return false;
    }

    /**
     * 获取用户信息
     * @param token 授权码换取的用户token
     */
    protected function get_user_info($token)
    {
        //  token 获取成功 ，使用token 获取用户信息
        // 拼接api地址
        $url = $url = config('llapi.formal_url').'/api/v1/user';
        // 拼接token
        $url = $url.'/?access_token='.$token;

        // 初始化 curl
        $chs = curl_init();
        // curl 设置
        curl_setopt($chs, CURLOPT_URL, $url);    # 需要获取的URL地址
        curl_setopt($chs, CURLOPT_TIMEOUT, 40);  # 设置 请求超时时间 单位秒
        curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);    # 设置获取到的内容不直接输出，而是返回存储到一个变量中
        curl_setopt($chs, CURLOPT_SSL_VERIFYPEER, false); # 禁止 cURL 验证对等证书
        $result = curl_exec($chs);
        // 结束curl请求
        curl_close($chs);

        $user_info = json_decode($result,true);

        // 判断 curl 请求是否成功
        if($result != false || $user_info != false || is_array($user_info) || !array_key_exists('error',$user_info)) {

            // 获取用户信息成功
            // 存储用户信息到数据库，存在则更新
            if($login_token = $this->update_user_info($user_info)) {

                // 用户信息处理成功，返回login_token
                return $login_token;

            }
            // 用户信息处理失败，返回false
            return false;
        }
        // 获取用户信息失败，返回false
        return false;
    }


    /**
     * 用户信息处理
     * @param userinfo 了了用户信息
     */
    private function update_user_info($userinfo)
    {
        // 查询用户是否存在
        $info = db('user_basic')->where(['ll_id'=>$userinfo['id']])->find();

        if(empty($info)) {
            // 用户不存在，直接进行添加
            // 整理用户数据
            $data['ll_id']      = $userinfo['id'];
            $data['name']       = $userinfo['name'];
            $data['nickname']   = $userinfo['nickname'];
            $data['phone']      = $userinfo['phone'];
            $data['openid']     = $userinfo['openid'];
            $data['headimgurl'] = $userinfo['headimgurl'];
            $data['registrationtime'] = time();
            $data['last_time']  = time();
            $data['studytime']  = 1;
            $data['curriculum'] = 0;

            if(db('user_basic')->insert($data)) {
                // 用户添加成功，获取用户ID
                $user_id = db('user_basic')->getLastInsID();

                // 生成用户 登录token
                $login_token = $this->create_login_token($user_id);

                // 返回用户 登录token
                return $login_token;
            }
            return false;
        }

        // 用户已存在，对用户基础信息进行更新
        // 包括：name,nickname,phone,headimgurl
        if(!empty($userinfo['name'])) {
            $upDate['name'] = $userinfo['name'];
        }
        if(!empty($userinfo['nickname'])) {
            $upDate['nickname'] = $userinfo['nickname'];
        }
        if(!empty($userinfo['phone'])) {
            $upDate['phone'] = $userinfo['phone'];
        }
        if(!empty($userinfo['headimgurl'])) {
            $upDate['headimgurl'] = $userinfo['headimgurl'];
        }
        if(!empty($userinfo['openid'])) {
            $upDate['openid'] = $userinfo['openid'];
        }

        // 判断本次登录时间 是否已过 上次登录时间 的晚上 23:59 ，过了则 累加学习时间 +1
        // 获取本次登录时间
        $newTime = time();
        // 获取上次登录时间的晚上23:59
        $time = strtotime(date('Y-m-d',$info['last_time']).' 23:59:00');

        // 判断 如果本次登录时间 大于 上次登录时间的23:59 则表示已经过了一天，总学习时间 +1
        if($newTime > $time) {
            $upDate['studytime'] = $info['studytime'] + 1;
        }

        // 更新本次登录时间
        $upDate['last_time'] = $newTime;


        // 直接更新，成功返回 true
        // 失败，使用旧数据继续执行 返回 true
        db('user_basic')->where(['ll_id'=>$userinfo['id']])->update($upDate);

        $login_token = $this->create_login_token($info['id']);

        return $login_token;

    }


    /**
     * 生成用户 登录token
     */
    private function create_login_token($id)
    {
        // 生成 login_token
        $login_token = strtoupper(md5('weikt-'.md5($id.'-'.time())));

        // 存储session k:login_token  v:用户ID
        session($login_token,$id);

        // 返回login_token
        return $login_token;
    }

}

