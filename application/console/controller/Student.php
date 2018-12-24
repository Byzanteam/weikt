<?php
namespace app\console\controller;

use app\console\Base;
use app\console\model\UserBasic as User_model;

class Student extends Base
{

    private $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new User_model();

    }

    /**
     * 学员管理页面加载
     */
    public function index()
    {

        return $this->fetch('console/student/index');

    }

    /**
     * 异步获取列表数据
     */
    public function getStudentList()
    {

        if(\think\Request::instance()->isGet()){

            $page = intval(input('page',1));
            $limit = intval(input('limit',10));

            // 筛选参数接受
            $name = input('name','','strip_tags,trim');
            $nickname = input('nickname','','strip_tags,trim');
            $phone = input('phone','','strip_tags,trim');

            $where = [];

            if(!empty($name)){
                $where['name'] = ['like','%'.$name.'%'];
            }

            if(!empty($nickname)){
                $where['nickname'] = ['like','%'.$nickname.'%'];
            }

            if(!empty($phone)){
                $where['phone'] = ['like','%'.$phone.'%'];
            }

            $data = $this->model->getTablePageList($where, $page, $limit);

            if(!empty($data['data'])){
                return json(['code' => 200, 'msg' => '列表获取成功', 'count' => $data['total'], 'data' => $data['data']]);
            }
            return json(['code' => 0, 'msg' => '没有数据了', 'count' => 0, 'data' => []]);
        }
        return json(['code' => 0, 'msg' => '没有数据', 'count' => 0, 'data' => []]);
    }


}