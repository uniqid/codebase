<?php
header("Content-Type:text/html; charset=utf-8;");



//city_json
exit;
$city_json = file_get_contents("E:/code/flyfish/tools/city_json_@taobao.txt");
$citys = json_decode($city_json, true);
$conn = mysql_connect("localhost", "root", "flyfish") or die("connect to mysql fail.");
mysql_query("use flyfish", $conn);
mysql_query("set names utf8", $conn);
foreach($citys as $code => $city){
    $sql = "insert into city(code, name, pcode) values('{$code}', '{$city[0]}', '{$city[1]}');";
    mysql_query($sql);
    echo $code, "\n";
}
?>