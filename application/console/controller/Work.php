<?php
/**
 * 作业处理控制器
 */
namespace app\console\controller;

use app\console\Base;
use app\console\model\UserTask as userTask_model;

class Work extends Base
{
    private $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new userTask_model();

    }

    /**
     * 作业管理页面加载
     */
    public function index()
    {

        return $this->fetch('console/work/index');

    }

    /**
     * 异步获取列表数据
     */
    public function getWorkList()
    {

        if(\think\Request::instance()->isGet()){

            $page = intval(input('page',1));
            $limit = intval(input('limit',10));
            // 筛选参数接受
            $chapter_id = intval(input('chapter_id',0));
            $user_id = intval(input('user_id',0));

            $where = [];

            if($chapter_id > 0){
                $where['chapter_id'] = ['eq',$chapter_id];
            }

            if($user_id > 0){
                $where['user_id'] = ['eq',$user_id];
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
     * 记录查看页面
     */
    public function get_view()
    {

        if(\think\Request::instance()->isGet()){

            $id = intval(input('id'));

            if(!empty($id)){

                // 获取作业记录
                $data = db('user_task')->where(['id' => $id])->find();

                if(!empty($data)){

                    // 获取作业内容
                    if($data['test_type'] == 1){
                        // 阅读题
                        $work['topic'] = db('curriculum_test')->where(['cc_id' => $data['chapter_id']])->find();
                        $work['topic']['topic'] = htmlspecialchars_decode($work['topic']['topic']);
                        $arr = json_decode($data['content'], true);
                        $work['work'] = '/'.$arr['url'];

                        $temp_path = 'console/work/read_view';
                    }else{

                        // 选择题
                        $arr = json_decode($data['content'], true);
                        foreach ($arr as $k => $v){
                            $res = db('curriculum_test')->where(['id' => $k])->find();
                            $topic = $res;
                            $topic['correct_option'] = db('curriculum_test_option')->where(['id' => $v])->find();
                            // 获取题目选项
                            $topic['options'] = db('curriculum_test_option')->where(['ct_id' => $res['id']])->order(['sort', 'id' => 'desc'])->select();
                            $work[] = $topic;
                        }

                        $temp_path = 'console/work/option_view';
                    }

                    $this->assign('data',$work);
                    return $this->fetch($temp_path);
                }
                $this->assign('msg','没有找到您要查看的记录');
                return $this->fetch('console/public/open_error_msg');
            }
            $this->assign('msg','缺少必要参数');
            return $this->fetch('console/public/open_error_msg');
        }
        $this->assign('msg','错误的请求');
        return $this->fetch('console/public/open_error_msg');
    }


    /**
     * 点评页面
     */
    public function review()
    {

        if(\think\Request::instance()->isGet()){

            $id = intval(input('id'));

            if(!empty($id)){

                if($data = db('user_task')->where(['id' => $id])->find()){

                    if($data['state'] != 1){

                        $this->assign('id',$id);
                        return $this->fetch('console/work/review');
                    }
                    $this->assign('msg','该作业已点评');
                    return $this->fetch('console/public/open_error_msg');
                }
                $this->assign('msg','没有找到您要查看的记录');
                return $this->fetch('console/public/open_error_msg');
            }
            $this->assign('msg','缺少必要参数');
            return $this->fetch('console/public/open_error_msg');
        }
        $this->assign('msg','错误的请求');
        return $this->fetch('console/public/open_error_msg');
    }


    /**
     * 记录点评提交
     */
    public function editReview()
    {
        if(\think\Request::instance()->isPost()){
            $id = intval(input('id'));
            $data['comment'] = input('comment','','strip_tags,trim');

            if(!empty($id) && !empty($data['comment']) ){

                $data['name'] = $this->userinfo['name'];
                $data['img'] = $this->userinfo['headimgurl'];
                $data['state'] = 1;

                $res = db('user_task')->where(['id'=>$id])->update($data);
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