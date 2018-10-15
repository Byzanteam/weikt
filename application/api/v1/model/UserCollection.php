<?php
namespace app\api\v1\model;

use think\Model;

class UserCollection extends Model {

    /**
     * 获取收藏详情
     * @param array $where
     * @param string $fields
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetail ($where = [],$fields = 'id,status') {
        return $this->field($fields)
                    ->where($where)
                    ->find();
    }

}