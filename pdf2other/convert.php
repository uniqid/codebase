<?php
$params = empty($argv)? $_GET: $argv;
isset($params[1]) && $params['d'] = $params[1];
isset($params['d']) || exit('Parameter error.');
$basepath = str_replace('\\', '/', dirname(realpath(__FILE__)));

require_once($basepath.'/Converter.php');
$converter = new Converter($basepath.'/tpl', $basepath.'/'.$params['d'], 'utf-8');
$converter->conv2chm();
$converter->conv2txt();
