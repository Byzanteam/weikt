<?php
namespace app\console\model;

use think\Model;

class UserTask extends Model
{
    /**
     * 分页获取数据列表
     * @param where 查询条件
     * @param page  分页页码
     * @param limit 每页显示数量
     * @param order 排序方式
     */
    public function getTablePageList ($where = [], $page = 1, $limit = 10, $order = 'ut.id desc') {

        // tp5 分页调用方式
        $res = $this->alias('ut')
            ->join('curriculum_chapter cc', 'cc.id = ut.chapter_id')
            ->join('user_basic u', 'u.id = ut.user_id')
            ->join('user_basic u1', 'u1.ll_id = ut.ll_id', 'left')
            ->field('ut.id,ut.chapter_id,cc.title,ut.user_id,u.name as user_name,ut.sub_time,ut.state,u1.name')
            ->where($where)
            ->order($order)
            ->paginate($limit,false,[
                'page' => $page,
            ])
            ->each(function($item,$key){

                $item->chapter_str = '['.$item->chapter_id.'] '.$item->title;

                $item->user_name = '['.$item->user_id.'] '.$item->user_name;

                $item->sub_time = date("Y-m-d H:i:s",$item->sub_time);

                $item->right = '<a class="layui-btn layui-btn-xs" lay-event="view">查看</a>';

                if ($item->state == 1) {
                    $item->state_str = '<span style="color:green;">已点评</span>';
                } elseif ($item->state == 2){
                    $item->state_str = "<span style='color:red;'>待点评</span>";
                    $item->right .= '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="review">点评</a>';
                }

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
     * 获取提交作业的人和作业信息
     * @param array $where 条件
     * @param string $fields 要获取的字段
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserAndTask ($where = [], $fields = '*') {
        return $this->alias('ut')
            ->field($fields)
            ->join('user_basic u', 'u.id = ut.user_id')
            ->join('curriculum_chapter cc', 'cc.id = ut.chapter_id')
            ->join('curriculum c', 'c.id = cc.cp_id')
            ->where($where)
            ->find();
    }
}