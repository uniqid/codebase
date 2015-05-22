<?php
error_reporting(0);
define('IN_CODEBASE', true);
define('AUTHOR_EMAIL', 'jacky325@qq.com');
define('COMMON', str_replace("\\", "/", dirname(dirname(realpath(__FILE__)))));
define('APP', dirname(COMMON));
define('BASE_URL', $_SERVER['REQUEST_SCHEME'] ."://". $_SERVER['HTTP_HOST']);

//Initialize database Settings 
require_once(COMMON . '/cfg/database.php');
require_once(COMMON . '/lib/mysql.php');
extract($dbConfig);
$db = Mysql::getInstance($username, $password, $database, $host, $port, $encoding);

require_once(COMMON . '/inc/function.php');
?>
