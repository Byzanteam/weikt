<?php
namespace app\api\v1\controller;

use app\api\Base;

class Common extends Base
{

    public function upload()
    {
        $media_id = $_POST['media_id'];
        $access_token = getAccessToken();

        $path = './weixinrecord/';   //保存路径，相对当前文件的路径
        $outPath = './php/weixinrecord/';  //输出路径，给show.php 文件用，上一级

        if(!is_dir($path)){
            mkdir($path);
        }

        //微 信上传下载媒体文件
        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}';

        $filename = 'wxupload_'.time().rand(1111,9999).'.amr';
        downAndSaveFile($url,$path.'/'.$filename);

        $data['path'] = $outPath.$filename;
        $data['msg'] = 'download record audio success!';
        // $data['url'] = $url;

        return json(['code' => 200, 'msg' => '获取成功', 'data' => $data]);
        echo json_encode($data);
    }

    /**
     * 获取了了 js_ticket
     */
    public function get_ticket()
    {

        // 当前时间-60秒
        $new_time = time() - 60;

        if(session('weikt_jssdk')){
            // 如果session 存在，则判断是否过去，如果没有过过期
            if($new_time < session('weikt_jssdk.expired_at')){
                return session('weikt_jssdk.ticket');
            }
        }

        // 设置请求的header参数
        $auto = 'Authorization:'.config('llapi.v4_api_Authorization');
        $headers = [$auto];

        // 设置请求URL
        $url = config('llapi.formal_url').'/api/v4/wechat_clients/jsapi_ticket';

        // 初始化 curl
        $chs = curl_init();
        // curl 设置
        curl_setopt($chs, CURLOPT_URL, $url);    # 需要获取的URL地址
        curl_setopt($chs, CURLOPT_TIMEOUT, 40);  # 设置 请求超时时间 单位秒
        curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);    # 设置获取到的内容不直接输出，而是返回存储到一个变量中
        curl_setopt($chs, CURLOPT_SSL_VERIFYPEER, false); # 禁止 cURL 验证对等证书
        curl_setopt($chs, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($chs);
        // 结束curl请求
        curl_close($chs);

        $data = json_decode($result,true);

        // 判断请求是否成功
        if($result != false && $data != false && is_array($data) && !array_key_exists('error',$data)){

            // 请求成功，将结存储到session中
            session('weikt_jssdk',$data);

            return json(['code' => 200, 'msg' => '获取成功', 'data' => $data]);
        }
        return false;
    }

    /**
     * 设置微信jssdk权限验证配置
     */
    public function generate_config()
    {
        if($this->request->isGet()) {

            if($js_ticket = $this->get_ticket()){

                $timestamp = time();
                $url = SITE_URL . $_SERVER['REQUEST_URI'];
                $nonceStr = $this->createNonceStr();

                // 这里参数的顺序要按照 key 值 ASCII 码升序排序
                $string = 'jsapi_ticket=$js_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url';

                // 加密
                $signature = sha1($string);

                $signPackage = [
                    'appId' => config('llapi.appid'),
                    'nonceStr' => $nonceStr,
                    'timestamp' => $timestamp,
                    'url' => $url,
                    'signature' => $signature,
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
    private function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }



}

