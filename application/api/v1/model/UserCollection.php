<?php
namespace app\api\v1\model;

use think\Model;

class UserCollection extends Model
{
    public function detail ($where = [],$fields = 'id,state') {
        return $this->field($fields)
            ->where($where)
            ->find();
    }

}