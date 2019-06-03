<?php
namespace app\api\v1\model;

use think\Model;

class Curriculum extends Model {
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
    public function getList($uid, $where = [], $order = '', $limit = 0, $fields = '*'){
        $this->alias('c')
                    ->join('vcr_curriculum_classification cl', 'c.cl_id = cl.id','LEFT')
                    ->join('vcr_curriculum_chapter cc', 'c.id = cc.cp_id', 'LEFT')
                    ->join('vcr_user_study st', 'cc.id = st.chapter_id AND (st.user_id = ' . $uid . ' OR st.user_id is null)', 'LEFT')
                    ->join('vcr_user_study us', 'cc.id = us.chapter_id AND us.state = 2)', 'LEFT')
                    ->where($where)
                    ->field($fields)
                    ->group('c.id')
                    ->order($order);
        if ($limit > 0) {
            $this->limit($limit);
        }
        return $this->select();
    }

    /**
     * 课程分页列表
     * @param array $where
     * @param int $p
     * @param int $l
     * @param array $order
     * @param string $fields
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCourseList($where = [], $p = 1, $l = 10, $order = [], $fields = '*') {
        return $this->alias('c')
                    ->join('vcr_curriculum_classification cc','c.cl_id = cc.id', 'LEFT')
                    ->field($fields)
                    ->where($where)
                    ->order($order)
                    ->page($p,$l)
                    ->select();

    }

    /**
     * 课程详情
     * @param $uid
     * @param array $where
     * @param string $fields
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetail($uid, $where = [], $fields = '*'){
        return $this->alias('c')
                    ->join('vcr_curriculum_classification cc','c.cl_id = cc.id', 'LEFT')
                    ->join('vcr_user_collection uc','uc.curriculum_id = c.id AND uc.user_id = ' . $uid . ' AND uc.status = 1', 'LEFT')
                    ->field($fields)
                    ->where($where)
                    ->find();
    }

}