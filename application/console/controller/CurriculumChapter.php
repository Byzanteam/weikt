<?php
namespace app\console\controller;

use app\console\Base;
use app\console\model\ChapterContent;
use app\console\model\CurriculumChapter as Chapter_model;

class CurriculumChapter extends Base {

    private $model;

    public function __construct () {
        parent::__construct();

        $this->model = new Chapter_model();

    }


    /**
     * 章节管理表单页载入
     */
    public function index () {
        return $this->fetch('console/curriculum_chapter/index');
    }


    /**
     * 异步获取列表数据
     */
    public function getChapterList () {
        if (\think\Request::instance()->isGet()) {

            $page = intval(input('page',1));
            $limit = intval(input('limit',10));

            // 筛选参数接受
            $id = intval(input('id',0));
            $title = input('title','','strip_tags,trim');

            $where = [];

            if ($id > 0) {
                $where['cp_id'] = ['eq', $id];
            }

            if (!empty($title)) {
                $where['title'] = ['like', '%'.$title.'%'];
            }

            $data = $this->model->getTablePageList($where,$page,$limit);
            if (!empty($data['data'])) {
                return json([
                    'code' => 200,
                    'msg' => '列表获取成功',
                    'count' => $data['total'],
                    'data'  => $data['data']
                ]);
            }
            return json(['code' => 0, 'msg' => '没有数据了', 'count' => 0, 'data' => []]);
        }
        return json(['code' => 0, 'msg' => '没有数据', 'count' => 0, 'data' => []]);
    }

    /**
     * 章节添加页面加载
     */
    public function add () {
        // 获取课程列表
        $courseList = db('curriculum')->where([])->field('id,title')->select();

        $teacher_list = get_user_list(25751);

        $this->assign('list', $courseList);
        $this->assign('teacher_list', $teacher_list);
        return $this->fetch('console/curriculum_chapter/add');
    }

