<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2015 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Codebase by e-mail at: jacky325@qq.com

The latest version of Codebase can be obtained from:
https://github.com/uniqid/codebase

*************************************************/
class TablePrinter{
    public $skin = array(
        'lt' => '┌', 'mt' => '┬', 'rt' => '┐',
        'lm' => '├', 'mm' => '┼', 'rm' => '┤',
        'lb' => '└', 'mb' => '┴', 'rb' => '┘',
        'x'  => '─', 'y'  => '│'
    );

    public function __construct($skin = array()){
        empty($skin) || $this->skin = array_merge($this->skin, $skin);
    }

    public function pr($trs){
        $cols = array();
        foreach($trs as $tds){
            foreach($tds as $key => $td){
                isset($cols[$key]) || $cols[$key] = 2;
                $cols[$key] < mb_strlen($td) + 2  && $cols[$key] = mb_strlen($td) + 2;
            }
        }
        $this->_print_table($trs, $cols);
    }

    private function _print_table($trs, $cols){
        extract($this->skin);
        $this->_print_top($lt, $mt, $rt, $x, $cols);
        foreach($trs as $trkey => $tds){
            $trkey > 0 && $this->_print_mid($lm, $mm, $rm, $x, $cols);
            echo $y;
            foreach($tds as $tdkey => $td){
                echo $tdkey > 0? $y: '', ' ', $td;
                //$spc_len = $cols[$tdkey]*2 - strlen(preg_replace('/[\x{4e00}-\x{9fa5}]{1}/u', '  ', $td)) -1;
                $spc_len = $cols[$tdkey] - strlen(preg_replace('/[\x{4e00}-\x{9fa5}]{1}/u', '  ', $td)) -1;
                echo str_repeat(' ', $spc_len);
            }
            echo $y, "\n";
        }
        $this->_print_bot($lb, $mb, $rb, $x, $cols);
    }

    private function _print_top($lt, $mt, $rt, $x, $cols){
        echo $lt;
        foreach($cols as $key => $col){
            echo $key>0? $mt: '', str_repeat($x, $col);
        }
        echo $rt, "\n";
    }

    private function _print_mid($lm, $mm, $rm, $x, $cols){
        echo $lm;
        foreach($cols as $key => $col){
            echo $key>0? $mm: '', str_repeat($x, $col);
        }
        echo $rm, "\n";
    }

    private function _print_bot($lb, $mb, $rb, $x, $cols){
        echo $lb;
        foreach($cols as $key => $col){
            echo $key>0 ? $mb: '', str_repeat($x, $col);
        }
        echo $rb, "\n";
    }
}

header("content-type:text/html;charset=utf-8");
echo "<pre>";
$printer = new TablePrinter();
$printer->pr(array(
    array('id', 'name', 'description'),
    array('1', 'Jacky', 'PHPer')
));
echo "</pre>";
