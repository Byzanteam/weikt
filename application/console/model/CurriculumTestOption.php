<?php
namespace app\console\model;

use think\Model;

class CurriculumTestOption extends Model
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

                // 类型转换
                if($item->state == 1){
                    $item->state_str = '<span style="color: green">正确</span>';
                }elseif ($item->state == 2){
                    $item->state_str = '<span style="color: red">错误</span>';
                }else{
                    $item->state_str = '<span>未知</span>';
                }


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