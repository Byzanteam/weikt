<?php
namespace app\console\model;

use think\Model;

class ChapterContent extends Model {

    /**
     * 根据ID删除
     * @param $where
     * @return int
     */
    public function del ($where) {
        return $this->where($where)->delete();
    }

    /**
     * 获取章节的内容列表
     * @param array $where
     * @param string $fields
     * @param array $order
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList ($where = [], $fields = '*', $order = ['sort', 'id' => 'desc']) {
        return $this->field($fields)
            ->where($where)
            ->order($order)
            ->select();
    }

    /**
     * 获取章节信息
     * @param array $where
     * @param string $fields
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
}