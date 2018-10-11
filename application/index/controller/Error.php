<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 11:29
 */

namespace app\index\controller;


class Error
{
    public function index()
    {

        return json(['code' => 405,'msg' => 'No routing path can be found for the request']);

    }
}