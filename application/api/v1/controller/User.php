<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\api\v1\model\UserCollection;
use app\api\v1\model\UserStudy;
use app\api\v1\model\UserTask;
use app\console\model\UserBasic;

class User extends Base {

    public function __construct () {
        parent::__construct();
    }

    /** 用户中心首页 */
    public function user_center () {
        /**
         * 1.用户数据：头像、姓名、累计学习天数、累计完成章节数、获得点评数
         * 2.排名位数
         * 3.本月学习时间
         */
        if ($this->request->isGet()) {
            // 参数整理
            $id = $this->userinfo['id'];
            if (!empty($id)) {

                $uModel = new UserBasic();
                $tModel = new UserTask();
                // 查询用户信息

                $u_where = ['id' => $id];
                $u_fields = 'id,name,headimgurl,studytime,curriculum,root_organization_ids';

                $data['userinfo'] = $uModel->getOne($u_where, $u_fields);
                if (!empty($data['userinfo'])) {
                    // 用户信息获取成功

                    // 获取 获得 作业已点评数量
                    $data['userinfo']['review_num'] = $tModel->where(['user_id' => $data['userinfo']['id'], 'state' => 1])->count('id');

                    // 获取用户排名
                    $data['rank_no'] = 10;
                    if($list = get_user_rank_no(2, $data['userinfo']['id'], 1)) {
                        $data['rank_no'] = $list[0]['rank_no'];
                    }

                    // 获取本月学习的日期
                    $stuModel = new UserStudy();
                    $beginDate = date('Y-m-01', strtotime(date('Y-m-d')));
                    $where  = ' study_date >= ' . strtotime($beginDate);
                    $where .= ' AND study_date <= ' . strtotime(date('Y-m-d', strtotime($beginDate . ' +1 month -1 day')));

                    $s_list = $stuModel->getList($where, 'study_date');

                    $data['study_times'] = [];

                    if (!empty($s_list)) {
                        foreach ($s_list as $k => $v) {
                            $data['study_times'][] = date('Y/m/d', $v['study_date']);
                        }

                        $data['study_times'] = array_unique($data['study_times']);
                    }

                    $teacher_organ = config('llapi.teacher_organ');
                    $data['is_teacher'] = 0;

                    $data['userinfo']['root_organization_ids'] = explode(',', $data['userinfo']['root_organization_ids']);
                    foreach ($data['userinfo']['root_organization_ids'] as $k => $v){
                        if(in_array($v,$teacher_organ)){
                            $data['is_teacher'] = 1;
                        }
                    }
                    unset($data['userinfo']['root_organization_ids']);

                    return json(['code' => 200, 'msg' => '用户信息获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有找到用户信息', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }


    /** 获取收藏列表 */
    public function get_collect_list () {
        if ($this->request->isPost()) {
            // 参数整理
            $page = intval(input('p',1));   // 页码
            $limit = intval(input('l',10)); // 每页数量
            $id = $this->userinfo['id'];
            if(!empty($id)) {

                $uc_model = new UserCollection();
                // 获取收藏数据
                $data = $uc_model->getList($id, $page, $limit);

                if(!empty($data)) {
                    return json(['code' => 200, 'msg' => '收藏列表获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有获取到收藏列表', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }

    /** 获取排名列表 */
    public function get_rank_list () {
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


    /** 获取作业列表 */
    public function get_work_list () {
        if($this->request->isPost()) {
            // 参数整理
            $uid    = $this->userinfo['id'];
            $page   = intval(input('p',1));  // 分页页码
            $limit  = intval(input('l',10)); // 每页显示数量

            // 判断用户ID不为空
            if(!empty($uid)) {
                $utModel = new UserTask();
                // 获取用户作业信息
                $where  = ['user_id' => $uid];
                $fields = 'cc.title,ut.id,from_unixtime(ut.sub_time, \'%Y-%m-%d\') as sub_time,ut.state';
                $data   = $utModel->getList($where, $page, $limit, $fields);

                if (!empty($data)) {
                    return json(['code' => 200, 'msg' => '作业列表获取成功', 'data' => $data]);
                }
                return json(['code' => 404, 'msg' => '没有获取到作业记录', 'data' => []]);
            }
            return json(['code' => 403, 'msg' => '缺少关键参数', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }

    /** 获取指定作业点评详情 */
    public function get_work_info () {

        // 参数整理
        $id  = intval(input('id',0)); // 作业ID
        $uid = $this->userinfo['id'];

        if(!empty($id)) {
            $utModel = new UserTask();

            $where = [
                'ut.id' => $id,
                'ut.user_id' => $uid
            ];
            $fields = 'ut.fraction,ut.id,ut.chapter_id,from_unixtime(ut.sub_time, \'%Y-%m-%d\') as sub_time,ut.state,ut.comment,u.name,u.headimgurl as img,cc.title';
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
}
