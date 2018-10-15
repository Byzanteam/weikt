<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\api\v1\model\UserTask;
use think\Db;

class User extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 用户中心首页
     */
    public function user_center()
    {
        /**
         * 1.用户数据：头像、姓名、累计学习天数、累计完成章节数、获得点评数
         * 2.排名位数
         * 3.本月学习时间
         */
        if($this->request->isGet()) {
            // 参数整理
            $id = $this->userinfo['id'];
            if(!empty($id)) {

                // 查询用户信息
                $data['userinfo'] = db('user_basic')->where(['id'=>$id])->field('id,name,headimgurl,studytime,curriculum')->find();
                if(!empty($data['userinfo'])) {
                    // 用户信息获取成功

                    // 获取 获得 作业已点评数量
                    $data['userinfo']['review_num'] = db('user_task')->where(['user_id' => $data['userinfo']['id'], 'state' => 1])->count('*');

                    // 获取用户排名
                    $rank_no = 10;
                    if($list = get_user_rank_no(2, $data['userinfo']['id'])) {
                        $rank_no = $list[0]['rank_no'];
                    }
                    $data['rank_no'] = $rank_no;

                    // 获取本月学习的日期 (暂时写死)
                    $data['study_times'] = ['1537500949','1537445809','1537438969'];

                    return json(['code' => 200, 'msg' => '用户信息获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有找到用户信息', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 获取收藏列表
     * @param p 页码
     * @param l 每页数量
     */
    public function get_collect_list()
    {
        if($this->request->isPost()) {
            // 参数整理
            $page = intval(input('p',1));
            $limit = intval(input('l',10));
            $id = $this->userinfo['id'];
            if(!empty($id)) {

                // 获取收藏数据
                $data = db('user_collection')->alias('uc')
                    ->join('vcr_curriculum c', 'uc.curriculum_id = c.id')
                    ->where(['uc.user_id'=>$id])
                    ->field('uc.id,uc.curriculum_id,c.title')
                    ->page($page,$limit)
                    ->order(['uc.id'=>'desc'])
                    ->select();

                if(!empty($data)) {
                    return json(['code' => 200, 'msg' => '收藏列表获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有获取到收藏列表', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }

    /**
     * 获取排名列表
     */
    public function get_rank_list()
    {
        if($this->request->isPost()) {

            $uid = $this->userinfo['id'];
            $type = intval(input('type',0)); // 获取类型  0.周榜 1.月榜 2.总榜

            if(!empty($uid)) {

                $data = [];

                if($type == 0) {
                    // 总榜获取
                    $data['user_ranking'] = get_user_rank_no(2,$uid)[0];
                    $data['total_list'] = get_user_rank_no(1,0);
                }elseif($type == 1) {
                    // 周榜获取
                    $data['user_ranking'] = get_user_rank_no(2,$uid,1)[0];
                    $data['total_list'] = get_user_rank_no(1,0,1);
                }else{
                    // 月榜获取
                    $data['user_ranking'] = get_user_rank_no(2,$uid,2)[0];
                    $data['total_list'] = get_user_rank_no(1,0,2);
                }


                if($data['user_ranking'] != false || $data['total_list'] != false) {
                    return json(['code' => 200, 'msg' => '榜单列表获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有获取到排行榜单', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /**
     * 获取作业列表
     * @param p 分页页码
     * @param l 每页显示数量
     */
    public function get_work_list()
    {
        if($this->request->isPost()) {
            // 参数整理
            $uid    = $this->userinfo['id'];
            $page   = intval(input('p',1));
            $limit  = intval(input('l',10));

            // 判断用户ID不为空
            if(!empty($uid)) {
                $utModel = new UserTask();
                // 获取用户作业信息
                $where  = ['user_id' => $uid];
                $fields = 'cc.title,ut.id,from_unixtime(ut.sub_time, \'%Y-%m-%d\') as sub_time,ut.state';
                $data   = $utModel->getList($where, $page, $limit, $fields);
                if(!empty($data)) {
                    return json(['code' => 200, 'msg' => '作业列表获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有获取到作业记录', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }

    /**
     * 获取指定作业点评详情
     * @param id 作业ID
     */
    public function get_work_info()
    {
        if($this->request->isPost()) {
            // 参数整理
            $id  = intval(input('id',0));
            $uid = $this->userinfo['id'];

            if(!empty($id)) {
                $utModel = new UserTask();

                $where = [
                    'ut.id' => $id,
                    'ut.user_id' => $uid
                ];
                $fields = 'ut.id,ut.chapter_id,from_unixtime(ut.sub_time, \'%Y-%m-%d\') as sub_time,ut.state,ut.comment,ut.name,ut.img,cc.title';
                $data = $utModel->getDetail($where, $fields);
                // 检查作业是否点评
                if (!empty($data)) {
                    if ($data['state'] == 1) {
                        return json(['code' => 200, 'msg' => '作业点评获取成功', 'data' => $data]);
                    }
                    return json(['code' => 404, 'msg' => '作业还未点评', 'data' => []]);
                }
                return json(['code' => 404, 'msg' => '没有找到作业的点评信息', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '请选择要查看点评的作业', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }
}

