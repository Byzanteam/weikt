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
    public function getTablePageList($where = [], $page = 1, $limit = 10, $order = 'id desc')
    {

        // tp5 分页调用方式
        $res = $this->where($where)
            ->order($order)
            ->paginate($limit,false,[
                'page' => $page,
            ])
            ->each(function($item,$key){

                // 获取章节名称
                $chapter = db('curriculum_chapter')->where(['id'=>$item->chapter_id])->field('title')->find();
                $item->chapter_str = '['.$item->chapter_id.'] '.$chapter['title'];

                // 获取用户名称
                $userinfo = db('user_basic')->where(['id'=>$item->user_id])->field('name')->find();
                $item->user_name = '['.$item->user_id.'] '.$userinfo['name'];

                $item->sub_time = date("Y-m-d H:i:s",$item->sub_time);
                if($item->test_type == 1){
                    $item->test_type = "阅读题";
                }
                if($item->test_type == 2){
                    $item->test_type = "选择题";
                }

                if($item->state == 1){
                    $item->state_str = "<span style='color:green;'>已点评</span>";
                }
                if($item->state == 2){
                    $item->state_str = "<span style='color:red;'>待点评</span>";
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
    public function del($id)
    {
        return $this->where(['id'=>$id])->delete();
    }
}