<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\api\v1\model\Curriculum;
use app\api\v1\model\CurriculumClassification;

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
    public function get_home_data () {

        // 首页数据包括：
        // 1. 分类信息：推荐首页的1.2级分类 分类ID、名称、2级分类拥有的课程数量
        // 2. 热门课程：1条课程下章节学习人数总和第一的课程，所属分类名称、分类ID、课程ID、课程名称、学习人员总数
        // 3. 推荐课程：推荐到首页的课程。分类名称、分类ID、课程ID、课程名称、学习人员总数
        // 4. 学员信息：头像，名称，周学习排名，累加学习天数，累加完成章节数


        $lag = input('lag', 'cn'); // cn学中文  en学英语

        // 获取 推荐首页 的顶级分类 2条
        $where = [
            'label'     => $lag,
            'parent_id' => 0,
            'level'     => 0
        ];
        $class_model = new CurriculumClassification();

        $result['recommend_classify'] = $class_model->getOne($where, 'id,name');

        if ($result['recommend_classify']) {
            // 获取子级推荐分类 并统计子分类下的课程数量

            $result['recommend_classify']['childs'] = $class_model->getChildList($result['recommend_classify']['id']);

            $curModel = new Curriculum();

            $fields = 'any_value(cl.id) as classify_id,any_value(cl.name) as classify_name,c.id';
            $fields .= ',any_value(c.title) as title,any_value(IF(SUM(study_num),SUM(study_num),0)) AS study_num';
            $fields .= ',any_value(IF(st.state,st.state,0)) as is_study,any_value(index_img)';
            // 获取热门课程
            $curWhere = ['cl.label' => $lag];
            $result['popular_course'] = $curModel->getList($this->userinfo['id'], $curWhere, 'study_num desc', 3, $fields);

            // 获取推荐课程
            $curWhere['c.state'] = 1;
            $result['recommend_course'] = $curModel->getList($this->userinfo['id'], $curWhere, 'c.chapter_num desc', 0, $fields);


            // 获取用户排名
            $rank_no = 10;
            if ($list = get_user_rank_no(2, $this->userinfo['id'], 0)) {
                $rank_no = $list[0]['rank_no'];
            }
            // 用户信息整理
            $result['userinfo'] = [
                'id'         => $this->userinfo['id'],
                'name'       => $this->userinfo['name'],
                'headimgurl' => $this->userinfo['headimgurl'],
                'rank_no'    => $rank_no,
                'studytime'  => $this->userinfo['studytime'],
                'curriculum' => $this->userinfo['curriculum']
            ];

            return json(['code'=>200, 'msg'=>'获取成功！', 'data'=>$result]);
        } else {
            return json(['code'=>404, 'msg'=>'分类不存在', 'data'=>[]]);
        }


    }

}
