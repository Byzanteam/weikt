<?php
namespace app\console\model;

use think\Model;

class UserBasic extends Model {

    /**
     * 分页获取数据列表
     * @param array $where 查询条件
     * @param int $page 分页页码
     * @param int $limit 每页显示数量
     * @param string $order 排序方式
     * @return array
     * @throws \think\exception\DbException
     */
    public function getTablePageList ($where = [], $page = 1, $limit = 10, $order = 'id desc') {

        // tp5 分页调用方式
        $res = $this->where($where)
            ->order($order)
            ->paginate($limit,false,[
                'page' => $page,
            ])
            ->toArray();

        return $res;
    }

    /**
     * 根据ID删除
     * @param $id
     * @return int
     */
    public function del ($id) {
        return $this->where(['id'=>$id])->delete();
    }

    /**
     * 获取一条数据
     * @param array $where 条件
     * @param string $fields 要获取的字段
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOne ($where = [], $fields = '*') {
        return $this->field($fields)
            ->where($where)
            ->find();
    }

    /**
     * 处理登录时的数据处理
     * @param array $userinfo 用户信息
     * @return bool|string
     * @throws \think\Exception
     */
    public function update_user_info ($userinfo = [], $is_teacher = true) {

        // 查询用户是否存在
        $where = [
            'll_id' => $userinfo['id']
        ];

        // 整理用户数据
        $data['ll_id']      = $userinfo['id'];
        $data['name']       = $userinfo['name'];
        $data['nickname']   = $userinfo['nickname'];
        $data['phone']      = $userinfo['phone'];
        $data['openid']     = $userinfo['openid'];
        $data['headimgurl'] = $userinfo['headimgurl'];
        $data['root_organization_ids']  = import(',', $userinfo['root_organization_ids']);

        if ($this->save($data, $where)) {

            if ($is_teacher === false) {
                // 判断 如果本次登录时间 大于 上次登录时间的23:59 则表示已经过了一天，总学习时间 +1
                $where['last_time < ?'] = date('Y-m-d 00:00:00');
                $this->where($where)
                    ->setInc('studytime')
                    ->update(['last_time' => date('Y-m-d H:i:s')]);
            }

            // 生成用户 登录token
            $login_token = strtoupper(md5('weikt-'.md5($userinfo['id'].'-'.time())));

            // 存储session k:login_token  v:用户ID
            session($login_token, $userinfo['id']);

            // 返回login_token
            return $login_token;
        }

        return false;
    }

}