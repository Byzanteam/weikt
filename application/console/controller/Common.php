<?php
namespace app\console\controller;

use app\console\Base;

class Common extends Base {


    /**
     * 文件上传
     */
    public function file_update () {

        $data = $_FILES['file'];

        $result = upload_file($data['name'], $data['tmp_name']);

        if ($result['status'] > 0) {
            return json(['code' => 200, 'msg' => '上传成功', 'data' => ['url' => $result['data']]]);
        }

        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 三级联动，根据分类ID获取对应分类下的课程
     */
    public function getSearchCourse()
    {
        if(\think\Request::instance()->isPost()){

            $id = intval(input('id',0));
            if(!empty($id)){

                // 获取对应分类下课程列表
                $data = db('curriculum')->where(['cl_id'=>$id])->select();
                if($data){
                    return json(['code' => 200, 'msg' => '课程获取成功', 'data' => $data]);
                }
                return json(['code' => 0, 'msg' => '该分类下没有找到课程，请重新选择']);
            }
            return json(['code' => 0, 'msg' => '获取课程失败，缺少必要参数']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    /**
     * 三级联动，根据课程ID获取对应课程下的章节
     */
    public function getSearchChapter()
    {
        if(\think\Request::instance()->isPost()){

            $id = intval(input('id',0));
            if(!empty($id)){

                // 获取对应分类下课程列表
                $data = db('curriculum_chapter')->where(['cp_id'=>$id])->select();
                if($data){
                    return json(['code' => 200, 'msg' => '章节获取成功', 'data' => $data]);
                }
                return json(['code' => 0, 'msg' => '该课程下没有找到章节，请重新选择']);
            }
            return json(['code' => 0, 'msg' => '获取章节失败，缺少必要参数']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    /**
     * 三级联动，根据章节ID获取章节信息
     */
    public function getChapterInfo(){
        if(\think\Request::instance()->isPost()){

            $id = intval(input('id',0));
            if(!empty($id)){

                // 获取对应分类下课程列表
                $data = db('curriculum_chapter')->where(['id'=>$id])->find();
                if($data){
                    return json(['code' => 200, 'msg' => '章节获取成功', 'data' => $data]);
                }
                return json(['code' => 0, 'msg' => '没有找到该章节的记录']);
            }
            return json(['code' => 0, 'msg' => '获取章节失败，缺少必要参数']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

}