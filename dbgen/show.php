<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2015 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Picker by e-mail at: jacky325@qq.com

The latest version of Picker can be obtained from:
https://github.com/flyfishsoft/codebase

*************************************************/
require_once '../common/cfg/bootstrap.php';
empty($_REQUEST['file']) && exit('Parameter error');
$database = substr(trim($_REQUEST['file']), 0, -4);
$results  = $db->findAll("auto_{$database}_tabs", array('order' => 'orderid asc, id asc'));
empty($results) && exit('Database dictionary not found!');

foreach($results as $row){
	$cols = $db->findAll("auto_{$database}_cols", array('conditions' => array('tab' => $row['name']), 'order' => 'orderid asc, id asc'));
	$tabs[$row['name']] = $cols;
	empty($row['extra']) && $row['extra'] = '--';
	$rows[$row['name']] = $row;
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title> <?php echo ucwords($database) ?> Database Dictionary</title>
<meta name="author" content="Jacky Yu" />
<meta charset="utf-8" />
<style>
    body {font-family:verdana,tahoma; font-size:14px; margin:0;padding:0}
    table{border-collapse:collapse; display:none; padding:0px; margin:0px;border-right: 1px dotted #000000; border-bottom: 1px dotted #000000;}
    .info tr td,.info tr th{border-top: 1px dotted #000000; border-left: 1px dotted #000000; padding:5px 10px}
    .info tr th{font-weight:bold; height:16px;}
    a{text-decoration:none; color:#0000ff;}
    a:hover{text-decoration:underline;}
    .item {white-space: nowrap;text-align: center; width:10px;}
    ul{margin:0px;padding:0px;}
    li{list-style:none;line-height:20px; padding:5px 0px 5px 15px; margin:0; border-bottom:1px dotted #ccc;}

    #content{padding-left:248px;}
    #leftPart{margin-left:-248px;width:248px;float:left;overflow-y:scroll;}
    #rightPart{width:100%; float:left; overflow-x:scroll;}
</style>
</head>
<body>
<div id="content">
    <div id="leftPart">
        <ul>
        <?php 
        $key = 0;
        foreach($rows as $arr){
            echo '<li>'.($key+1).'. <a onclick="show(\'div_'.$arr['name'].'\');" href="javascript:void(0);" title="'.$arr['comment'].'">'.$arr['name'].(@$arr['discard']? '<font color="red"> [弃用]</font>': '').'</a></li>'."\n";
            $key++;
        }
        ?>
			<li><b>Updated</b>: <font color='red'><?php echo date('Y-m-d H:i', strtotime(gmdate('Y-m-d H:i'). " +8 hours")) ?></font></li>
			<li><b>Support</b>: <a href="mailto:<?php __(AUTHOR_EMAIL) ?>">Jacky Yu</a></li>
        </ul>
    </div>
    <div id="rightPart">
    <?php
    $firstFlag = true;
    foreach($tabs as $tbname => $tabs){
        if($firstFlag){ $firstFlag = false; $firstTb = $tbname;}
    ?>
        <table width="100%" class="info" id="div_<?php echo $tbname;?>">
          <?php 
			echo '<tr><th class="item">ID</th><th width="150">列名</th><th width="320">类型</th><th>备注</th> </tr>';
		    $hasCID = false;
            foreach($tabs as $key => $arr){
                $arr['name'] == "cid" && $hasCID = true;
                echo '<tr><td class="item">'.($key+1).'</td><td><a href="#">'.str_replace('"', "",$arr['name']).'</a></td><td><a href="#">'.$arr['type'].'</a></td><td>'.$arr['comment'].(@$arr['discard']? '<font color="red"> [弃用]</font>': '').'</td></tr>'."\n";
            }
            echo '<tr height="24"><td colspan="4"><span style="color:#ff0000;line-height:20px;">'.preg_replace("/CONSTRAINT\s+\w+\s+/s", "", str_replace(",", "<br />", $rows[$tbname]['extra'])).($hasCID? "<br />CREATE UNIQUE INDEX {$rows[$tbname]['name']}_cid_index ON {$rows[$tbname]['name']} USING btree(cid);": "").'</span></td></tr>';
			echo '<tr height="24"><td colspan="4"><b>'.$rows[$tbname]['name'].' ('.count($tabs).')</b> [ <span style="color:#ff0000;">'.$rows[$tbname]['comment'].'</span> ]</td></tr>';
          ?>
        </table>
        <?php } ?>
    </div>
</div>
<div style="clear:both;"></div>
<script language="javaScript">
elems = document.getElementsByTagName("table");
function show(id){
    var agent = navigator.userAgent.toLowerCase();
    var display = !/opera/.test(agent) && /msie/.test(agent)? "block": "table";
    obj = document.getElementById(id);
    obj.style.display = display;
    for(var i=0; i<elems.length; i++){
        if(elems[i].id != id){
            elems[i].style.display = "none";
        }
    }
};
function resize(){
    document.getElementById("leftPart").style.height  = document.documentElement.clientHeight + "px";
    document.getElementById("rightPart").style.height = document.documentElement.clientHeight + "px";
}
window.onresize = resize;
(function(){
    show("div_<?php echo $firstTb;?>");
    resize();
})();
</script>
 </body>
</html>