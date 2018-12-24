<?php
namespace app\api\v1\controller;

use app\api\Base;
use app\api\v1\model\CurriculumClassification;

class Classify extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取课程列表
     * @return \think\response\Json
     */
    public function getClassList () {

        if ($this->request->isPost()) {
            $c_id = (int)input('pi', 0); // 分类ID

            $ccModel = new CurriculumClassification();

            $data = $ccModel->getChildList($c_id, 0);

            if (!empty($data)) {
                // 查询成功，返回课程列表
                return json(['code' => 200, 'msg' => '分类数据获取成功', 'data' => $data]);
            }
            return json(['code' => 404, 'msg' => '没有获取到分类数据', 'data' => []]);
        }
        return json(['code' => 400, 'msg' => '请求方式不正确', 'data' => []]);
    }
}
