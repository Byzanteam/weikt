<?php
namespace app\api\v1\controller;

use app\api\Base;

class Classify extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取分类列表
     * @param p 页码 默认1
     * @param l 每页数量 默认10
     * @param pi 父级ID 0-获取所有父级 id 获取指定父级ID下的子级
     */
    public function get_classify_list()
    {
        if($this->request->isPost()) {
            // 参数整理
            $page = intval(input('p',1));
            $limit = intval(input('l',10));
            $parent_id = intval(input('pi',0));

            $data = [];
            if($parent_id == 0) {
                // 获取所有父级
                $data = db('curriculum_classification')
                    ->where(['parent_id'=>0,'level'=>0])
                    ->page($page,$limit)
                    ->order(['sort','id'=>'desc'])
                    ->select();
            }else{
                // 获取指定父级下的子级分类
                $data = db('curriculum_classification')
                    ->where(['parent_id'=>$parent_id,'level'=>1])
                    ->order(['sort','id'=>'desc'])
                    ->select();
            }

            if(!empty($data)) {
                return json(['code' => 200, 'msg' => '分类数据获取成功', 'data' => $data]);
            }
            return json(['code' => 404, 'msg' => '没有获取到分类数据', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }



}
