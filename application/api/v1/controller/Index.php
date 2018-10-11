<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\api\v1\controller\User;
use app\api\v1\model\Curriculum;

class Index extends Base
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 用户进入，请求了了接口进行权限验证，成功返回首页数据
     */


    /**
     * 登录成功，获取首页数据
     */
    public function get_home_data()
    {

        // 首页数据包括：
        // 1. 学员信息：头像，名称，周学习排名，累加学习天数，累加完成章节数
        // 2. 分类信息：推荐首页的1.2级分类 分类ID、名称、2级分类拥有的课程数量
        // 3. 热门课程：1条课程下章节学习人数总和第一的课程，所属分类名称、分类ID、课程ID、课程名称、学习人员总数
        // 4. 推荐课程：推荐到首页的课程。分类名称、分类ID、课程ID、课程名称、学习人员总数

        // 获取用户排名
        $rank_no = 10;
        if($list = (new User)->get_user_rank_no(2,$this->userinfo['id'],0)) {
            $rank_no = $list[0]['rank_no'];
        }


        // 用户信息整理
        $result['userinfo']['id'] = $this->userinfo['id'];
        $result['userinfo']['name'] = $this->userinfo['name'];
        $result['userinfo']['headimgurl'] = $this->userinfo['headimgurl'];
        $result['userinfo']['rank_no'] = $rank_no;
        $result['userinfo']['studytime'] = $this->userinfo['studytime'];
        $result['userinfo']['curriculum'] = $this->userinfo['curriculum'];

        // 获取 推荐首页 的顶级分类 2条
        $result['recommend_classify'] = db('curriculum_classification')
            ->where(['state'=>1, 'parent_id' => 0, 'level' => 0])
            ->limit(2)
            ->field('id,name,parent_id')
            ->order(['sort','id'=>'desc'])
            ->select();
        // 获取子级推荐分类 并统计子分类下的课程数量
        foreach ($result['recommend_classify'] as $k => $v) {
            $sql = 'select a.id,a.name,a.back_img,a.parent_id,b.num from vcr_curriculum_classification a left join (select cl_id, count(*) as num from vcr_curriculum group by cl_id ) as b on b.cl_id = a.id where a.parent_id = '.$v['id'].' and a.level = 1 and a.state = 1';
            $res = db('curriculum_classification')->query($sql);
            $result['recommend_classify'][$k]['childs'] = $res;
        }

        $curModel = new Curriculum();

        // 获取热门课程
        $result['popular_course'] = $curModel->getList($this->userinfo['id'], [], 'study_num desc');

        // 获取推荐课程
        $result['recommend_course'] = $curModel->getList($this->userinfo['id'], ' c.state = 1 ');

        return json(['code'=>200,'msg'=>'获取成功！','data'=>$result]);
    }

}
