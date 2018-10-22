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

    public function getList ($id, $page = 1, $limit = 10) {
        return $this->alias('uc')
                    ->join('vcr_curriculum c', 'uc.curriculum_id = c.id')
                    ->where([
                            'uc.user_id' => $id,
                            'uc.status'  => 1
                        ])
                    ->field('uc.id,uc.curriculum_id,c.title')
                    ->page($page,$limit)
                    ->order(['uc.id'=>'desc'])
                    ->select();
    }

}