<?php
/**
 * Created by PhpStorm.
 * User: xiaoy
 * Date: 2018-8-26
 * Time: 22:18
 */

namespace app\console\controller;


use app\console\Base;
use app\console\model\Curriculum as Curr_model;
use app\console\model\CurriculumClassification as classify_model;


class Curriculum extends Base
{

    private $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new Curr_model();

    }


    /**
     * 课程管理页面加载
     */
    public function index()
    {

        return $this->fetch('console/curriculum/index');

    }


    /**
     * 异步获取列表数据
     */
    public function getCurriculumList()
    {

        if(\think\Request::instance()->isGet()){

            $page = intval(input('page',1));
            $limit = intval(input('limit',10));
            // 筛选参数接受
            $cl_id = intval(input('cl_id',0));
            $title = input('title','','strip_tags,trim');

            $where = [];

            if($cl_id > 0){
                $where['cl_id'] = ['eq',$cl_id];
            }

            if(!empty($title)){
                $where['title'] = ['like','%'.$title.'%'];
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
     * 课程添加页面
     */
    public function add()
    {

        // 分层级获取所有分类
        $classify_model = new classify_model();
        $list = $classify_model->getClassifyAll();

        $this->assign('list',$list);
        return $this->fetch('console/curriculum/add');
    }


    /**
     * 课程异步添加
     */
    public function addCurriculum()
    {
        if(\think\Request::instance()->isPost()){

            $data['title'] = input('title','','strip_tags,trim');
            $data['cl_id'] = intval(input('cl_id',0));
            $data['desc'] = input('desc','','strip_tags,trim');
            $data['sort'] = intval(input('sort',0));
            $data['back_img'] = input('media_path','');
            $data['index_img'] = input('media_path1','');

            if(!empty($data['title']) && !empty($data['cl_id']) && !empty($data['desc'])){

                if(db('curriculum')->where(['cl_id'=>$data['cl_id'], 'title'=>$data['title']])->find()){
                    return json(['code' => 0, 'msg' => '课程名称重复，课程添加失败']);
                }

                // 添加到数据库
                $res = db('curriculum')->insert($data);
                if($res){
                    return json(['code' => 200, 'msg' => '课程添加成功']);
                }
                return json(['code' => 0, 'msg' => '课程添加失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，添加失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 记录编辑页面
     */
    public function edit () {

        if(\think\Request::instance()->isGet()){

            $id = intval(input('id'));

            if(!empty($id)){

                // 获取课程记录
                $data = db('curriculum')->where(['id'=>$id])->find();

                if(!empty($data)){

                    // 分层级获取所有分类
                    $classify_model = new classify_model();
                    $list = $classify_model->getClassifyAll();

                    $this->assign('data',$data);
                    $this->assign('list',$list);
                    return $this->fetch('console/curriculum/edit');
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
    public function editCurriculum () {
        if(\think\Request::instance()->isPost()){
            $id = intval(input('id'));
            $data['title'] = input('title','','strip_tags,trim');
            $data['cl_id'] = intval(input('cl_id',0));
            $data['desc'] = input('desc','','strip_tags,trim');
            $data['sort'] = intval(input('sort',0));
            $data['back_img'] = input('media_path','');
            $data['index_img'] = input('media_path1','');

            if(!empty($id) && !empty($data['title']) && !empty($data['cl_id']) && !empty($data['desc'])){

                if(db('curriculum')->where(['id'=>['neq',$id],'cl_id'=>$data['cl_id'], 'title'=>$data['title']])->find()){
                    return json(['code' => 0, 'msg' => '课程名称重复，课程添加失败']);
                }

                $res = db('curriculum')->where(['id'=>$id])->update($data);
                if($res){
                    return json(['code' => 200, 'msg' => '分类编辑成功']);
                }
                return json(['code' => 0, 'msg' => '分类编辑失败，请重新操作']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，修改失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /** 删除记录 */
    public function delCurriculum () {
        if (\think\Request::instance()->isPost()) {

            $id = intval(input('id')); // 课程ID

            if (!empty($id)) {

                // 判断课程下是否拥有章节
                $is_chapter = db('curriculum_chapter')->where(['cp_id' => $id])->select();

                if (empty($is_chapter)) {
                    // 执行删除
                    $res = $this->model->del($id);
                    if($res){
                        return json(['code' => 200, 'msg' => '数据删除成功']);
                    }
                    return json(['code' => 0, 'msg' => '数据删除失败']);
                }
                return json(['code' => 0, 'msg' => '删除失败！该课程下拥有章节记录，请先删除或转移下属章节']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，删除失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /** 置顶状态修改 */
    public function editState () {

        if (\think\Request::instance()->isPost()) {

            $id = intval(input('id')); // 记录ID
            $is_state = intval(input('is_state')); // 状态码

            if ($is_state != 0 && $is_state != 1) {
                return json(['code' => 0, 'msg' => '参数异常，修改失败']);
            }

            if (!empty($id)) {
                // 修改数据记录
                $res = db('curriculum')->where(['id'=>$id])->update(['state'=>$is_state]);
                if($res){
                    return json(['code' => 200, 'msg' => '状态修改成功']);
                }
                return json(['code' => 0, 'msg' => '状态修改失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，修改失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


}