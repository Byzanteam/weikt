<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/10
 * Time: 2:53
 */

namespace app\api\v1\model;


use think\Model;

class Curriculum extends Model
{
    /**
     * 首页列表查询
     * @param 用户ID
     * @param array $where
     * @param string $order
     * @param int $limit
     * @param string $fields
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($uid, $where = [], $order = 'c.chapter_num desc', $limit = 3, $fields = 'cl.id as classify_id,cl.name as classify_name,c.id,c.title,IF(SUM(study_num),SUM(study_num),0) AS study_num,IF(st.state,st.state,0) as is_study'){
        return $this->alias('c')
                    ->join('vcr_curriculum_classification cl', 'c.cl_id = cl.id','LEFT')
                    ->join('vcr_curriculum_chapter cc', 'c.id = cc.cp_id', 'LEFT')
                    ->join('vcr_user_study st', 'cc.id = st.chapter_id  AND (st.user_id = ' . $uid . ' OR st.user_id is null)', 'LEFT')
                    ->where($where)
                    ->field($fields)
                    ->group('c.id')
                    ->order($order)
                    ->limit($limit)
                    ->select();
    }


    public function getCourseList($where = [], $p = 1, $l = 10, $order = ['c.sort', 'id' => 'desc'], $fields = 'c.id, c.title, c.chapter_num, cc.name as classify_name') {
        return $this->alias('c')
                    ->join('vcr_curriculum_classification cc','c.cl_id = cc.id', 'LEFT')
                    ->field($fields)
                    ->where($where)
                    ->order($order)
                    ->page($p,$l)
                    ->select();

    }

    public function getDetail($uid, $where = [], $fields = 'c.id, c.title, c.desc, c.chapter_num, cc.name as classify_name,IF(uc.id, "1", 0) AS is_collection'){
        return $this->alias('c')
                    ->join('vcr_curriculum_classification cc','c.cl_id = cc.id', 'LEFT')
                    ->join('vcr_user_collection uc','uc.curriculum_id = c.id AND uc.user_id = ' . $uid . ' AND uc.status = 1', 'LEFT')
                    ->field($fields)
                    ->where($where)
                    ->find();
    }

}