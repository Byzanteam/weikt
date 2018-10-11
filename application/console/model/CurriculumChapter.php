<?php
/**
 * Created by PhpStorm.
 * User: xiaoy
 * Date: 2018-8-27
 * Time: 0:20
 */

namespace app\console\model;

use think\Model;

class CurriculumChapter extends Model
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

                // 获取所属课程的名称
                $typeName = db('curriculum')->where(['id'=>$item->cp_id])->field('id,title')->find();
                $item->cl_name = '[ '.$typeName['id'].' ] - '.$typeName['title'];

                if($item->media_type == 'audio'){
                    $item->media_type_name = '音频';
                }elseif ($item->media_type == 'video'){
                    $item->media_type_name = '视频';
                }else{
                    $item->media_type_name = '文本';
                }

                if($item->test_type == 1){
                    $item->test_type_str = '阅读题';
                }elseif ($item->test_type == 2){
                    $item->test_type_str = '选择题';
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