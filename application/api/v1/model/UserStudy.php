<?php
namespace app\api\v1\model;

use think\Model;

class UserStudy extends Model {


    /**
     * 获取我的学习详情
     * @param array $where
     * @param string $fields
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetail ($where = [], $fields = '*') {
        return db('user_study')->field($fields)
                    ->where($where)
                    ->find();
    }

    public function getList ($where = [], $fields = '*') {
        return db('user_study')->field($fields)
                ->where($where)
                ->select();
    }
}