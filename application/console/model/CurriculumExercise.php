<?php
namespace app\console\model;

use think\Model;

class CurriculumExercise extends Model {

    /**
     * 分页获取数据列表
     * @param array where 查询条件
     * @param int page  分页页码
     * @param int limit 每页显示数量
     * @param string order 排序方式
     * @return array
     * @throws \think\exception\DbException
     */
    public function getTablePageList ($where = [], $page = 1, $limit = 10, $order = 'id desc') {

        // tp5 分页调用方式
        $res = $this->where($where)
            ->order($order)
            ->paginate($limit,false,[
                'page' => $page,
            ])
            ->each(function($item,$key){

                $item->title = htmlspecialchars($item->title);

                // 获取所属章节名称
                $data = db('curriculum_chapter')->where(['id'=>$item->cc_id])->field('id,title')->find();
                $item->chapter_name = '[ '.$data['id'].' ] '.htmlspecialchars($data['title']);


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
     * 获取章节对应练习题
     * @param array $where
     * @param string $fields
     * @param array $order
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList ($where = [], $fields = '*', $order = ['sort', 'id' => 'desc']) {
        return db('curriculum_exercise')->field($fields)
                ->where($where)
                ->order($order)
                ->select();
    }

    /**
     * 获取练习题详情
     * @param array $where
     * @param string $fields
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOne ($where = [], $fields = '*') {
        return db('curriculum_exercise')->field($fields)
            ->where($where)
            ->find();
    }
}
