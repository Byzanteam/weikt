<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/31
 * Time: 16:43
 */

//读取文件内容
$_sql = file_get_contents('weikt_webuildus.sql');


print_r(getenv());
$_arr = explode(';', $_sql);
$_mysqli = new mysqli(getenv('DATABASE_URL'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
if (mysqli_connect_errno()) {
    exit('连接数据库出错');
}
//执行sql语句
foreach ($_arr as $_value) {
    $_mysqli->query($_value.';');
}
$_mysqli->close();
$_mysqli = null;