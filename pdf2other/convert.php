<?php
$params = empty($argv)? $_GET: $argv;
isset($params[1]) && $params['d'] = $params[1];
if(!isset($params['d'])){
    exit('Parameter error.');
}
$basepath = str_replace('\\', '/', dirname(realpath(__FILE__)));

$dir_tpl = $basepath.'/tpl';
$tpl_htm = $dir_tpl.'/tpl.htm';
$tpl_hhc = $dir_tpl.'/tpl.hhc';
$tpl_hhk = $dir_tpl.'/tpl.hhk';
$tpl_hhp = $dir_tpl.'/tpl.hhp';
$tpl_tit = 'ตฺ {__name__} าณ';

$dir_htm = $basepath.'/'.$params['d'].'/htm';
$dir_png = $basepath.'/'.$params['d'].'/png';
$dir_txt = $basepath.'/'.$params['d'].'/txt';
is_dir($dir_htm) || mkdir($dir_htm);
is_dir($dir_htm.'/png') || mkdir($dir_htm.'/png');

create_htm($dir_htm, $dir_png, $dir_txt, $tpl_htm, $tpl_tit, 'utf-8', 'gbk//ignore');
create_css($dir_tpl, $dir_htm);
create_js($dir_tpl,  $dir_htm);
create_hhp($tpl_hhp, $dir_htm);
create_hhc($tpl_hhc, $dir_htm, $tpl_tit);
create_hhk($tpl_hhk, $dir_htm, $tpl_tit);

exit('### Done ###');

//################### function ########################
function create_htm($dir_htm, $dir_png, $dir_txt, $tpl_htm, $tpl_tit, $from_encoding = 'gbk', $to_encoding = 'gbk'){
    $template = file_get_contents($tpl_htm);
    $files = get_sort_files($dir_png."/*.*");
    $count = count($files);
    foreach($files as $key => $file){
        $basename = basename($file);
        $onlyname = substr($basename, 0, -4);
        $is_last  = ($key == $count);
        $html = set_png($template, $basename);
        $html = set_txt($html, $dir_txt.'/'.$onlyname.'.txt', $is_last, $tpl_tit, $from_encoding, $to_encoding);
        file_put_contents($dir_htm.'/'.$onlyname.'.htm', $html);
        copy($file, $dir_htm.'/png/'.$basename);
        echo $file, "<br/>\n";
    }
}

function set_png($html, $file){
    $html = str_replace('{__png__}', '<img src="./png/'.$file.'" />', $html);
    return $html;
}

function set_txt($html, $file, $is_last, $tpl_tit, $from_encoding = 'gbk', $to_encoding = 'gbk'){
    $txt  = file_get_contents($file);
    if($to_encoding !== $from_encoding){
       $txt = iconv($from_encoding, $to_encoding, $txt);
    }
    $html = str_replace('{__txt__}', '<pre class="hidden">'.$txt.'</pre>', $html);
    $html = str_replace('{__title__}', str_replace('{__name__}', substr(basename($file), 0, -4), $tpl_tit), $html);
    if($is_last){
        $html = str_replace('{__extra__}', '<div class="hidden"><a id="_next" href="1.htm">next</a></div>', $html);
    } else {
        $html = str_replace('{__extra__}', '', $html);
    }
    return $html;
}

function create_css($from, $to, $file = 'pdf.css'){
    copy($from.'/'.$file, $to.'/'.$file);
}

function create_js($from, $to, $file = 'pdf.js'){
    copy($from.'/'.$file, $to.'/'.$file);
}

function create_hhp($hhp_tpl, $htm){
    $files = '';
    foreach(get_sort_files($htm."/*.htm") as $file){
        $files .= basename($file)."\r\n";
    }
    $hhp = file_get_contents($hhp_tpl);
    file_put_contents($htm.'/pdf.hhp', str_replace('{__files__}', $files, $hhp));
}

function create_hhc($hhc_tpl, $htm, $tpl_tit){
    $hhc_cell = <<<EOT
	<LI> <OBJECT type="text/sitemap">
		<param name="Name" value="{__title__}">
		<param name="Local" value="./{__file__}">
		<param name="ImageNumber" value="{__number__}">
		</OBJECT>

EOT;
    $hhc_cells = '';
    foreach(get_sort_files($htm."/*.htm") as $file){
        $name  = str_replace('{__name__}', substr(basename($file), 0, -4), $tpl_tit);
        $local = basename($file);
        $num   = 1;
        $hhc_cells .= str_replace(array('{__title__}', '{__file__}', '{__number__}'), array($name, $local, $num), $hhc_cell);
    }
    $hhc = file_get_contents($hhc_tpl);
    file_put_contents($htm.'/pdf.hhc', str_replace('{__hhc__}', $hhc_cells, $hhc));
}

function create_hhk($hhk_tpl, $htm, $tpl_tit){
    $hhk_cell = <<<EOT
	<LI> <OBJECT type="text/sitemap">
		<param name="Name" value="{__title__}">
		<param name="Local" value="{__file__}">
		</OBJECT>

EOT;
    $hhk_cells = '';
    foreach(get_sort_files($htm."/*.htm") as $file){
        $name  = str_replace('{__name__}', substr(basename($file), 0, -4), $tpl_tit);
        $local = basename($file);
        $hhk_cells .= str_replace(array('{__title__}', '{__file__}'), array($name, $local), $hhk_cell);
    }
    $hhk = file_get_contents($hhk_tpl);
    file_put_contents($htm.'/pdf.hhk', str_replace('{__hhk__}', $hhk_cells, $hhk));
}

function get_sort_files($pattern){
    $files = glob($pattern);
    $arrs  = array();
    foreach($files as $file){
        $k = substr(basename($file), 0, -4);
        $arrs[$k] = $file;
    }
    ksort($arrs);
    return $arrs;
}
