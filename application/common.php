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

/**
 * 获取 jsapi_ticket
 * @return bool|mixed
 */
function get_ticket () {

    // 当前时间-60秒
    $new_time = time() - 60;

    if(session('weikt_jssdk')){
        // 如果session 存在，则判断是否过去，如果没有过过期
        if($new_time < session('weikt_jssdk.expired_at')){
            return session('weikt_jssdk.ticket');
        }
    }

    // 设置请求的header参数
    $headers = ['Authorization:'.config('llapi.v4_api_Authorization')];

    // 设置请求URL
    $url = config('llapi.formal_url').'/api/v4/wechat_clients/jsapi_ticket';

    $result = curlRequest($url, '', $headers);

    $data = json_decode($result,true);

    // 判断请求是否成功
    if($result != false && $data != false && is_array($data) && !array_key_exists('error',$data)){

        // 请求成功，将结存储到session中
        session('weikt_jssdk',$data);

        return $data['ticket'];
    }
    return false;
}

/**
 * 获取token
 * @param $code 验证码
 * @param $redirect_uri  回调地址
 * @return bool|mixed
 */
function get_user_token ($code, $redirect_uri) {

    $token = session('weikt_token');
    if(!empty($token) && time() < ($token['created_at'] + $token['expires_in'])) {

        return $token['access_token'];
    } else {

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

        session('weikt_token', $data);

        // 判断请求是否成功
        if($output != false && $data != false && is_array($data) && !isset($user_info['error']) && isset($user_info['access_token'])) {
            // 获取成功，返回 token 字符串
            return $data['access_token'];
        }
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
    return $user_info;
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
    if (empty($headers)) {
//        $headers = ['Content-type: text/json'];
    } elseif (!is_array($headers)) {
        parse_str($headers,$headers);
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
