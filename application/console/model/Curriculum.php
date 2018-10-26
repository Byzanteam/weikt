<?php
namespace app\console\model;

use think\Model;

class Curriculum extends Model {

    /**
     * 分页获取数据列表
     * @param array where 查询条件
     * @param int page  分页页码
     * @param int limit 每页显示数量
     * @param string order 排序方式
     * @return array
     * @throws \think\exception\DbException
     */
    public function getTablePageList($where = [], $page = 1, $limit = 10, $order = 'id desc') {

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

            })
            ->toArray();

        return $res;
    }

    /**
     * 根据ID删除
     * @param $id
     * @return int
     */
    public function del ($id) {
        return $this->where(['id'=>$id])->delete();
    }

    /**
     * 获取课程详情
     * @param array $where
     * @param string $fields
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOne ($where = [], $fields = '*') {
        return $this->where($where)
            ->field($fields)
            ->find();
    }

    /**
     * 获取课程列表
     * @param array $where
     * @param string $fields
     * @param array $order
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList ($where = [], $fields = '*', $order = ['sort', 'id' => 'desc']) {
        return $this->field($fields)
            ->where($where)
            ->order($order)
            ->select();
    }

}