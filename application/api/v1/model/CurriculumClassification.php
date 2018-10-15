<?php
namespace app\api\v1\model;

use think\Model;

class CurriculumClassification extends Model {

    public function getList ($where = [], $page = 1, $limit = 10, $fields = '*') {
        return $this->field($fields)
                    ->where($where)
                    ->page($page,$limit)
                    ->order('sort')
                    ->select();
    }
}