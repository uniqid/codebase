<?php
header("content-type:text/html;charset=utf-8");

require "TablePrinter.php";
$printer = new TablePrinter();
$printer->pr(array(
    array('id', 'name', 'description'),
    array('1', 'Jacky', 'Jacky Yu <jacky325@qq.com>')
));


require "Txt2chm.php";
$base = str_replace("\\", "/", dirname(realpath(__FILE__))) . "/res/Txt2chm";
$parser = new Txt2chm(array(
    'txt_file' => $base.'/txt/1.txt',
    'pattern'  => '/\n第[^\x{3000}\n]+\n/uis',
    'tpl_path' => $base.'/tpl/007/',
    'htm_path' => $base.'/htm/demo/',
    'chm_title'=> 'Just a demo'
));
$parser->create_chm();
