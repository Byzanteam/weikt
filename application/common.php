<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * * 获取学习记录排名 / 排行榜单
 * @param int $type 1.获取排行榜的 2-获取指定排行数
 * @param int $id   2.获取用户排行是，需要传递的用户信息
 * @param int $time 时间范围 0.总 1.周 2.月
 * @param int $limit 查询数量
 * @return bool|mixed
 * @throws \think\db\exception\BindParamException
 * @throws \think\exception\PDOException
 */
function get_user_rank_no ($type=1, $id=0, $time=0, $limit=10) {

    if($type != 1) {
        if(empty($id)) {
            return false;
        }

        $where = 'WHERE user_id='.$id;
        $limit = '';
    }else{
        $where = '';
        $limit = 'LIMIT '.$limit;
    }


    $child_where = '';

    // 查询时间
    if($time == 1) {
        // 本周一
        $start_time = strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
        // 本周日
        $end_time   = strtotime(date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600)));

        // 获取 当前周的开始时间和结束时间
        $child_where = ' WHERE study_date>=' .$start_time. ' AND study_date<=' .$end_time;

    }elseif($time == 2) {
        // 本月1号
        $start_time = strtotime(date('Y-m-d', strtotime(date('Y-m', time()) . '-01 00:00:00')));
        // 本月最后一天
        $end_time   = strtotime(date('Y-m-d', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00')));

        // 获取 当前月的开始时间和结束时间
        $child_where = ' WHERE study_date>=' .$start_time. ' AND study_date<=' .$end_time;

    }


    // 对学习记录进行分组统计
    $GroupCountSql = 'SELECT user_id, COUNT(*) AS num FROM vcr_user_study' .$child_where. ' GROUP BY user_id ORDER BY num DESC';


    $sql = 'SELECT basic.name, c_tmp.* FROM vcr_user_basic basic JOIN (SELECT user_id, num, @rank:=@rank+1 AS rank_no FROM ('.$GroupCountSql.') a, (SELECT @rank:=0) b '.$where.' '.$limit.') c_tmp ON basic.id=c_tmp.user_id';


    $res = db('user_study')->query($sql);

    if($res) {
        return $res;
    }

    return false;

}

function get_user_list ($organization_id) {
    // 设置请求的header参数
    $headers = ['Authorization:'.config('llapi.v4_api_Authorization')];

    // 设置请求URL
    $url = config('llapi.formal_url').'/api/v4/organizations/' . $organization_id . '/members';

    $result = curlRequest($url, '', $headers);

    $data = json_decode($result, true);

    // 判断请求是否成功
    if($result != false && $data != false && is_array($data) && !array_key_exists('error',$data)){

        return $data;
    }
    return false;
}

/**
 * 发送模板消息
 * @param $data
 * @param $template_id
 * @return object
 */
function weixin_tempalte ($data, $template_id) {

    $template = (object)[
        'template_id' => $template_id, // 模版id
        'url'      => $data['url'],
        'data' => [
            'first'    => ['value' => $data['first'], 'color' => "#000"],
            'keyword1' => ['value' => $data['keyword1'], 'color' => '#F70997'],
            'keyword2' => ['value' => $data['keyword2'], 'color' => '#248d24'],
            'remark'   => ['value' => $data['remark'], 'color' => '#1784e8']
        ]
    ];

    return $template;
}

function send_weixin_msg ($openid, $data, $template_id = 'XcVL1dSyOdOKfEQBxLN8Qkz5usZPYTUBIetBcrJG_oA'){

    $url = config('llapi.formal_url'). '/api/v4/pushes/wechat';

    // 设置请求的header参数
    $headers = ['Authorization:'.config('llapi.v4_api_Authorization')];

    $params['openids'] = $openid;
    $params['template_entity'] = weixin_tempalte($data, $template_id);

    curlRequest($url, 'POST', $headers, $params);
}

function get_wechat_token () {

    $file = getcwd().'/wechat_token.json';

    $wechat_token = file_exists($file) ? json_decode(file_get_contents($file), true) : false;

    if($wechat_token && time() < $wechat_token['expired_at']){

        return $wechat_token['access_token'];
    }

    // 设置请求的header参数
    $headers = ['Authorization:'.config('llapi.v4_api_Authorization')];

    // 设置请求URL
    $url = config('llapi.formal_url').'/api/v4/wechat_clients/access_token';

    $result = curlRequest($url, '', $headers);

    $data = json_decode($result, true);

    // 判断请求是否成功
    if($result != false && $data != false && is_array($data) && !array_key_exists('error',$data)){

        file_put_contents($file, $result);

        return $data['access_token'];
    }
    return false;
}


/**
 * 获取 jsapi_ticket
 * @return bool|mixed
 */
function get_ticket () {

    $file = getcwd().'/weikt_jssdk.json';

    $weikt_jssdk = file_exists($file) ? json_decode(file_get_contents($file), true) : false;

    if($weikt_jssdk && time() < $weikt_jssdk['expired_at']){

        return $weikt_jssdk['ticket'];
    }

    // 设置请求的header参数
    $headers = ['Authorization:'.config('llapi.v4_api_Authorization')];

    // 设置请求URL
    $url = config('llapi.formal_url').'/api/v4/wechat_clients/jsapi_ticket';

    $result = curlRequest($url, '', $headers);

    $data = json_decode($result,true);

    // 判断请求是否成功
    if($result != false && $data != false && is_array($data) && !array_key_exists('error',$data)){

        file_put_contents($file, $result);

        return $data['ticket'];
    }
    return false;
}

/**
 * 获取token
 * @param string $code 验证码
 * @param string $redirect_uri  回调地址
 * @return bool|mixed
 */
function get_user_token ($code = '', $redirect_uri = '') {

    $file = session('weikt_token');

    $token = ($file && !is_array($file)) ? json_decode($file, true) : null;

    if (!empty($token) && time() < ($token['created_at'] + $token['expires_in'])) {

        return $token['access_token'];

    }

    $url = config('llapi.formal_url').'/oauth/token';

    // 整理请求参数
    $param = [
        'client_id'     => config('llapi.client_id'),
        'client_secret' => config('llapi.client_secret'),
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => $redirect_uri
    ];

    $output = curlRequest($url, 'POST', [], $param);

    $data = json_decode($output,true);

    // 判断请求是否成功
    if (isset($data['access_token'])) {

        session('weikt_token', $output);

        // 获取成功，返回 token 字符串
        return $data['access_token'];
    }

    // 失败fasle
    return false;
}

/**
 * 获取用户信息
 * @param $token
 * @return bool|mixed
 */
function get_user_info ($token) {

    $url = config('llapi.formal_url').'/api/v1/user';
    // 拼接token
    $url .= '/?access_token=' . $token;

    $result = curlRequest($url, 'GET');

    $user_info = json_decode($result,true);

    // 判断 curl 请求是否成功
    if($result != false || $user_info != false || is_array($user_info) || !isset($user_info['error'])) {
        // 获取用户信息成功
        return $user_info;
    }
    // 获取用户信息失败，返回false
    return false;
}

/**
 * 提交数据
 * @param  string $url 请求Url
 * @param  string $method 请求方式
 * @param  array/string $headers Headers信息
 * @param  array/string $params 请求参数
 * @return 返回的
 */
function curlRequest ($url, $method, $headers = [], $params = []) {
    if (is_array($params)) {
        $requestString = http_build_query($params);
    } else {
        $requestString = $params ? : '';
    }

    // setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // setting the POST FIELD to curl
    switch ($method){
        case "GET" :
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            break;
        case "POST":
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
        case "PUT" :
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
        case "DELETE":
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
    }
    // getting response from server
    $response = curl_exec($ch);

    //close the connection
    curl_close($ch);

    //return the response
    return $response;
}
