<?php
namespace app\console\controller;

use app\console\Base;

class Common extends Base
{

    // 允许上传文件类型数组
    private $allow_type_arr = ['audio','video','image'];

    // 允许上传文件的大小 20M
    private $allow_size = 300 * 1024 * 1024;

    // 文件保存路径
    private $allow_file_path = 'static/update/';


    /**
     * 文件上传
     */
    public function file_update()
    {

        if(\think\Request::instance()->isPost()){
            $data = $_FILES['file'];
            $types = input('type','','strip_tags,trim');

            if(!empty($data) && $data['error'] == 0){
                // 获取上传文件的类型
                $arr = explode('/',$data['type']);
                $type = $arr[0];

                // 获取文件后缀
                $suffix = $arr[1];

                if(in_array($type,$this->allow_type_arr)){

                    // 确认文件类型正确后，判断文件的大小是否符合要求
                    if($data['size'] <= $this->allow_size){

                        // 根据文件类型 设置文件保存位置
                        $path = $this->allow_file_path;
                        if(!empty($types)){
                            $path .= $types.'/';
                        }
                        $path .= $type.'/';

                        // 判断文件夹是否存在，不存在则创建，创建失败的话返回错误
                        if(!is_dir($path)){
                            // 文件夹不存在，进行创建
                            if(!mkdir ($path,0777,true)){
                                return json(['code' => 0, 'msg' => '文件夹创建失败，请联系管理员']);
                            }
                        }


                        // 声明文件名称 命名格式 md5(time()).源文件后缀
                        $file_name = md5(time()).'.'.$suffix;

                        // 进行文件移动
                        if(move_uploaded_file($data['tmp_name'],$path.$file_name)){

                            // 上传完成，判断下文件是否存在
                            if(file_exists($path.$file_name)){

                                // 设置 文件访问路径
//                                $url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$path.$file_name;
                                $url = '/'.$path.$file_name;

                                return json(['code' => 200, 'msg' => '上传成功', 'data' => ['url' => $url]]);
                            }
                            return json(['code' => 0, 'msg' => '上传失败，文件在上传过程中丢失']);
                        }
                        return json(['code' => 0, 'msg' => '文件上传失败']);
                    }
                    return json(['code' => 0, 'msg' => '上传文件过大']);
                }
                return json(['code' => 0, 'msg' => '上传文件类型错误']);
            }
            return json(['code' => 0, 'msg' => '文件上传失败，错误代码：'.$data['error']]);
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