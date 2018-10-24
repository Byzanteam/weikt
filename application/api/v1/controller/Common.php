<?php
namespace app\api\v1\controller;

use app\api\Base;

class Common extends Base {

    /**
     * 设置微信jssdk权限验证配置
     */
    public function generate_config () {
        if($this->request->isGet()) {

            $js_ticket = get_ticket();
            if($js_ticket){
                $timestamp = time();
                $url = input('curUrl');
                $nonceStr = $this->createNonceStr();

                // 这里参数的顺序要按照 key 值 ASCII 码升序排序
                $string  = 'jsapi_ticket=' . $js_ticket;
                $string .= '&noncestr=' . $nonceStr;
                $string .= '&timestamp=' . $timestamp;
                $string .= '&url=' . $url;

                // 加密
                $signature = sha1($string);

                $signPackage = [
                    'appId' => config('llapi.appid'),
                    'nonceStr' => $nonceStr,
                    'timestamp' => $timestamp,
                    'url' => $url,
                    'signature' => $signature,
                    'js_ticket' => $js_ticket,
                    'rawString' => $string
                ];

                return json(['code' => 200, 'msg' => 'jssdk获取成功', 'data' => $signPackage]);
            }
            return json(['code' => 404, 'msg' => '获取js_ticket失败', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @return string
     */
    private function createNonceStr ($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

}

