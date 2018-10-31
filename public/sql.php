<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/31
 * Time: 16:43
 */

//读取文件内容
$_sql = file_get_contents('weikt_webuildus.sql');


$_arr = explode(';', $_sql);
$_mysqli = new mysqli(getenv('DATABASE_URL'), getenv('DOKKU_MYSQL_WEIKT_DB_ENV_MYSQL_USER'), getenv('DOKKU_MYSQL_WEIKT_DB_ENV_MYSQL_ROOT_PASSWORD'));
if (mysqli_connect_errno()) {
    exit('连接数据库出错');
}

$_mysqli->query('USE ' . getenv('DOKKU_MYSQL_WEIKT_DB_ENV_MYSQL_DATABASE'));
//执行sql语句
foreach ($_arr as $_value) {
    $_mysqli->query($_value.';');
}
$_mysqli->close();
$_mysqli = null;