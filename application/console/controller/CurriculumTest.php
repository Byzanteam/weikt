<?php
namespace app\console\controller;

use app\console\Base;
use app\console\model\CurriculumTest as Test_model;
use app\console\model\CurriculumClassification as Classify_model;
use app\console\model\CurriculumTestOption as testOption_model;

class CurriculumTest extends Base
{

    private $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new Test_model();

    }


    /**
     * 测验题数据表格也加载
     */
    public function index()
    {
        return $this->fetch('console/curriculum_test/index');
    }


    /**
     * 异步获取列表数据
     */
    public function getTestList()
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
                $where['topic'] = ['like','%'.$content.'%'];
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
     * 测验题添加页面加载
     */
    public function add()
    {
        // 获取分类
        $classify = new Classify_model();
        $classifyList = $classify->getClassifyAll();

        $this->assign('list',$classifyList);
        return $this->fetch('console/curriculum_test/add');
    }


    /**
     * 习题添加异步处理
     */
    public function addTest()
    {
        if(\think\Request::instance()->isPost()){

            $data['is_type'] = intval(input('is_type',0));
            $data['cl_id'] = intval(input('cl_id',0));
            $data['cp_id'] = intval(input('cp_id',0));
            $data['cc_id'] = intval(input('cc_id',0));
            $data['sort'] = intval(input('sort',0));
            $data['topic'] = input('topic','');

//            dump($data);exit;

            if(!empty($data['is_type']) && !empty($data['cl_id']) && !empty($data['cp_id']) && !empty($data['cc_id']) && !empty($data['topic'])){

                // 补全
                $data['is_time'] = time();

                // 添加到数据库
                $res = db('curriculum_test')->insert($data);
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
     * 编辑页面在家
     */
    public function edit()
    {
        if(\think\Request::instance()->isGet()){

            $id = intval(input('id'));

            if(!empty($id)){

                // 获习题记录
                $data = db('curriculum_test')->where(['id'=>$id])->find();

                if(!empty($data)){

                    // 获取所有分类
                    // 获取分类
                    $classify = new Classify_model();
                    $classifyList = $classify->getClassifyAll();

                    // 获取习题选定分类下课程
                    $courseList = db('curriculum')->where(['cl_id'=>$data['cl_id']])->select();

                    // 获取所选课程下的章节
                    $chapterList = db('curriculum_chapter')->where(['cp_id'=>$data['cp_id']])->select();

                    // 判断 如果是阅读题 对 题目进行 HTML反编译
                    if($data['is_type'] == 1){
                        // 文本内容反编译
                        $data['topic'] = htmlspecialchars_decode($data['topic']);
                    }else{
                        $data['topic'] = '<p>'.$data['topic'].'</p>';
                    }

                    $this->assign('classify_list',$classifyList);
                    $this->assign('course_list',$courseList);
                    $this->assign('chapter_list',$chapterList);
                    $this->assign('data',$data);
                    return $this->fetch('console/curriculum_test/edit');
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
    public function editTest()
    {
        if(\think\Request::instance()->isPost()){
            $id = intval(input('id'));
            $data['is_type'] = intval(input('is_type',0));
            $data['cl_id'] = intval(input('cl_id',0));
            $data['cp_id'] = intval(input('cp_id',0));
            $data['cc_id'] = intval(input('cc_id',0));
            $data['sort'] = intval(input('sort',0));
            $data['topic'] = input('topic','');


            if(!empty($data['is_type']) && !empty($data['cl_id']) && !empty($data['cp_id']) && !empty($data['cc_id']) && !empty($data['topic'])){

                // 检查记录是否存在
                $arr = db('curriculum_test')->where(['id'=>$id])->find();
                if(!empty($arr)){


                    $res = db('curriculum_test')->where(['id'=>$id])->update($data);
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
     * 选项添加页面加载
     */
    public function option()
    {
        if(\think\Request::instance()->isGet()){

            $id = intval(input('id'));

            if(!empty($id)){

                // 获习题记录
                $data = db('curriculum_test')->where(['id'=>$id])->find();

                if(!empty($data)){

                    // 判断测验题是否为选择题
                    if($data['is_type'] == 2){

                        $this->assign('id',$data['id']);
                        return $this->fetch('console/curriculum_test/option');
                    }
                    $this->assign('msg','该题型不需要设置选项');
                    return $this->fetch('console/public/open_error_msg');
                }
                $this->assign('msg','没有找到您要添加选项的记录的记录');
                return $this->fetch('console/public/open_error_msg');
            }
            $this->assign('msg','缺少必要参数');
            return $this->fetch('console/public/open_error_msg');
        }
        $this->assign('msg','错误的请求');
        return $this->fetch('console/public/open_error_msg');
    }

    /**
     * 选项添加异步处理
     */
    public function addOption()
    {
        if(\think\Request::instance()->isPost()) {

            $data['ct_id'] = intval(input('id'));
            $data['option_str'] = input('option_str','','strip_tags,trim');
            $data['state'] = intval(input('state',2));
            $data['sort'] = intval(input('sort',0));
            $data['analyze'] = input('analyze','','strip_tags,trim');

            if(!empty($data['ct_id']) && !empty($data['option_str']) && !empty($data['option_str'])){

                // 判断选项是否正确，正确判断解析是否为空
                if($data['state'] == 1){
                    if(empty($data['analyze'])){
                        return json(['code' => 0, 'msg' => '请填写正确答案的解析']);
                    }
                }

                // 补全
                $data['is_time'] = time();

                if (db('curriculum_test_option')->insert($data)){

                    return json(['code' => 200, 'msg' => '选项添加成功，可点击 [查看选项] 按钮查看']);

                }
                return json(['code' => 0, 'msg' => '选项添加失败，请重试']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，添加失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /**
     * 选项查看
     */
    public function viewOption()
    {
        if(\think\Request::instance()->isGet()){

            $id = intval(input('id'));

            if(!empty($id)){

                // 查看测验题是否存在
                $data = db('curriculum_test')->where(['id'=>$id])->find();

                if(!empty($data)){

                    // 判断测验题是否为选择题
                    if($data['is_type'] == 2){

                        $this->assign('id',$data['id']);
                        return $this->fetch('console/curriculum_test/view_option');
                    }
                    $this->assign('msg','该题型不需要设置选项');
                    return $this->fetch('console/public/open_error_msg');
                }
                $this->assign('msg','没有找到您要添加选项的记录的记录');
                return $this->fetch('console/public/open_error_msg');
            }
            $this->assign('msg','缺少必要参数');
            return $this->fetch('console/public/open_error_msg');
        }
        $this->assign('msg','错误的请求');
        return $this->fetch('console/public/open_error_msg');
    }

    /**
     * 选项查看表格异步
     */
    public function api_view_Option()
    {
        if(\think\Request::instance()->isGet()){
            $id = intval(input('id',0));
            $page = intval(input('page',1));
            $limit = intval(input('limit',10));

            if(!empty($id)){

                $option_model = new testOption_model();
                $data = $option_model->getTablePageList(['ct_id'=>$id],$page,$limit);

//                dump($data);exit;
                if(!empty($data['data'])){
                    return json(['code' => 200, 'msg' => '列表获取成功', 'count' => $data['total'], 'data' => $data['data']]);
                }
                return json(['code' => 0, 'msg' => '没有数据了', 'count' => 0, 'data' => []]);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，没有获取到数据', 'count' => 0, 'data' => []]);
        }
        return json(['code' => 0, 'msg' => '没有数据', 'count' => 0, 'data' => []]);
    }


    /**
     * 选项删除异步处理
     */
    public function delOption()
    {
        if(\think\Request::instance()->isPost()) {
            $id = intval(input('id'));
            if(!empty($id)){
                // 删除前 获取一下数据，检查数据是否存在
                $data = db('curriculum_test_option')->where(['id'=>$id])->find();
                if(!empty($data)){
                    $option_model = new testOption_model();
                    // 执行删除
                    $res = $option_model->del($id);
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


    /**
     * 删除记录
     * @param id 记录ID
     */
    public function delTest()
    {
        if(\think\Request::instance()->isPost()) {
            $id = intval(input('id'));
            if(!empty($id)){
                // 删除前 获取一下数据，检查数据是否存在
                $data = db('curriculum_test')->where(['id'=>$id])->find();
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