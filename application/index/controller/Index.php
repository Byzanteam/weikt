<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 20:26
 */

namespace app\index\controller;


class Index {

    public function index () {
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
