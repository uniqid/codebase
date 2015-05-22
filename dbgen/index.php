<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2015 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Codebase by e-mail at: jacky325@qq.com

The latest version of Codebase can be obtained from:
https://github.com/flyfishsoft/codebase

*************************************************/
require_once '../common/cfg/bootstrap.php';
$sql_file_path  = APP . '/dbgen/sql/';
$html_file_path = APP . '/dbgen/html/';
$req_uri  = get_req_uri();
$base_url = BASE_URL . $req_uri;
?>
<!DOCTYPE HTML>
<html>
<head>
<link rel="shortcut icon" type="image/ico" href="favicon.ico">
<link href="<?php __(dirname($base_url)) ?>/common/css/base.css" type="text/css" rel="stylesheet">
<meta charset="utf-8" />
<style>
    fieldset{border:1px solid #0000ff; margin:3px 0; padding:0 10px 10px; width:60%; display:block;}
    legend{font-weight:bold; padding:8px; color:#0000FF;cursor:pointer;}
    table{border-collapse:collapse; word-break:break-all; word-wrap:break-word; width:100%; display:none;}
    td,th{border:1px solid #000; padding:3px 10px 3px 10px;}
    th{width:120px;}
    a{color:red;}
</style>
</head>
<body>
<?php 
if(empty($_REQUEST["file"]) || !is_file($sql_file_path . trim($_REQUEST["file"]))){
    $sql_files = getFiles($sql_file_path, array('backup'));
?>
    <div class="nav-list">
        <h1>Database List</h1>
        <?php
            foreach($sql_files as $file){
                echo '<a target="_blank" href="'.$base_url.'/index.php?file='.$file.'">'.$file.'</a>';
            }
        ?>
        <div class="nav-foot RoundedCorner">
            <b class="rbottom"><b class="r4"></b><b class="r3"></b><b class="r2"></b><b class="r1"></b></b>
        </div>
    </div>
<?php
} else {
    $file = trim($_REQUEST["file"]);
    if(empty($_REQUEST["action"]) || !in_array($_REQUEST["action"], array('generate', 'html', 'delete'))){
        echo "<fieldset><legend>Actions</legend>";
            echo "<a target='_blank' href='".$base_url."/?file=".$file."&action=generate'>Create/Update Database dictionary</a><br/><br/>";
            echo "<a target='_blank' href='".$base_url."/show.php?file=".$file."'>Show Database dictionary</a><br/><br/>";
            echo "<a target='_blank' href='".$base_url."/?file=".$file."&action=html'>Create Html Database dictionary</a><br/><br/>";
            echo "<a target='_blank' href='".$base_url."/?file=".$file."&action=delete'>Delete This Database dictionary</a>";
        echo "</fieldset>";

        foreach(getMatchedTables($sql_file_path . $file)  as $table){
            preg_match("/^CREATE\s+TABLE[^`]*\s+`?(\w+)`?\s+--\s+(.*?)\n/is", $table, $tmp);
            $tableName = $tmp[1];
            $tableDesc = $tmp[2];
    ?>
         <fieldset>
             <legend><?php echo $tableName, " (", $tableDesc, ")"; ?></legend>
             <table>
                 <tr>
                     <td><pre><?php echo $table; ?></pre></td>
                 </tr>
             </table>
         </fieldset>
    <?php
        } //end foreach
    ?>
        <script language="javaScript">
            var show = function(event){
                obj = this.nextElementSibling? this.nextElementSibling: event.srcElement.nextSibling;
                var agent = navigator.userAgent.toLowerCase();
                var display = !/opera/.test(agent) && /msie/.test(agent)? "block": "table";
                if(obj.style.display==display){
                    obj.style.display = "none";
                    obj.parentNode.style.width = "60%";
                }
                else{
                    obj.style.display = display;
                    obj.parentNode.style.width = "98%";
                }
            };
            (function(){
                var elems = document.getElementsByTagName("legend");
                for(var i=0; i<elems.length; i++){
                    elems[i].addEventListener? elems[i].addEventListener( "click", show, false ): elems[i].attachEvent("onclick", show);
                }
            })();
        </script>
<?php
    } else {
        $action   = $_REQUEST["action"];
        $database = substr($file, 0, -4);
        if($action == 'generate'){
            $create_sqls = array(
                "CREATE TABLE IF NOT EXISTS `auto_{$database}_cols` (
                  `id` int(10) NOT NULL AUTO_INCREMENT,
                  `tab` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `type` varchar(400) COLLATE utf8_unicode_ci NOT NULL,
                  `comment` varchar(2000) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `orderid` int(10) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `auto_{$database}_tabs` (
                  `id` int(10) NOT NULL AUTO_INCREMENT,
                  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `extra` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
                  `orderid` int(10) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
            );
            //init tables
            foreach($create_sqls as $sql){
                $db->create($sql);
            }
            $db->truncate("auto_{$database}_cols");
            $db->truncate("auto_{$database}_tabs");

            foreach(getMatchedTables($sql_file_path . $file) as $table){
                preg_match("/^CREATE\s+TABLE[^`]*\s+`?(\w+)`?\s+--\s+(.*?)\n/is", $table, $tmp);
                $table = preg_replace("/^CREATE\s+TABLE[^`]*\s+`?(\w+)`?\s+--\s+(.*?)\n/is", "", $table);
                $cols = array_filter(preg_split("/\n/is", $table));

                foreach($cols as $col){
                    if(preg_match("/([\w\"\`]+)\s+(.*?),?\s+--\s+(.*)/is", $col, $tmpCol)){
                        $tmpCol = array_map("trim", $tmpCol);
                        $table = str_replace($col, "", $table);
                        $tmpCol[2] = str_replace("'", "''", $tmpCol[2]);
                        $tmpCol[3] = str_replace("'", "''", $tmpCol[3]);
                        $sql = "insert into auto_{$database}_cols (tab, name, type, comment) values('{$tmp[1]}', '{$tmpCol[1]}', '{$tmpCol[2]}', '{$tmpCol[3]}');";
                        echo (mysql_query($sql)? $sql: mysql_error()) . "<br/>";
                    }
                }
                $table  = trim(preg_replace("/\([\s\n]+(\s+.*?)\)$/is", "$1", $table));
                $tmp[2] = str_replace("'", "''", $tmp[2]);
                $table  = str_replace("'", "''", $table);
                $sql = "insert into auto_{$database}_tabs (name, comment, extra) values('{$tmp[1]}', '{$tmp[2]}', '{$table}');";
                echo ($db->query($sql)? $sql: $db->message) . "<br/><br/>";
            }
        }

        if($action == 'html'){
            $success = false;
            if($result = file_get_contents($base_url."/show.php?file=".$file)){
                if(file_put_contents($html_file_path . substr($file, 0, -4) . '.html', $result)){
                    $success = true;
                    echo $html_file_path . substr($file, 0, -4) . '.html';
                }
            }
            if(!$success){
                echo "Create Html Database dictionary fail!";
            }
        }

        if($action == 'delete'){
            if(!$result1 = $db->drop("auto_{$database}_tabs")){
                if($db->message == "Unknown table 'auto_{$database}_tabs'"){
                    $result1 = true;
                }
            }

            if(!$result2 = $db->drop("auto_{$database}_cols")){
                if($db->message == "Unknown table 'auto_{$database}_cols'"){
                    $result2 = true;
                }
            }

            $result = $result1 && $result2;
            if(is_file($html_file_path . substr($file, 0, -4) . '.html')){
                $result = $result && unlink($html_file_path . substr($file, 0, -4) . '.html');
            }

            if(is_file($sql_file_path . $file)){
                date_default_timezone_set('Asia/Shanghai');
                !is_dir($sql_file_path .'backup') && mkdir($sql_file_path .'backup');
                $result = $result && rename($sql_file_path . $file, $sql_file_path .'backup/'. substr($file, 0, -4) .date('_Ymd_His').'.txt');
            }
            echo "Delete Database dictionary ".($result? 'success': 'fail')."!";
        }

        echo "<script>setTimeout('window.close()', 2000);</script>";//for all action
    }
}
?>
</body>
</html>