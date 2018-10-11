<?php
/**
 * Created by PhpStorm.
 * User: xiaoy
 * Date: 2018-8-26
 * Time: 22:20
 */

namespace app\console\model;

use think\Model;

class Curriculum extends Model
{

    /**
     * 分页获取数据列表
     * @param where 查询条件
     * @param page  分页页码
     * @param limit 每页显示数量
     * @param order 排序方式
     */
    public function getTablePageList($where = [], $page = 1, $limit = 10, $order = 'id desc')
    {

        // tp5 分页调用方式
        $res = $this->where($where)
            ->order($order)
            ->paginate($limit,false,[
                'page' => $page,
            ])
            ->each(function($item,$key){

                // 获取所属分类的名称
                $typeName = db('curriculum_classification')->where(['id'=>$item->cl_id])->field('id,name')->find();
                $item->cl_name = '[ '.$typeName['id'].' ] - '.$typeName['name'];

                $item->is_time = date("Y-m-d H:i:s",$item->is_time);

            })
            ->toArray();

        return $res;
    }

    /**
     * 根据ID删除
     * @param $id
     * @return int
     */
    public function del($id)
    {
        return $this->where(['id'=>$id])->delete();
    }

}