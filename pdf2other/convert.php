<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2016 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Codebase by e-mail at: jacky325@qq.com

The latest version of Codebase can be obtained from:
https://github.com/uniqid/codebase

*************************************************/

$params = empty($argv)? $_GET: $argv;
isset($params[1]) && $params['d'] = $params[1];
isset($params['d']) || exit('Parameter error.');
$basepath = str_replace('\\', '/', dirname(realpath(__FILE__)));

require_once($basepath.'/Converter.php');
$converter = new Converter($basepath.'/tpl', $basepath.'/'.$params['d'], 'utf-8');
$converter->conv2chm();
$converter->conv2txt();
