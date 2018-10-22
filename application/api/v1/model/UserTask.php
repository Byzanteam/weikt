<?php
namespace app\api\v1\model;

use think\Model;

class UserTask extends Model {
    /**
     * 获取我的作业详情
     * @param array $where
     * @param string $fields
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetail ($where = [], $fields = '*') {
        return $this->alias('ut')
                    ->join('vcr_curriculum_chapter cc', 'cc.id = ut.chapter_id')
                    ->field($fields)
                    ->where($where)
                    ->find();
    }

    /**
     * 获取我的作业列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param string $fields
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList ($where = [], $page = 1, $limit = 10, $fields = '*') {
        return $this->alias('ut')
            ->join('vcr_curriculum_chapter cc', 'cc.id = ut.chapter_id')
            ->field($fields)
            ->where($where)
            ->page($page,$limit)
            ->select();
    }

}