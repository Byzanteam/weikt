<?php
namespace app\console\controller;

use app\console\Base;
use app\console\model\CurriculumClassification as classify_model;

class CourseClassify extends Base
{

    private $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new classify_model();

    }

    /**
     * 加载分类管理页面
     */
    public function index()
    {
        return $this->fetch('/console/course_classify/index');
    }


    /**
     * 获取分类表格数据
     */
    public function getClassifyList()
    {
        if(\think\Request::instance()->isGet()){

            $page = intval(input('page',1));
            $limit = intval(input('limit',10));

            // 筛选参数接受
            $id = intval(input('id',0));
            $title = input('title','','strip_tags,trim');

            $where = [];

            if($id > 0){
                $where['parent_id'] = ['eq',$id];
            }

            if(!empty($title)){
                $where['name'] = ['like','%'.$title.'%'];
            }

            $data = $this->model->getTablePageList($where,$page,$limit);
            if(!empty($data['data'])){
                return json(['code' => 200, 'msg' => '列表获取成功', 'count' => $data['total'], 'data' => $data['data']]);
            }
            return json(['code' => 0, 'msg' => '没有数据了', 'count' => 0, 'data' => []]);
        }
        return json(['code' => 0, 'msg' => '没有数据', 'count' => 0, 'data' => []]);
    }


    /**
     * 添加分类
     */
    public function add()
    {

        // 读取顶级分类列表
        $list = db('curriculum_classification')->where(['level'=>0,'parent_id'=>0])->select();

        $this->assign('list',$list);
        return $this->fetch('/console/course_classify/add');
    }

    /**
     * 分类异步添加
     */
    public function addClassify()
    {
        if(\think\Request::instance()->isPost()){

            $data['name'] = input('name','','strip_tags,trim');
            $data['parent_id'] = intval(input('parent_id'));
            $data['sort']  = intval(input('sort',0));
            $data['state'] = intval(input('state',0));
            $data['back_img'] = input('media_path','');

            if(!empty($data['name'])){

                $data['level'] = empty($data['parent_id']) ? 0 : 1;
                $data['is_time'] = time();

                // 判断名称是否重复
                if(db('curriculum_classification')->where(['name'=>$data['name'], 'level'=>$data['level'], 'parent_id'=>$data['parent_id']])->find()){
                    return json(['code' => 0, 'msg' => '分类名称重复！添加失败！']);
                }

                // 添加到数据库
                $res = db('curriculum_classification')->insert($data);
                if($res){
                    return json(['code' => 200, 'msg' => '分类添加成功']);
                }
                return json(['code' => 0, 'msg' => '分类添加失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，添加失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 置顶状态修改
     * @param id 记录ID
     * @param is_state 状态码
     */
    public function editState()
    {
        if(\think\Request::instance()->isPost()){
            $id = intval(input('id'));
            $is_state = intval(input('is_state'));

            if($is_state != 0 && $is_state != 1){
                return json(['code' => 0, 'msg' => '参数异常，修改失败']);
            }

            if(!empty($id)){
                // 修改数据记录
                $res = db('curriculum_classification')->where(['id'=>$id])->update(['state'=>$is_state]);
                if($res){
                    return json(['code' => 200, 'msg' => '状态修改成功']);
                }
                return json(['code' => 0, 'msg' => '状态修改失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，修改失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 删除记录
     * @param id 记录ID
     */
    public function delClassify()
    {
        if(\think\Request::instance()->isPost()) {
            $id = intval(input('id'));
            if(!empty($id)){

                // 分类删除
                // 顶级分类检测是否拥有子分类，是否拥有课程
                // 子分类，检测是否拥有课程

                // 检测是否拥有子分类
                $is_child = db('curriculum_classification')->where(['parent_id'=>$id])->select();
                if(empty($is_child)){

                    // 判断分类下是否拥有课程
                    $is_course = db('curriculum')->where(['cl_id'=>$id])->select();
                    if(empty($is_course)){
                        // 执行删除
                        $res = $this->model->del($id);
                        if($res){
                            return json(['code' => 200, 'msg' => '数据删除成功']);
                        }
                        return json(['code' => 0, 'msg' => '数据删除失败']);
                    }
                    return json(['code' => 0, 'msg' => '删除失败！该分类下拥有课程，请先删除或转移课程']);
                }
                return json(['code' => 0, 'msg' => '删除失败！该分类拥有子分类，请先删除或转移子分类']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，删除失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 记录编辑页面
     */
    public function edit()
    {

       if(\think\Request::instance()->isGet()){

           $id = intval(input('id'));

           if(!empty($id)){

               // 获取分类记录
               $data = db('curriculum_classification')->where(['id'=>$id])->find();

               if(!empty($data)){

                   // 读取顶级分类列表
                   $list = db('curriculum_classification')->where(['level'=>0,'parent_id'=>0])->select();

                   $this->assign('data',$data);
                   $this->assign('list',$list);
                   return $this->fetch('/console/course_classify/edit');
               }
               $this->assign('msg','没有找到您要编辑的记录');
               return $this->fetch('/console/public/open_error_msg');
           }
           $this->assign('msg','缺少必要参数');
           return $this->fetch('/console/public/open_error_msg');
       }
       $this->assign('msg','错误的请求');
       return $this->fetch('/console/public/open_error_msg');
    }

    /**
     * 记录异步编辑
     */
    public function editClassify()
    {
        if(\think\Request::instance()->isPost()){
            $id = intval(input('id'));
            $data['name'] = input('name','','strip_tags,trim');
            $data['parent_id'] = intval(input('parent_id'));
            $data['sort'] = intval(input('sort',0));
            $data['state'] = intval(input('state',0));
            $data['back_img'] = input('media_path','');
            if(!empty($id) && !empty($data['name'])){

                $data['level'] = empty($data['parent_id']) ? 0 : 1;

                // 判断名称是否重复
                if(db('curriculum_classification')->where(['id'=>['neq',$id],'name'=>$data['name'], 'level'=>$data['level'], 'parent_id'=>$data['parent_id']])->find()){
                    return json(['code' => 0, 'msg' => '分类名称重复！添加失败！']);
                }

                $res = db('curriculum_classification')->where(['id'=>$id])->update($data);
                if($res){
                    return json(['code' => 200, 'msg' => '分类编辑成功']);
                }
                return json(['code' => 0, 'msg' => '分类编辑失败，请重新操作']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，修改失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

}