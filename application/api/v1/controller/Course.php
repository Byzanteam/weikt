<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\api\v1\model\Curriculum;
use app\api\v1\model\UserCollection;

class Course extends Base
{

    // 文件保存路径
    private $save_file_path = 'static/update/';


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取课程列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_course_list()
    {
        if($this->request->isPost()) {
            // 获取列表分页参数
            $p = intval(input('p',1)); // 页码
            $l = intval(input('l',10)); // 每页显示数量
            $type_id = intval(input('t',0));  // 分类ID

            // 查询条件
            $where = [];
            if(!empty($type_id)) {
                $where = ' (c.cl_id = ' . $type_id . ')  OR (cc.parent_id = ' . $type_id . ') ';
            }

            $curModel = new Curriculum();

            // 获取课程分页列表
            $data = $curModel->getCourseList($where, $p, $l);

            if(!empty($data)) {
                // 查询成功，返回课程列表
                return json(['code' => 200, 'msg' => '课程列表获取成功', 'data' => $data]);
            }
            return json(['code' => 404, 'msg' => '没有获取到课程列表', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 获取课程详情
     */
    public function get_course_info()
    {

        if($this->request->isPost()) {

            // 参数整理
            $id   = intval(input('id',0)); // 课程ID
            $uid  = $this->userinfo['id']; //用户ID
            if(!empty($id) && !empty($uid)) {

                $curModel = new Curriculum();

                $where  = ['c.id ' => $id];
                // 获取课程分页列表
                $data = $curModel->getDetail($uid, $where);

                if ($data) {

                    // 获取课程下章节列表
                    // 学习状态暂无
                    $data['chapter_list'] = db('curriculum_chapter')->alias('ch')
                        ->join('vcr_user_study st', 'ch.id = st.chapter_id AND user_id = ' . $uid, 'LEFT')
                        ->where(['cp_id'=>$id])
                        ->field('ch.id,ch.title,ch.media_type,from_unixtime(ch.is_time, \'%Y-%m-%d\') as is_time,ch.study_num,IF(st.state, st.state, 0) AS state')
                        ->order(['sort', 'id'=>'desc'])
                        ->select();

                    return json(['code' => 200, 'msg' => '课程信息获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有找到您要查看的课程信息', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '请选择您要获取详情课程', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }

    /**
     * 课程收藏
     */
    public function collect_course () {
        if ($this->request->isPost()) {
            // 整理参数
            $u_id = $this->userinfo['id']; // 用户ID
            $c_id = intval(input('c_id',0)); // 课程ID

            if (!empty($u_id) && !empty($c_id)) {
                $model = new UserCollection();
                // 整数添加数据
                $data['curriculum_id'] = $c_id;
                $data['user_id'] = $u_id;
                $detail = $model->getDetail($data);

                $data['collection_time'] = time();
                $data['status'] = 1;

                $where = [];
                $msg   = '课程收藏成功';
                $error = '课程收藏失败';
                if ($detail) {
                    $where = ['id' => $detail['id']];
                    if ($detail['status'] == 1) {
                        $data['status'] = 0;
                        $msg   = '课程取消收藏成功';
                        $error = '课程取消收藏失败';
                    }
                }

                $res = $model->save($data, $where);

                if($res){
                    return json(['code' => 200, 'msg' => $msg, 'data' => []]);
                }

                return json(['code' => 404, 'msg' => $error, 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '参数不完整，缺少用户或课程编号', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 获取章节信息
     * @param id 章节ID
     */
    public function get_chapter_info()
    {
        if($this->request->isPost()) {

            // 参数整理
            $id = intval(input('id',0));

            if(!empty($id)) {

                // 获取章节内容
                $data = db('curriculum_chapter')
                    ->where(['id'=>$id])
                    ->field('id,cp_id,title,study_num,media_type,media_path,content,test_type,from_unixtime(is_time, \'%Y-%m-%d\') as is_time')
                    ->find();

                if(!empty($data)) {
                    // 平均
                    $data['media_path'] = SITE_URL . $data['media_path'];

                    return json(['code' => 200, 'msg' => '章节信息获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有找到您要查看的章节信息', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '请选择您要查看的章节', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 获取章节对应练习题
     * @param id 章节ID
     */
    public function get_chapter_exercise()
    {
        if($this->request->isPost()) {

            // 参数整理
            $id = intval(input('id',0));
            if(!empty($id)) {

                // 获取章节对应练习题
                $data = db('curriculum_exercise')
                    ->where(['cc_id'=>$id])
                    ->order(['sort', 'id' => 'desc'])
                    ->select();

                if(!empty($data)) {

                    // 处理媒体文件播放路径
                    foreach ($data as $k => $v) {
                        $data[$k]['media_path'] = SITE_URL . $v['media_path'];
                    }

                    return json(['code' => 200, 'msg' => '练习题获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有找到该章节下的练习题', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '请选择您要获取练习题的章节', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 获取章节对应的测验题
     * @param id 章节ID
     */
    public function get_chapter_test()
    {
        if($this->request->isPost()) {

            // 参数整理
            $id = intval(input('id',0));

            if(!empty($id)) {

                // 获取章节基本信息
                $data = db('curriculum_chapter')->where(['id'=>$id])->field('test_type')->find();
                if(!empty($data)) {

                    // 获取章节对应的测验题
                    $data['list'] = db('curriculum_test')->where(['cc_id'=>$id,'is_type'=>$data['test_type']])->select();
                    if(!empty($data)) {

                        // 测验题获取成功，判断日过是选择题，则获取对应题目下的选项
                        if($data['test_type'] == 2) {
                            foreach ($data['list'] as $k => $v) {
                                $data['list'][$k]['option'] = db('curriculum_test_option')
                                    ->where(['ct_id'=>$v['id']])
                                    ->field('id,ct_id,option_str')
                                    ->order(['sort','id'])
                                    ->select();
                            }
                        }

                        return json(['code' => 200, 'msg' => '测验题获取成功', 'data' => $data]);
                    }
                    return json(['code' => 404, 'msg' => '没有找到该章节对应的测验题记录', 'data' => []]);
                }
                return json(['code' => 404, 'msg' => '没有找到章节信息', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '请选择要获取测验题的章节', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }



    /**
     * 作业提交 - 音频
     * @param u_id 用户ID
     * @param cc_id 章节ID
     * @param files 提交文件数据
     */
    public function audio_work_submit()
    {
        if($this->request->isPost()) {

            // 参数接受
            $u_id = $this->userinfo['id'];
            $cc_id = intval(input('cc_id',0));
            $file = $_FILES['files'];

            if(!empty($u_id) && !empty($cc_id) && !empty($file) && $file['error'] == 0) {

                // 获取文件类型
                $arr = explode('/',$file['type']);
                $type = $arr[0];

                // 判断文件类型是否正确
                if($type == 'audio') {

                    // 释放变量
                    unset($arr);

                    // 获取上传文件后缀
                    $arr = explode('.',$file['name']);
                    $suffix = $arr[count($arr)-1];

                    // 设置文件名称 时间戳_用户ID_文章ID.文件原后缀
                    $file_name = time().'_'.$u_id."_".$cc_id.".$suffix";

                    // 设置文件保存路径
                    $path = $this->save_file_path.'work/';

                    // 判断文件夹是否存在，不存在则创建，创建失败的话返回错误
                    if(!is_dir($path)) {
                        // 文件夹不存在，进行创建
                        if(!mkdir ($path,0777,true)) {
                            return json(['code' => 400, 'msg' => '文件夹创建失败，请联系管理员']);
                        }
                    }


                    // 进行文件移动
                    if(move_uploaded_file($file['tmp_name'],$path.$file_name)) {

                        // 上传完成，判断下文件是否存在
                        if(file_exists($path.$file_name)) {

                            // 添加作业记录，不存在则直接添加，如果作业记录已存在，但未审核，则进行覆盖，如果以审核 则提示 已提交过
                            if(!db('user_task')->where(['chapter_id' => $cc_id, 'user_id' => $u_id])->find()) {
                                // 作业不存在，直接新增作业记录
                                // 作业数据整理
                                $workDate['chapter_id']    = $cc_id;
                                $workDate['user_id']    = $u_id;
                                $workDate['sub_time']   = time();
                                $workDate['test_type']  = 1;
                                $workDate['content']    = json_encode(['url' => $path.$file_name]);
                                $workDate['state']      = 2;

                                if(!db('user_task')->insert($workDate)) {
                                    return json(['code' => 403, 'msg' => '抱歉！作业提交失败', 'data' => []]);
                                }

                            }else{

                                // 如果作业存在，判断是否已经 点评，没有点评则对上传时间，内容进行覆盖
                                // 已点评则直接提示 作业以点评
                                if(db('user_task')->where(['chapter_id' => $cc_id, 'user_id' => $u_id, 'state' => 1])->find()) {
                                     // 作业以被点评， 直接返回 作业以点评！
                                    return json(['code' => 203, 'msg' => '作业以点评，不需要再次提交', 'data' => []]);
                                }

                                // 作业没有点评 ，进行数据覆盖
                                $workDate['sub_time']   = time();
                                $workDate['test_type']  = 1;
                                $workDate['content']    = json_encode(['url' => $path.$file_name]);

                                if(!db('user_task')->where(['chapter_id' => $cc_id, 'user_id' => $u_id, 'state' => 2])->update($workDate)) {
                                    return json(['code' => 403, 'msg' => '抱歉！作业提交失败', 'data' => []]);
                                }

                            }

                            // 作业提交成功


                            // 判断 盖章节是否已经学习过，如果已经存在学习记录 并且学习完成 则不累计 学习章节
                            if(!db('user_study')->where(['chapter_id' => $cc_id, 'user_id' => $u_id, 'state' => 2])->find()) {
                                // 学习完成章节数+1
                                db('user_basic')->where(['id'=>$u_id])->setInc('curriculum');
                            }


                            // 更新学习记录
                            // 查询 学习 记录是否存在，存在则检查状态，修改学习中未 学习完成
                            if(db('user_study')->where(['chapter_id' => $cc_id, 'user_id' => $u_id])->find()) {
                                // 修改状态为2 已学习完成
                                db('user_study')->where(['chapter_id' => $cc_id, 'user_id' => $u_id])->update(['state' => 2]);
                            }else{
                                // 学习记录不存在，新添加一条，并直接设置状态为2 学习完成
                                $studyData['chapter_id']    = $cc_id;
                                $studyData['user_id']       = $u_id;
                                $studyData['study_date']    = time();
                                $studyData['study_time']    = 0;
                                $studyData['state']         = 2;
                                db('user_study')->insert($studyData);
                            }


                            return json(['code' => 200, 'msg' => '作业上传成功，请等候老师点评', 'data' => []]);
                        }
                        return json(['code' => 403, 'msg' => '上传失败，文件在上传过程中丢失', 'data' => []]);
                    }
                    return json(['code' => 403, 'msg' => '上传失败', 'data' => []]);
                }
                return json(['code' => 403, 'msg' => '上传失败，文件类型不允许', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '音频作业提交失败，数据不完整', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 作业提交 - 选择题作业提交
     * @param u_id      用户ID
     * @param cc_id     章节ID
     * @param result    选择题作业 二维数组
     */
    public function slect_work_submit()
    {
        if($this->request->isPost()) {

            // 整理参数
            $u_id    = $this->userinfo['id'];
            $cc_id  = intval(input('cc_id',0));
            $result = input('result/a');

            if(!empty($u_id) && !empty($cc_id) && !empty($result) && is_array($result)) {

                // 1.写入作业记录
                // 2.更新累计学习章节数量
                // 3.添加/更新学习记录
                // 4.返回作业结果

                // 判断作业记录是否存在，不存在，则直接添加
                // 存在则判断作业是否点评，没有则进行数据覆盖，已点评则直接返回 作业已提交
                if(!db('user_task')->where(['chapter_id' => $cc_id, 'user_id' => $u_id])->find()) {
                    // 作业记录不存在，添加作业记录
                    // 整理作业数据
                    $workDate['chapter_id'] = $cc_id;
                    $workDate['user_id']    = $u_id;
                    $workDate['sub_time']   = time();
                    $workDate['test_type']  = 2;
                    $workDate['content']    = json_encode($result);
                    $workDate['state']      = 2;

                    if(!db('user_task')->insert($workDate)) {
                        return json(['code' => 403, 'msg' => '抱歉！作业提交失败', 'data' => []]);
                    }

                }else{

                    // 作业记录存在，判断是否点评
                    if(db('user_task')->where(['chapter_id' => $cc_id, 'user_id' => $u_id, 'state' => 1])->find()) {
                        // 作业以点评，返回提示
                        return json(['code' => 203, 'msg' => '作业以点评，不需要再次提交', 'data' => []]);

                    }

                    // 作业未点评，数据覆盖
                    $workDate['sub_time']   = time();
                    $workDate['test_type']  = 2;
                    $workDate['content']    = json_encode($result);

                    if(!db('user_task')->where(['chapter_id' => $cc_id, 'user_id' => $u_id, 'state' => 2])->update($workDate)) {
                        return json(['code' => 403, 'msg' => '抱歉！作业提交失败', 'data' => []]);
                    }

                }

                // 作业提交成功


                // 判断 盖章节是否已经学习过，如果已经存在学习记录 并且学习完成 则不累计 学习章节
                if(!db('user_study')->where(['chapter_id' => $cc_id, 'user_id' => $u_id, 'state' => 2])->find()) {
                    // 学习完成章节数+1
                    db('user_basic')->where(['id'=>$u_id])->setInc('curriculum');
                }


                // 更新学习记录
                // 查询 学习 记录是否存在，存在则检查状态，修改学习中未 学习完成
                if(db('user_study')->where(['chapter_id' => $cc_id, 'user_id' => $u_id])->find()) {
                    // 修改状态为2 已学习完成
                    db('user_study')->where(['chapter_id' => $cc_id, 'user_id' => $u_id])->update(['state' => 2]);
                }else{
                    // 学习记录不存在，新添加一条，并直接设置状态为2 学习完成
                    $studyData['chapter_id']    = $cc_id;
                    $studyData['user_id']       = $u_id;
                    $studyData['study_date']    = time();
                    $studyData['study_time']    = 0;
                    $studyData['state']         = 2;
                    db('user_study')->insert($studyData);
                }

                // 获取做题结果
                $correctArr = $this->jubSelectResult($result);

                return json(['code' => 200, 'msg' => '作业上传成功，请等候老师点评', 'data' => $correctArr]);
            }
            return json(['code' => 403, 'msg' => '作业提交失败，缺少必要参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 对选择题结果进行判断
     */
    public function jubSelectResult($result)
    {

        $correctArr = [];
        // 总题目数量
        $correctArr['total_topic'] = count($result);
        // 正确题目数量
        $correctTopic = 0;
        // 错误题目数量
        $errorTopic = 0;

        foreach ($result as $k => $v) {
            // 查询，如果选项错误 则获取正确选项
            if(db('curriculum_test_option')->where(['ct_id' => $k, 'id' => $v, 'state' => 2])->find()) {
                // 题目错误，错题数+1
                $errorTopic = $errorTopic + 1;

                // 获取题目
                $data = db('curriculum_test')->where(['id'=>$k])->field('id,cc_id,is_type,topic')->find();
                // 获取正确选项ID
                $correct_option = db('curriculum_test_option')->where(['ct_id' => $k, 'state' => 1])->field('id,analyze')->find();
                $data['correct_option'] = $correct_option['id'];
                $data['currect_analyze'] = $correct_option['analyze'];
                // 获取所有选项
                $data['child_options'] = db('curriculum_test_option')->where(['ct_id' => $k])->field('id,ct_id,option_str,state')->select();
                $correctArr['list'][] = $data;
            }else{
                $correctTopic = $correctTopic + 1;
            }
        }

        $correctArr['correct_topic'] = $correctTopic;
        $correctArr['error_topic'] = $errorTopic;

        return $correctArr;
    }

}

