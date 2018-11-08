<?php
namespace app\console\controller;

use app\console\Base;

class Common extends Base {


    /**
     * 文件上传
     */
    public function file_update () {

        $data = $_FILES['file'];

        $result = upload_file($data['name'], $data['tmp_name']);

        if ($result['status'] > 0) {
            return json(['code' => 200, 'msg' => '上传成功', 'data' => ['url' => $result['data']]]);
        }

        return json(['code' => 0, 'msg' => $result['msg']]);
    }

    /**
     * 获取阿里云js直传签名
     */
    public function getOssSignature () {

        $id  = config('ali_oss.accessKeyId');
        $key = config('ali_oss.accessKeySecret');

        // $host的格式为 bucketname.endpoint，请替换为您的真实信息。
        $host = config('ali_oss.bucket_host');

        // $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
        $callbackUrl =  SITE_URL . '/console/Common/callback';

        // 用户上传文件时指定的前缀。
        $dir = '';

        $callback_param = [
            'callbackUrl'      => $callbackUrl,
            'callbackBody'     => 'filename=${object}&size=${size}&mimeType=${mimeType}',
            'callbackBodyType' => 'application/x-www-form-urlencoded'
        ];
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 300;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = gmt_iso8601($end);


        //最大文件大小.用户可以自己设置
        $condition = [
            0 => 'content-length-range',
            1 => 0,
            2 => 1048576000
        ];
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = [
            0 => 'starts-with',
            1 => '$key',
            2 => $dir
        ];
        $conditions[] = $start;

        $arr = ['expiration'=>$expiration,'conditions'=>$conditions];
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = [
            'accessid'  => $id,
            'host'      => $host,
            'policy'    => $base64_policy,
            'signature' => $signature,
            'expire'    => $end,
            'callback'  => $base64_callback_body,
            'dir'       => $dir // 这个参数是设置用户上传文件时指定的前缀。
        ];
        return json($response);
    }

    public function callback () {
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = '';
        $pubKeyUrlBase64     = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            exit;
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);

        if ($pubKey == '') {
            exit;
        }

        // 4.获取回调body
        $body = file_get_contents('php://input');

        // 5.拼接待签名字符串
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path).PHP_EOL.$body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos).PHP_EOL.$body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);

        if ($ok == 1) {
            header("Content-Type: application/json");
            echo json_encode(['Status' => 'Ok']);
        } else {
            exit;
        }
    }

    /**
     * 三级联动，根据分类ID获取对应分类下的课程
     */
    public function getSearchCourse()
    {
        if(\think\Request::instance()->isPost()){

            $id = intval(input('id',0));
            if(!empty($id)){

                // 获取对应分类下课程列表
                $data = db('curriculum')->where(['cl_id'=>$id])->select();
                if($data){
                    return json(['code' => 200, 'msg' => '课程获取成功', 'data' => $data]);
                }
                return json(['code' => 0, 'msg' => '该分类下没有找到课程，请重新选择']);
            }
            return json(['code' => 0, 'msg' => '获取课程失败，缺少必要参数']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    /**
     * 三级联动，根据课程ID获取对应课程下的章节
     */
    public function getSearchChapter()
    {
        if(\think\Request::instance()->isPost()){

            $id = intval(input('id',0));
            if(!empty($id)){

                // 获取对应分类下课程列表
                $data = db('curriculum_chapter')->where(['cp_id'=>$id])->select();
                if($data){
                    return json(['code' => 200, 'msg' => '章节获取成功', 'data' => $data]);
                }
                return json(['code' => 0, 'msg' => '该课程下没有找到章节，请重新选择']);
            }
            return json(['code' => 0, 'msg' => '获取章节失败，缺少必要参数']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    /**
     * 三级联动，根据章节ID获取章节信息
     */
    public function getChapterInfo(){
        if(\think\Request::instance()->isPost()){

            $id = intval(input('id',0));
            if(!empty($id)){

                // 获取对应分类下课程列表
                $data = db('curriculum_chapter')->where(['id'=>$id])->find();
                if($data){
                    return json(['code' => 200, 'msg' => '章节获取成功', 'data' => $data]);
                }
                return json(['code' => 0, 'msg' => '没有找到该章节的记录']);
            }
            return json(['code' => 0, 'msg' => '获取章节失败，缺少必要参数']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

}