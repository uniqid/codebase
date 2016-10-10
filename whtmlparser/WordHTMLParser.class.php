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
Class WordHTMLParser{
    private $_shtml   = null;
    private $_dhtml   = null;
    private $_html    = '';
    private $_tpl     = '';
    private $_charset = 'utf-8';
    private $_data    = '';

    /**
      *$shtml source word html file path
      *$dhtml destination word html file path
    */
    public function __construct($shtml, $dhtml, $charset = 'utf-8'){
        is_dir($shtml) || exit('Path does not exist:'.$shtml);
        is_dir($dhtml) || exit('Path does not exist:'.$dhtml);
        $tpl = dirname(__FILE__).'/tpl.html';
        is_file($tpl) || exit('Template file does not exist:'.$tpl);

        $hhc = dirname(__FILE__).'/tpl.hhc';
        is_file($hhc) || exit('Template file does not exist:'.$hhc);

        $hhk = dirname(__FILE__).'/tpl.hhk';
        is_file($hhk) || exit('Template file does not exist:'.$hhk);

        $this->_shtml   = $shtml;
        $this->_dhtml   = $dhtml;
        $this->_tpl     = $tpl;
        $this->_hhc     = $hhc;
        $this->_hhk     = $hhk;
        $this->_charset = strtolower($charset);
    }

    /**
      *creat all html files
    */
    public function create_htmls($i=-1){
        $sfile = $this->_get_sort_files($this->_shtml, true); //print_r($sfile);exit;
        $j = 0;
        foreach($sfile as $key => $files){
            $file = $files['name'];
            if($i !== -1){
                $j++;
                if($j != $i){continue;}
            }
            $files = $this->_create_html($file, $key); //print_r($files);exit;
        }
    }


    /**
      *replace prev & next link
    */
    public function replace_link(){
        $dfile = $this->_get_sort_files($this->_dhtml, true); //print_r($dfile);exit;
		$links = array();
		foreach($dfile as $key => $file){
			if(isset($file['name'])){
                $links[] = $file['name'];
            } else {
                foreach($file['children'] as $child){
					$links[] = $child;
                }
            }
		}

		foreach($links as $key => $link){
			$prev = isset($links[$key - 1])? $links[$key - 1]: '';
			$next = isset($links[$key + 1])? $links[$key + 1]: '';
			$content = file_get_contents($this->_dhtml.$link);
			if($prev == ''){
				$content = preg_replace("/<a[^>]*href='[^']*prev[^']*'>[^<]*<\/a>/is", '', $content);
			} else {
				$content = str_replace('{__prev__}', $prev, $content);
			}

			if($next == ''){
				$content = preg_replace("/<a[^>]*href='[^']*next[^']*'>[^<]*<\/a>/is", '', $content);
			} else {
				$content = str_replace('{__next__}', $next, $content);
			}
			file_put_contents($this->_dhtml.$link, $content);
		}
    }

    /**
      *creat hhc file
    */
    public function create_hhc(){
        $hhc_cell = <<<EOT
		<LI> <OBJECT type="text/sitemap">
			<param name="Name" value="{__title__}">
			<param name="Local" value="./{__file__}">
			<param name="ImageNumber" value="{__number__}">
			</OBJECT>

EOT;
        $sfile = $this->_get_sort_files($this->_shtml);       //print_r($sfile);exit;
        $dfile = $this->_get_sort_files($this->_dhtml, true); //print_r($dfile);exit;
        $hhc = '';
        foreach($dfile as $key => $file){
            if(isset($file['name'])){
                $title = substr($file['name'], 0, -5);
                $_file = $file['name'];
            } else {
                $title = substr($sfile[$key]['name'], 0, -5);
                $_file = $file['children'][1];
            }
            $hhc .= str_replace(array('{__title__}','{__file__}','{__number__}'), array($title, $_file, 1), $hhc_cell);

            if(isset($file['children'])){//means has children
                $hhc .= "        <UL>\n";
                foreach($file['children'] as $child){
                    $title = substr($child, 0, -5);
                    $hhc .= "\t". str_replace(array('{__title__}','{__file__}','{__number__}'), array($title, $child, 2), $hhc_cell);
                }
                $hhc .= "        </UL>\n";
            }
        }
        $hhc_content = str_replace('{__hhc__}', $hhc, file_get_contents($this->_hhc));
        file_put_contents($this->_dhtml.'fo.hhc', $hhc_content);
    }

    /**
      *creat hhk file
    */
    public function create_hhk(){
        $hhk_tpl = <<<EOT
		<LI> <OBJECT type="text/sitemap">
			<param name="Name" value="{__title__}">
			<param name="Local" value="{__file__}">
			</OBJECT>

EOT;
        $sfile = $this->_get_sort_files($this->_shtml);       //print_r($sfile);exit;
        $dfile = $this->_get_sort_files($this->_dhtml, true); //print_r($dfile);exit;
        $hhk = '';
        foreach($dfile as $key => $file){
            if(isset($file['name'])){
                $title = substr($file['name'], 0, -5);
                $_file = $file['name'];
            } else {
                $title = substr($sfile[$key]['name'], 0, -5);
                $_file = $file['children'][1];
            }
            $hhk .= str_replace(array('{__title__}','{__file__}'), array($title, $_file), $hhk_tpl);

            if(isset($file['children'])){//means has children
                foreach($file['children'] as $child){
                    $title = substr($child, 0, -5);
                    $hhk .= "\t". str_replace(array('{__title__}','{__file__}'), array($title, $child), $hhk_tpl);
                }
            }
        }

        $hhk_content = str_replace('{__hhk__}', $hhk, file_get_contents($this->_hhk));
        file_put_contents($this->_dhtml.'fo.hhk', $hhk_content);
    }

    private function _get_sort_files($path, $include_child = false, $pattern = '*.html'){
        $files = glob($path . $pattern);
        $kfile = array();
        foreach($files as $key => &$file){
            $file = str_replace($path, '', $file);
            preg_match('/^([\d\.\s]+).*?/is', $file, $matched);
            $keystr = str_replace(' ', '', $matched[1]);
            $keys = explode('.', $keystr);
            if(!empty($keys[1])){
                $include_child && $kfile[$keys[0]]['children'][$keys[1]] = $file;
            } else {
                $kfile[$keys[0]]['name'] = $file;
            }
        }

        foreach($kfile as &$files){
            isset($files['children']) && 
            uksort($files['children'], function($i, $j){return $i > $j;});
        }
        uksort($kfile, function($i, $j){return $i > $j;});
        return $kfile;
    }

    /**
      *creat all html files
    */
    private function _create_html($file, $prekey){
        $this->_set_data($file);
        list($list_pres, $lists) = $this->_get_content_list();
        $titles = $this->get_menu_titles(true);
        $this->_clean_data();

        //echo "<pre>"; print_r($list_pres); print_r($lists); echo "</pre>";
        //echo "<pre>"; print_r($titles); echo "</pre>"; return ;exit;

        $filenames = array_values($titles);
        $contents  = array_values($lists);
        if(count($filenames) != count($contents)){
            echo $this->_html, "-- Create Fail.<br/>";
            return false;
        }

        $htmls  = array_combine($filenames, $contents);
        $prekey = count($htmls) > 1? $prekey.".": ''; 
        $files  = array();
        $tpl    = file_get_contents($this->_tpl);
        foreach($htmls as $_title => $_content){
            $_title   = str_replace('?', '？', $_title);
            $_content = str_replace(array('{__title__}', '{__content__}'), array($_title, $_content), $tpl);
            if($this->_charset != 'utf-8'){
                $_title   = mb_convert_encoding($_title,   $this->_charset, 'utf-8');
                $_content = mb_convert_encoding($_content, $this->_charset, 'utf-8');
            }

            //echo $this->_html, '<br/>'; continue; //$this->clean_html($_content);exit;
            $_content = $this->clean_html($_content);
            $_title   = str_replace(array('&#8212;', '&#8212;'), array('',''), $_title);
            if(!file_put_contents($this->_dhtml.$prekey.$_title.'.html', $_content)){
                echo $this->_dhtml.$prekey.$_title.'.html<br/>';
            }
            
            $files[] = $this->_dhtml.$prekey.$_title.'.html';
        }
        return $files;
    }

    /**
      *set the html file content
    */
    private function _set_data($file){
        if(empty($this->_data)){
            $this->_html = $this->_shtml . $file;
            $this->_data = file_get_contents($this->_html);
            if($this->_charset != 'utf-8'){
                $this->_data = mb_convert_encoding($this->_get_data(), 'UTF-8', $this->_charset);
            }
        }
        return true;
    }

    /**
      *get the html file content
    */
    private function _get_data(){
        return $this->_data;
    }

    /**
      *clean the html file content
    */
    private function _clean_data(){
        $this->_data = '';
        return true;
    }

    /**
      *get the parsed content list
    */
    private function _get_content_list(){
        preg_match('/<\/style><\/head><body style[^>]*>(.*<\/span><\/p><\/div>)/is', $this->_get_data(), $matched);
        $content = isset($matched[1])?$matched[1]: $this->_get_data();  //echo $content;exit;

        preg_match_all('/<(?:p class=(?:MsoTitle|MsoNormal)|h1)[^>]*>[<\/span>]?<a\s+name="_Toc\d+"\s*>|<a\s+name="_Toc\d+"\s*><\/a>(<span style=[^>]*>\d+\.)/is', $content, $preArr); //print_r($preArr);exit;

        $datas = preg_split('/<(?:p class=(?:MsoTitle|MsoNormal)|h1)[^>]*>[<\/span>]?<a\s+name="_Toc\d+"\s*>|<a\s+name="_Toc\d+"\s*><\/a><span style=[^>]*>\d+\./is', $content); //print_r($datas);exit;

        $lists = $datas;
        foreach($datas as $key => &$data){
            $key > 0 && ($data = $preArr[1][$key-1] . $data);
            $lists[$key] = $data;
            $data = preg_replace('/<[^>]*>/is', '', $data);
            if(!preg_match('/^\d+.*?/is', $data)){
                unset($datas[$key], $lists[$key]);
                continue;
            }
            $data = mb_substr($data, 0, 5, 'UTF-8');
        }
        if(empty($datas)){
            $lists = array($content);
            $datas = array('***All***');
        }
        return array($datas, $lists);
    }

    /**
      *get the parsed menu title list
    */
    public function get_menu_titles($include_parent = false){
        preg_match_all('/<a[^>]*href=[^>]*>(.*?)<\/a>/is', $this->_get_data(), $matched);
        $menus = array_filter($matched[1]);
        foreach($menus as $key => &$menu){
            $menu = preg_replace('/<[^>]*>/is', '', $menu);
            $menu = trim(str_replace(array('&#9;', '&nbsp;'), array('', ' '), $menu));
            $menu = preg_replace('/^(.*)[\d+]$/is', '$1', $menu);
            $menu = preg_replace('/^(.*)[\d+]$/is', '$1', $menu);
            $menu = preg_replace('/^(.*)[\d+]$/is', '$1', $menu);
            $menu = str_replace(array('&#8220;', '&#8221;'), array('', ''), $menu);
            if(!preg_match('/^\d+.*?/is', $menu)){
                unset($menus[$key]);
            }
        }
        if($include_parent && empty($menus)){
            $title = substr(basename($this->_html),0,-5);
            array_splice($menus, 0, 0, mb_convert_encoding($title, 'UTF-8', $this->_charset));
        }
        return $menus;
    }

    /**
      *Removes all FONT and SPAN tags, and all Class and Style attributes.
      *Designed to get rid of non-standard Microsoft Word HTML tags.
      *start by completely removing all unwanted tags
    */
    public function clean_html($html) {
        return $html;
        $html = ereg_replace("<(/)?(font|span|del|ins)[^>]*>", "", $html);
        // then run another pass over the html (twice), removing unwanted attributes
        $html = ereg_replace("<([^>]*)(class|lang|style|size|face)=(\"[^\"]*\"|'[^']*'|[^>]+)([^>]*)>","<\\1>",$html);
        $html = ereg_replace("<([^>]*)(class|lang|style|size|face)=(\"[^\"]*\"|'[^']*'|[^>]+)([^>]*)>","<\\1>",$html);
        return $html;
    }
}

header("Content-type: text/html; charset=gbk");
$parser = new WordHTMLParser(dirname(__FILE__)."/shtml/", dirname(__FILE__)."/dhtml/", 'gbk');
echo "<pre>";
$parser->create_htmls();
$parser->replace_link();
$parser->create_hhc();
$parser->create_hhk();
echo "</pre>";
exit;
?>