    /**
     * 章节添加异步处理
     */
    public function addChapter () {
        if (\think\Request::instance()->isPost()) {

            $data['title'] = input('title','','strip_tags,trim');
            $data['cp_id'] = intval(input('cp_id',0));
            $data['sort'] = intval(input('sort',0));
            $data['test_type'] = intval(input('test_type',0));
            $data['teachers'] = input('teachers/a');

            if (!empty($data['title']) && !empty($data['cp_id']) && !empty($data['media_type']) && !empty($data['test_type']) && !empty($data['teachers'])) {

                // 判断媒体类型
                if ($data['media_type'] == 'audio' || $data['media_type'] == 'video') {
                    if(empty($data['media_path'])){
                        return json(['code' => 0, 'msg' => '请上传对应的多媒体文件']);
                    }
                } elseif ($data['media_type'] == 'text'){
                    if(empty($data['content'])){
                        return json(['code' => 0, 'msg' => '请填写文本内容']);
                    }
                }


                // 通课程下，章节名称重复判断
                if (db('curriculum_chapter')->where(['cp_id'=>$data['cp_id'],'title'=>$data['title']])->find()) {
                    return json(['code' => 0, 'msg' => '章节名称重复,章节添加失败']);
                }

                $data['teachers'] = implode(',', $data['teachers']);
                // 添加到数据库
                $res = db('curriculum_chapter')->insert($data);
                if ($res) {

                    // 章节添加成功，课程拥有的章节数量+1
                    db('curriculum')->where(['id'=>$data['cp_id']])->setInc('chapter_num');

                    return json(['code' => 200, 'msg' => '章节添加成功']);
                }
                return json(['code' => 0, 'msg' => '章节添加失败']);
            }
            return json(['code' => 0, 'msg' => '缺少必要参数，添加失败']);
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }


    /** 编辑页面加载 */
    public function edit () {

        $id = intval(input('id'));

        if ($id < 0) {

            // 获取课程记录
            $data = db('curriculum_chapter')->where(['id'=>$id])->find();

            if(!empty($data)){

                // 获取课程列表
                $courseList = db('curriculum')->where([])->field('id,title')->select();

                // 转为数组
                $data['teachers'] = !empty($data['teachers']) ? explode(',', $data['teachers']) : [];

                $teacher_list = get_user_list(25751);

                $this->assign('teacher_list', $teacher_list);
                $this->assign('list',$courseList);
                $this->assign('data',$data);
                return $this->fetch('console/curriculum_chapter/edit');
            }
            $this->assign('msg','没有找到您要编辑的记录');
            return $this->fetch('console/public/open_error_msg');
        }
        $this->assign('msg','缺少必要参数');
        return $this->fetch('console/public/open_error_msg');
    }


    /**
     * 记录异步编辑
     */
    public function editChapter () {
        if (\think\Request::instance()->isPost()) {
            $id = intval(input('id'));
            $data['title'] = input('title','','strip_tags,trim');
            $data['cp_id'] = intval(input('cp_id',0));
            $data['sort']  = intval(input('sort',0));
            $data['test_type'] = intval(input('test_type',0));
            $data['teachers']  = input('teachers/a');


            if (!empty($id) && !empty($data['title']) && !empty($data['cp_id']) && !empty($data['media_type']) &&!empty($data['test_type']) && !empty($data['teachers'])) {

                // 检查记录是否存在
                $arr = db('curriculum_chapter')->where(['id'=>$id])->find();
                if(!empty($arr)){

                    // 判断 file_name 为空则 unset
                    if(empty($data['file_name'])){
                        unset($data['file_name']);
                    }

                    // 通课程下，章节名称重复判断
                    if(db('curriculum_chapter')->where(['id'=>['neq',$id],'cp_id'=>$data['cp_id'],'title'=>$data['title']])->find()){
                        return json(['code' => 0, 'msg' => '章节名称重复,章节添加失败']);
                    }

                    $data['teachers'] = implode(',', $data['teachers']);

                    $res = db('curriculum_chapter')->where(['id'=>$id])->update($data);
                    if($res){

                        // 编辑成功，判断所属课程是否发生变化
                        if($arr['cp_id'] != $data['cp_id']){
                            // 原归属课程 拥有章节数-1   新归属课程 拥有章节数+1
                            db('curriculum')->where(['id'=>$arr['cp_id']])->setDec('chapter_num');
                            db('curriculum')->where(['id'=>$data['cp_id']])->setInc('chapter_num');
                        }

                        return json(['code' => 200, 'msg' => '分类编辑成功']);
                    }
                    return json(['code' => 0, 'msg' => '分类编辑失败，请重新操作']);
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
    public function delChapter () {
        if (\think\Request::instance()->isPost()) {
            $id = intval(input('id')); // 记录ID
            if(!empty($id)){
                // 删除前 获取一下数据，检查数据是否存在
                $data = db('curriculum_chapter')->where(['id'=>$id])->find();
                if(!empty($data)){

                    // 判断下面是否有 练习题或测验题
                    if(db('curriculum_exercise')->where(['cc_id'=>$id])->find()){
                        return json(['code' => 0, 'msg' => '数据删除失败,该章节下存在练习题']);
                    }
                    if(db('curriculum_test')->where(['cc_id'=>$id])->find()){
                        return json(['code' => 0, 'msg' => '数据删除失败,该章节下存在测验题']);
                    }


                    // 执行删除
                    $res = $this->model->del($id);
                    if($res){
                        $conModel = new ChapterContent();
                        $conModel->del(['cc_id' => $id]);

                        // 删除成功，修改课程拥有章节数-1
                        db('curriculum')->where(['id' => $data['cp_id']])->setDec('chapter_num');

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
     * 章节内容列表单页载入
     * @return mixed
     */
    public function contentList () {
        $cc_id = intval(input('cc_id')); // 章节ID

        $this->assign('cc_id', $cc_id);
        return $this->fetch('console/curriculum_chapter/content');
    }

    /**
     * 章节内容列表
     */
    public function ajaxContentList () {
        $cc_id = intval(input('cc_id')); // 章节ID

        if ($cc_id > 0) {
            $conModel = new ChapterContent();

            $where = [
                'cc_id' => $cc_id
            ];
            $data = $conModel->getList($where);

            if (!empty($data)) {

                foreach ($data as $k => $v) {
                    if($v['media_type'] == 'audio'){
                        $v['media_type_name'] = '音频';
                    }elseif ($v['media_type'] == 'video'){
                        $v['media_type_name'] = '视频';
                    }else{
                        $v['media_type_name'] = '文本';
                    }
                    $data[$k] = $v;
                }

                return json([
                    'code' => 200,
                    'msg'  => '列表获取成功',
                    'data' => $data
                ]);
            }
            return json(['code' => 0, 'msg' => '没有数据', 'count' => 0, 'data' => []]);
        }
        return json(['code' => 0, 'msg' => '缺少章节ID', 'count' => 0, 'data' => []]);
    }

    public function editContent () {
        $cc_id = intval(input('cc_id')); // 章节ID
        $id    = intval(input('id', 0)); // 章节内容ID

        $conModel = new ChapterContent();

        $data = [];
        if ($id > 0) {
            $where = [
                'cc_id' => $cc_id,
                'id'    => $id
            ];

            $data = $conModel->getOne($where);

            // 文本内容反编译
            $data['content'] = htmlspecialchars_decode($data['content']);
        }

        if ($data) {
            $this->assign('data', $data);
            $fetch = 'console/curriculum_chapter/edit_content';
        } else {
            $this->assign('cc_id', $cc_id);
            $fetch = 'console/curriculum_chapter/add_content';
        }

        return $this->fetch($fetch);
    }

    public function ajaxAddContent () {
        $id    = intval(input('id', 0)); // 章节内容ID

        $data['cc_id']      = intval(input('cc_id')); // 章节ID
        $data['sort']       = intval(input('sort',0));
        $data['media_type'] = input('media_type','','strip_tags,trim');
        $data['file_name']  = input('file_name','','strip_tags,trim');
        $data['media_path'] = input('media_path','','trim');
        $data['content']    = input('content','');

        $conModel = new ChapterContent();
        $where = [];

        $msg = '添加成功';
        $error_msg = '添加失败';

        if ($id > 0) {
            $where = [
                'id' => $id
            ];

            $msg = '编辑成功';
            $error_msg = '编辑失败';
        }

        if ($conModel->save($data, $where) !== false) {
            return json(['code' => 200, 'msg' => $msg]);
        }

        return json(['code' => 0, 'msg' => $error_msg]);
    }

    public function delChapterContent () {
        $id = intval(input('id', 0)); // 章节内容ID

        $conModel = new ChapterContent();
        if ($conModel->del(['id' => $id])) {
            return json(['code' => 200, 'msg' => '数据删除成功']);
        }

        return json(['code' => 0, 'msg' => '数据删除失败']);
    }
}