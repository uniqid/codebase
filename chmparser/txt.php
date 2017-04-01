<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2017 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Codebase by e-mail at: jacky325@qq.com

The latest version of Codebase can be obtained from:
https://github.com/uniqid/codebase

*************************************************/
Class Txt{
    protected $cfgs = array();
    public function __construct($cfgs = array()){
        if(!is_file($cfgs['txt_file']) || empty($cfgs['pattern']) || empty($cfgs['tpl_path']) || empty($cfgs['htm_path'])){
            exit('Failed to initiate the txt parser');
        }
        is_dir($cfgs['tpl_path']) || mkdir($cfgs['tpl_path'], 0777, true);
        is_dir($cfgs['htm_path']) || mkdir($cfgs['htm_path'], 0777, true);
        $this->set_cfgs($cfgs);
    }

    public function set_cfgs($cfgs){
        if(isset($cfgs['tpl_path'])){
            $cfgs['tpl_path'] = str_replace('\\', '/', $cfgs['tpl_path']);
            substr($cfgs['tpl_path'], -1) == '/' || $cfgs['tpl_path'] .= '/';
        }
        if(isset($cfgs['htm_path'])){
            $cfgs['htm_path'] = str_replace('\\', '/', $cfgs['htm_path']);
            substr($cfgs['htm_path'], -1) == '/' || $cfgs['htm_path'] .= '/';
        }
        $this->cfgs = array_merge($this->cfgs, $cfgs);
    }

    public function get_text(){
        if(isset($this->cfgs['text'])){
            return $this->cfgs['text'];
        }
        $text = $this->_get_content($this->cfgs['txt_file']);
        $this->cfgs['text'] = $text;
        return $text;
    }

    public function get_titles(){
        if(isset($this->cfgs['titles'])){
            return $this->cfgs['titles'];
        }
        $content = $this->get_text();
        preg_match_all($this->cfgs['pattern'], $content, $matched);
        $titles = $matched[0];
        $titles = array_map('trim', $titles);
        array_unshift($titles, '简介');
        array_unshift($titles, '目录');
        $this->cfgs['titles'] = $titles;
        return $titles;
    }

    public function get_pages(){
        if(isset($this->cfgs['pages'])){
            return $this->cfgs['pages'];
        }
        $content = $this->get_text();
        $pages   = preg_split($this->cfgs['pattern'], $content);
        $pages   = array_map('trim', $pages);
        $this->cfgs['pages'] = $pages;
        return $pages;
    }

    public function create_nav(){
        $this->_copy_folder($this->cfgs['tpl_path'], $this->cfgs['htm_path']);

        $tpl_file = $this->cfgs['tpl_path'].'page.htm';
        $nav_file = $this->cfgs['htm_path'].'0.htm';
        $tpl_str  = $this->_get_content($tpl_file);

        $titles = $this->get_titles();
        $pages  = $this->get_pages();
        $navs   = '';
        $number = 0;
        $cells  = 0;
        foreach($titles as $key => $title){
            if($key == 0){//nav itself
                continue;
            }
            if($this->_is_empty($pages[$key-1])){
                if($cells % 2){
                    $navs .= '<a href="javascript:void(0);">&nbsp;</a>';
                    $cells++;
                }
                $navs .= '<span>'.$title.'</span>';
                $cells = $cells + 2;
            } else {
                $number++;
                $navs .= '<a href="'.$number.'.htm">'.$title.'</a>';
                $cells++;
            }
        }

        $this->_create_js($home_num = 0, $min_prev = 0, $max_next = $number);

        if($cells % 2){
            $navs .= '<a>&nbsp;</a>';
        }
        $tpl_str = str_replace('{title}', '目录', $tpl_str);
        $tpl_str = str_replace('{content}', '<div id="nav">'.$navs.'</div>', $tpl_str);
        return $this->_put_content($nav_file, $tpl_str);
    }

    public function create_pages(){
        $tpl_str= $this->_get_page_tpl();
        $titles = $this->get_titles();
        $pages  = $this->get_pages();
        $number = 0;
        foreach($titles as $key => $title){
            if($key == 0 || $this->_is_empty($pages[$key-1])){
                continue;
            } else {
                $page_str = str_replace('{title}', $title, $tpl_str);
                $page_str = str_replace('{content}', '<p>'.preg_replace('/\r\n/is','</p><p>',$pages[$key-1]).'</p>', $page_str);
                $number++;
                $this->_put_content($this->cfgs['htm_path'].$number.'.htm', $page_str);
            }
        }
        return $number;
    }

    public function create_hhc(){
        $navs = $this->_get_content($this->cfgs['htm_path'].'0.htm');
        preg_match_all('/<a\s+href="(\d+\.htm)"[^>]*>(.*?)<\/a>|<span>(.*?)<\/span>/is', $navs, $matcheds);
        $locals  = $matcheds[1];
        $names   = $matcheds[2];
        $classes = $matcheds[3];

        $tpl_unit= '<LI> <OBJECT type="text/sitemap"><param name="Name" value="{name}"><param name="Local" value="{local}"><param name="ImageNumber" value="{icon}"></OBJECT>';
        $hhc_str = '';
        $closed  = '';
        foreach($locals as $k => $local){
            if(empty($local)){
                $unit   = $closed.str_replace('{local}', $locals[$k+1], $tpl_unit).'<ul>';
                $unit   = str_replace('{name}', $classes[$k], $unit);
                $unit   = str_replace('{icon}', '1', $unit);
                $closed = '</ul>';
            } else {
                $unit   = str_replace('{local}', $local, $tpl_unit);
                $unit   = str_replace('{name}', $names[$k], $unit);
                $unit   = str_replace('{icon}', '11', $unit);
            }
            $hhc_str .= $unit;
        }
        $hhc_str .= $closed;

        $tpl_str  = $this->_get_content($this->cfgs['tpl_path'].'chm.hhc');
        return $this->_put_content($this->cfgs['htm_path'].'chm.hhc', str_replace('{content}', $hhc_str, $tpl_str));
    }


    public function create_hhk(){
        $navs = $this->_get_content($this->cfgs['htm_path'].'0.htm');
        preg_match_all('/<a\s+href="(\d+\.htm)"[^>]*>(.*?)<\/a>|<span>(.*?)<\/span>/is', $navs, $matcheds);
        $locals  = $matcheds[1];
        $names   = $matcheds[2];
        $classes = $matcheds[3];

        $tpl_unit= '<LI> <OBJECT type="text/sitemap"><param name="Name" value="{name}"><param name="Local" value="{local}"></OBJECT>';
        $hhk_str = '';
        foreach($locals as $k => $local){
            if(empty($local)){
                $unit = str_replace('{local}', $locals[$k+1], $tpl_unit);
                $unit = str_replace('{name}', $classes[$k], $unit);
            } else {
                $unit = str_replace('{local}', $local, $tpl_unit);
                $unit = str_replace('{name}', $names[$k], $unit);
            }
            $hhk_str .= $unit;
        }

        $tpl_str  = $this->_get_content($this->cfgs['tpl_path'].'chm.hhk');
        return $this->_put_content($this->cfgs['htm_path'].'chm.hhk', str_replace('{content}', $hhk_str, $tpl_str));
    }

    public function create_hhp(){
        $tpl_str   = $this->_get_content($this->cfgs['tpl_path'].'chm.hhp');
        if(empty($this->cfgs['chm_title'])){
            $this->cfgs['chm_title'] = substr(basename($this->cfgs['txt_file']), 0, -4);
        }
        $chm_title = $this->cfgs['chm_title'];
        return $this->_put_content($this->cfgs['htm_path'].'chm.hhp', str_replace('{title}', $chm_title, $tpl_str));
    }
    
    public function create_chm(){
        $this->create_nav();
        $this->create_pages();
        $this->create_hhc();
        $this->create_hhk();
        $this->create_hhp();
    }

    private function _is_empty($data){
        $data = trim($data);
        $data = preg_replace('/[\x{3000}\s\r\n]+/uis', '', $data);
        if(strlen($data) == 0){
            return true;
        }
        return false;
    }

    private function _get_content($file){
        if(!is_file($file)){
            exit('Failed to open file:'.$file);
        }
        $content  = file_get_contents($file);
        $encoding = mb_detect_encoding($content, "UTF-8,CP936");
        if($encoding != 'UTF-8'){
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        return $content;
    }

    private function _put_content($file, $content){
        $dir = dirname($file);
        is_dir($dir) || mkdir($dir, 0777, true);
        $encoding = mb_detect_encoding($content, "UTF-8,CP936");
        if($encoding == 'UTF-8'){
            $content = mb_convert_encoding($content, 'GBK', 'UTF-8');
        }
        return file_put_contents($file, $content);
    }

    private function _create_js($home_num, $min_prev, $max_next){
        $tpl_file = $this->cfgs['tpl_path'].'page.htm';
        $tpl_str  = $this->_get_content($tpl_file);
        preg_match_all('/<!--javascript-->(.*?)<!--javascript-->/is', $tpl_str, $matcheds);
        foreach($matcheds[1] as $k => $html){
            $html = preg_replace('/>[\s\n\r]+</is', '><', $html);
            $html = "document.write('".trim(str_replace("'", "\\'", $html))."');";
            $this->_put_content($this->cfgs['htm_path'].'j/auto_'.$k.'.js', $html);
        }
        
        $script = 'var home_num='.$home_num.', min_prev='.$min_prev.', max_next='.$max_next.';';
        $this->_put_content($this->cfgs['htm_path'].'j/nav.js', $script);
    }

    private function _get_page_tpl(){
        $tpl_file = $this->cfgs['tpl_path'].'page.htm';
        $tpl_str  = $this->_get_content($tpl_file);
        preg_match_all('/<!--javascript-->(.*?)<!--javascript-->/is', $tpl_str, $matcheds);
        foreach($matcheds[0] as $k => $html){
            $tpl_str = str_replace($html, '<script src="j/auto_'.$k.'.js" type="text/javascript"></script>', $tpl_str);
        }
        return preg_replace('/<!--noscript-->.*?<!--noscript-->/is', '', $tpl_str);
    }

    private function _copy_folder($form, $to, $root = true){
        $form = str_replace('\\', '/', $form);
        substr($form, -1) == '/' || $form .= '/';

        $to = str_replace('\\', '/', $to);
        substr($to, -1) == '/' || $to .= '/';
        is_dir($to) || mkdir($to, 0777, true);

        $dh = opendir($form);
        if(!$dh) return false;
        while (($file = readdir($dh)) !== false) {
            if($file == '.' || $file == '..') continue;

            if (is_dir($form.$file)) {
                $this->_copy_folder($form.$file, $to.$file, false);//set $root param false
            } else {
                if(!$root){
                    copy($form.$file, $to.$file);
                }
            }
        }
        closedir($dh);
        return true;
    }
}

header("Content-type:text/html;charset=utf-8;");

$base = 'f:/data';
$parser = new Txt(array(
    'txt_file' => $base.'/txt/justatest.txt',
    'pattern'  => '/\n第[^\x{3000}\n]+\n/uis',
    'tpl_path' => $base.'/tpl/007/',
    'htm_path' => $base.'/htm/justatest/',
    'chm_title'=> 'just a test'
));


$parser->create_chm();
