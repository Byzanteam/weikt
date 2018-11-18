<?php
namespace app\console\model;

use think\Model;

class CurriculumClassification extends Model
{

    /**
     * 分页获取数据列表
     * @param array where 查询条件
     * @param int page  分页页码
     * @param int limit 每页显示数量
     * @param string order 排序方式
     * @return array
     * @throws \think\exception\DbException
     */
    public function getTablePageList($where = [], $page = 1, $limit = 10, $order = 'id desc')
    {

        // tp5 分页调用方式
        $res = $this->field('*,name as c_name')
            ->where($where)
            ->order($order)
            ->paginate($limit,false,[
                'page' => $page,
            ])
            ->each(function ($item) {

                $item->c_name = htmlspecialchars($item->c_name);

                if ($item->parent_id == 0) {
                    $item->parent_name = '';
                } else {
                    $parentData = $this->where(['id'=>$item->parent_id])->field('id,name')->find();
                    $item->parent_name = '[ '.$parentData['id'].' ] - '.htmlspecialchars($parentData['name']);
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


    /**
     * 获取全部分类
     */
    public function getClassifyAll()
    {
        // 获取所有顶级分类
        $topClassify = $this->where(['parent_id'=>0,'level'=>0])->select();

        $res = [];

        // 获取每个顶级分类下的子分类
        foreach ($topClassify as $key => $val){
            // 将模型对象转换为数组
            $res[$key] = $val->toArray();
            // 获取下级分类
            $two = $this->where(['parent_id'=>$val['id'],'level'=>1])->select();

            $arr = [];
            foreach ($two as $k => $v){
                $arr[$k] = $v->toArray();
            }

            $res[$key]['child'] = $arr;
        }


        return $res;

    }

}