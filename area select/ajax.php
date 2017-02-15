<?php
header("Content-Type:text/html; charset=utf-8;");
$conn = mysql_connect("localhost", "root", "flyfish");
mysql_select_db("flyfish");
mysql_query("set names utf8");
$actions = array("city");
$action = isset($_POST['action'])? $_POST['action']: null;
if(is_null($action) || !in_array($action, $actions)){
    echo "Deny!";
}
else if($action == 'city'){
    $rs = mysql_query("select * from city where pcode = '{$_POST['code']}'");
    $first = true;
    while($city = mysql_fetch_array($rs, MYSQL_ASSOC)){
        if($first){
            echo "<option selected='selected' value='{$city['code']}'>{$city['name']}</option>";
            $first = false;
        }
        else{
            echo "<option value='{$city['code']}'>{$city['name']}</option>";
        }
    }
}
?>