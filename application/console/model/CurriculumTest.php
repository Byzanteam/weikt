<?php
namespace app\console\model;

use think\Model;

class CurriculumTest extends Model
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

                // 获取所属章节名称
                $data = db('curriculum_chapter')->where(['id'=>$item->cc_id])->field('id,title')->find();
                $item->chapter_name = '[ '.$data['id'].' ] '.$data['title'];

                // 类型转换
                if($item->is_type == 1){
                    $item->type_str = '阅读题';
                }elseif ($item->is_type == 2){
                    $item->type_str = '选择题';
                }else{
                    $item->type_str = '未知';
                }


                $item->topic = strip_tags($str = htmlspecialchars_decode($item->topic));


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