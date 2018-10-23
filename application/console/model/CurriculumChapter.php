<?php
namespace app\console\model;

use think\Model;

class CurriculumChapter extends Model {

    /**
     * 分页获取数据列表
     * @param array $where 查询条件
     * @param int $page 分页页码
     * @param int $limit 每页显示数量
     * @param string $order 排序方式
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
    public function del ($id) {
        return $this->where(['id'=>$id])->delete();
    }

    /**
     * 获取章节列表 并获取用户的学习情况
     * @param $uid
     * @param $where
     * @param string $fields
     * @param array $order
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChapterList ($uid, $where = [], $fields = '*', $order = ['sort', 'id'=>'desc']) {
        return $this->alias('ch')
            ->join('vcr_user_study st', 'ch.id = st.chapter_id AND user_id = ' . $uid, 'LEFT')
            ->where($where)
            ->field($fields)
            ->order($order)
            ->select();
    }

    /**
     * 获取章节信息
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
}