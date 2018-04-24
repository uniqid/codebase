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
if(!defined('IN_CODEBASE')) {
	exit('Access Denied');
}

function getConfigs($db, $pkey = '', $key = '', $pro = 'codebase'){
	$pkey = trim($pkey); $key  = trim($key); $pro = trim($pro);
	if($pkey != ''){
		if($key != ''){
			$conditions = array('pkey' => $pkey, 'key' => $key, 'pro' => $pro);
		}
		else{
			$conditions = array('pkey' => $pkey, 'pro' => $pro);
		}
	}
	else{
		$conditions = array('pro' => $pro);
	}
	$configs = $db->findAll('configs', array('conditions' => $conditions));

	$result = array();
	if(!empty($configs)){
		$isMulti = false;
		foreach($configs as $key => $config){
			if(is_numeric($key)){
				$isMulti = true;
			}

			if(!$isMulti){
				$result[$configs['key']] = $configs['val'];
			}
			else{
				$result[$config['key']] = $config['val'];
			}
		}
	}

	return $result;
}

function __($str){
	echo $str;
}

function get_req_uri(){
    $req_uri  = dirname($_SERVER['REQUEST_URI'] . "s");
    if(substr($req_uri,-1) == "\\" || substr($req_uri,-1) == "/"){
        $req_uri = substr($req_uri,0,-1);
    }
    return $req_uri;
}

function getFiles($path, $filter = array()) {
    $arr = scandir($path);
    unset($arr[array_search(".", $arr)]);
    unset($arr[array_search("..", $arr)]);
	foreach($filter as $file){
		unset($arr[array_search($file, $arr)]);
	}
    return $arr;
}

function getMatchedTables($filepath){
    $filepath = getSqlMatchedTxt($filepath);
    $content = str_replace("<", "&lt;", file_get_contents($filepath));
    $content = trim(preg_replace("/CREATE UNIQUE INDE.*?btree\(cid\);/is", "", $content));
    if(preg_match('/WITH\s+\(OIDS=FALSE\);/is', $content)){
        $arr = array_filter(preg_split("/WITH\s+\(OIDS=FALSE\);/is", $content));
    } else {
        $arr = array_filter(preg_split("/CREATE\s+TABLE\s+/is", $content));
        foreach($arr as $key => $table){
            $arr[$key] = "CREATE TABLE " . $table;
        }
    }
    $arr = array_map("trim", $arr);
    return $arr;
}

function getSqlMatchedTxt($filepath){
    $dirname  = dirname(dirname($filepath));
    $basename = pathinfo(basename($filepath));
    if(strtolower($basename['extension']) == 'sql'){
        $txtFile = $dirname .'/'. $basename['filename'].'.txt';
        if(!is_file($txtFile)){
            createSqlMatchedTxt($txtFile, $filepath);
        }
        return $txtFile;
    } else {
        return $filepath;
    }
}

function createSqlMatchedTxt($txtFile, $filepath){
    $content = file_get_contents($filepath);
    $content = preg_replace('|/\*\!.*?\ */;|is', '', $content);
    $content = preg_replace('|AUTO_INCREMENT=\d+ |is', '', $content);
    $content = preg_replace('|(CREATE TABLE `([^`]+)` )\(|is', '$1    -- $2'."\n(", $content);
    $content = preg_replace('|(  `([^`]+)`.*?,)[\r\n]+|', '$1    -- $2'."\n", $content);
    $content = preg_replace('|(  `[^`]+`.*?\s+COMMENT\s+\'(.*?)\',\s+--\s+).*?[\r\n]+|is', '$1$2'."\n", $content);
    file_put_contents($txtFile, $content);
}

function parseCsvToArray($file, $encoding = 'utf-8', $delimiter = ',', $enclosure = '"'){
	if(!$fp = fopen($file, 'r')){
		return false;
	}

	$isHeader = true;
	$lineNum  = 0;
	$default_encoding = mb_internal_encoding();
	$enc = $enclosure;
	mb_internal_encoding('UTF-8');
	while(false !== ($line = fgets($fp, 4096))){
		if(strtolower($encoding) !== 'utf-8'){
			$line = mb_convert_encoding($line, 'UTF-8', $encoding);
		}
		if($isHeader && "\xEF\xBB\xBF" == substr($line, 0, 3)){
			$line = substr($line, 3);
		}
		if(trim($line) === ''){
			continue;
		}

		$arr = explode($delimiter, $line);
		while(list($key, $val) = each($arr)){
			if(preg_match('/^\\'.$enc.'(?:\\'.$enc.'\\'.$enc.')*(?!\\'.$enc.')/is', $val)){
				$realVal = str_replace($enc.$enc, $enc, substr($val, 1));
				while(list($key, $val) = each($arr)){
					if(preg_match('/(?<!\\'.$enc.')(?:\\'.$enc.'\\'.$enc.')*\\'.$enc.'$/is', $val)){
						$realVal .= ',' . str_replace($enc.$enc, $enc, substr($val, 0, -1));
						break;
					}
					else{
						$realVal .= ',' . str_replace($enc.$enc, $enc, $val);
					}
				}
				$datas[$lineNum][] = $realVal;
			}
			else{
				$datas[$lineNum][]= preg_match('/^[\\'.$enc.']+$/is', $val)? str_replace($enc.$enc, $enc, substr($val, 1, -1)): $val;
			}
		}
		$lineNum++;
	}
	fclose($fp);
	mb_internal_encoding($default_encoding);
	return isset($datas)? $datas: false;
}

function message($msg) {
	header('Content-type: text/html; charset=UTF-8');
	echo $msg;
	exit;
}

function pr($arr){
	echo "<pre>";
	print_r($arr);
	echo "</pre>";
}
?>
