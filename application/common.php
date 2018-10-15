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
