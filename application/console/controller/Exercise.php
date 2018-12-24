<?php
namespace app\console\controller;

use app\console\Base;
use app\console\model\CurriculumExercise as Exercise_model;
use app\console\model\CurriculumClassification as Classify_model;
use app\console\model\CurriculumChapter;
use app\console\model\Curriculum;

class Exercise extends Base
{

    private $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new Exercise_model();

    }

    /**
     * 练习题库，数据表格加载
     */
    public function index()
    {
        return $this->fetch('console/exercise/index');
    }


    /**
     * 异步获取列表数据
     */
    public function getExerciseList()
    {
        if(\think\Request::instance()->isGet()){

            $page = intval(input('page',1));
            $limit = intval(input('limit',10));

            // 筛选参数接受
            $id = intval(input('id',0));
            $content = input('content','','strip_tags,trim');

            $where = [];

            if($id > 0){
                $where['cc_id'] = ['eq',$id];
            }

            if(!empty($content)){
                $where['content'] = ['like','%'.$content.'%'];
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
     * 章节添加页面加载
     * @return mixed
     */
    public function add()
    {
        // 获取分类
        $classify = new Classify_model();
        $classifyList = $classify->getClassifyAll();

        $this->assign('list',$classifyList);
        return $this->fetch('console/exercise/add');
    }

    /**
     * 习题添加异步处理
     */
    public function addExercise()
    {
        if(\think\Request::instance()->isPost()){

            $data['title'] = strip_tags(input('title','','trim'), '<br>');
            $data['cl_id'] = intval(input('cl_id',0));
            $data['cp_id'] = intval(input('cp_id',0));
            $data['cc_id'] = intval(input('cc_id',0));
            $data['sort'] = intval(input('sort',0));
            $data['file_name'] = input('file_name','','strip_tags,trim');
            $data['media_path'] = input('media_path','','trim');
            $data['content'] = input('content','','strip_tags,trim');

            if(!empty($data['title']) && !empty($data['cl_id']) && !empty($data['cp_id']) && !empty($data['cc_id']) && !empty($data['media_path'])){

                // 添加到数据库
                $res = db('curriculum_exercise')->insert($data);
                if($res){

                    return json(['code' => 200, 'msg' => '习题添加成功']);
                }
                return json(['code' => 0, 'msg' => '习题添加失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，添加失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 习题编辑
     */
    public function edit () {
        if (\think\Request::instance()->isGet()) {

            $id = intval(input('id'));

            if (!empty($id)) {

                // 获习题记录
                $data =$this->model->getOne(['id' => $id]);

                if (!empty($data)) {

                    // 获取所有分类
                    // 获取分类
                    $classify = new Classify_model();
                    $classifyList = $classify->getClassifyAll();

                    // 获取习题选定分类下课程
                    $curModel = new Curriculum();
                    $courseList =$curModel->getList(['cl_id' => $data['cl_id']]);

                    // 获取所选课程下的章节
                    $chaModel = new CurriculumChapter();
                    $chapterList = $chaModel->getList(['cp_id' => $data['cp_id']]);

                    $this->assign('classify_list', $classifyList);
                    $this->assign('course_list', $courseList);
                    $this->assign('chapter_list', $chapterList);
                    $this->assign('data', $data);
                    return $this->fetch('console/exercise/edit');
                }
                $this->assign('msg','没有找到您要编辑的记录');
                return $this->fetch('console/public/open_error_msg');
            }
            $this->assign('msg','缺少必要参数');
            return $this->fetch('console/public/open_error_msg');
        }
        $this->assign('msg','错误的请求');
        return $this->fetch('console/public/open_error_msg');
    }

    /**
     * 记录异步编辑
     */
    public function editExercise () {
        if (\think\Request::instance()->isPost()) {
            $id = intval(input('id'));
            $data['title'] = strip_tags(input('title','','trim'), '<br>');
            $data['cl_id'] = intval(input('cl_id',0));
            $data['cp_id'] = intval(input('cp_id',0));
            $data['cc_id'] = intval(input('cc_id',0));
            $data['sort'] = intval(input('sort',0));
            $data['file_name'] = input('file_name','','strip_tags,trim');
            $data['media_path'] = input('media_path','','trim');
            $data['content'] = input('content','','strip_tags,trim');


            if(!empty($data['title']) && !empty($data['cl_id']) && !empty($data['cp_id']) && !empty($data['cc_id']) && !empty($data['media_path'])){

                // 检查记录是否存在
                $arr = db('curriculum_exercise')->where(['id'=>$id])->find();
                if(!empty($arr)){

                    // 判断 file_name 为空则 unset
                    if(empty($data['file_name'])){
                        unset($data['file_name']);
                    }

                    $res = db('curriculum_exercise')->where(['id'=>$id])->update($data);
                    if($res){

                        return json(['code' => 200, 'msg' => '题目编辑成功']);
                    }
                    return json(['code' => 0, 'msg' => '题目编辑失败，请重新操作']);
                }
                return json(['code' => 0, 'msg' => '没有找到您要编辑的记录，修改失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，修改失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 删除记录
     */
    public function delExercise () {
        if (\think\Request::instance()->isPost()) {
            $id = intval(input('id'));
            if(!empty($id)){
                // 删除前 获取一下数据，检查数据是否存在
                $data = db('curriculum_exercise')->where(['id'=>$id])->find();
                if(!empty($data)){
                    // 执行删除
                    $res = $this->model->del($id);
                    if($res){
                        return json(['code' => 200, 'msg' => '数据删除成功']);
                    }
                    return json(['code' => 0, 'msg' => '数据删除失败']);
                }
                return json(['code' => 0, 'msg' => '删除失败！没有找到您要删除的数据']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，删除失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


}