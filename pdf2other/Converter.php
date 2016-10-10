<?php
class Converter{
    public $cfg = array();
    public function __construct($dir_tpl, $dir_target, $encoding = 'gbk'){
        $this->cfg = array(
            'tpl_htm' => $dir_tpl.'/tpl.htm',
            'tpl_hhp' => $dir_tpl.'/tpl.hhp',
            'tpl_hhc' => $dir_tpl.'/tpl.hhc',
            'tpl_hhk' => $dir_tpl.'/tpl.hhk',
            'tpl_tit' => 'µÚ {__name__} Ò³',
            'dir_tpl' => $dir_tpl,
            'dir_htm' => $dir_target.'/htm',
            'dir_png' => $dir_target.'/png',
            'dir_txt' => $dir_target.'/txt',
            'encoding'=> $encoding,
        );

        $this->cfg['hhc_cell'] = <<<EOT
		<LI> <OBJECT type="text/sitemap">
			<param name="Name" value="{__title__}">
			<param name="Local" value="./{__file__}">
			<param name="ImageNumber" value="{__number__}">
			</OBJECT>

EOT;
        $this->cfg['hhk_cell'] = <<<EOT
		<LI> <OBJECT type="text/sitemap">
			<param name="Name" value="{__title__}">
			<param name="Local" value="{__file__}">
			</OBJECT>

EOT;

        is_dir($this->cfg['dir_htm']) || mkdir($this->cfg['dir_htm']);
        is_dir($this->cfg['dir_htm'].'/png') || mkdir($this->cfg['dir_htm'].'/png');
    }

    public function convert(){
        $this->_create_htm();
        $this->_create_css();
        $this->_create_js();
        $this->_create_hhp();
        $this->_create_hhc();
        $this->_create_hhk();
    }

    private function _create_htm(){
        $template = file_get_contents($this->cfg['tpl_htm']);
        $files = $this->_get_sort_files($this->cfg['dir_png']."/*.*");
        $count = count($files);
        foreach($files as $key => $file){//key is filename
            $basename = basename($file);
            $html = $this->_set_png($template, $basename);
            $html = $this->_set_txt($html, $key.'.txt', ($key == $count));

            file_put_contents($this->cfg['dir_htm'].'/'.$key.'.htm', $html);
            copy($file, $this->cfg['dir_htm'].'/png/'.$basename);
            echo $file, "<br/>\n";
        }
    }

    private function _set_png($html, $basename){
        $html = str_replace('{__png__}', '<img src="./png/'.$basename.'" />', $html);
        return $html;
    }

    private function _set_txt($html, $basename, $is_last){
        $txt = file_get_contents($this->cfg['dir_txt'].'/'.$basename);
        if($this->cfg['encoding'] != 'gbk'){
           $txt = iconv($this->cfg['encoding'], 'gbk//ignore', $txt);
        }
        $txt = str_replace("\f", '', "\r\n<p>".trim($txt)."</p>\r\n");
        $txt = preg_replace('/([\.\!\?¡££¡£¿]{1})[\r\n]{1,}/is', "$1 </p>\r\n<p>", $txt);
        $txt = preg_replace('/<\/p>[\r\n]{2,}<p>/is', "</p>\r\n<p>", $txt);
        $txt = str_replace("</p>\r\n<p>¡±", "¡±</p>\r\n<p>", $txt);
        $txt = str_replace("\n\n", "<br/>", $txt);
        $txt = str_replace("<br/><br/>", "</p>\r\n<p>", $txt);
        $txt = str_replace("<p></p>\r\n", "", $txt);
        $txt = str_replace("<p><br/>", "<p>", $txt);

        $html = str_replace('{__txt__}', '<div class="txt hidden">'.$txt.'</div>', $html);
        $html = str_replace('{__title__}', str_replace('{__name__}', substr($basename, 0, -4), $this->cfg['tpl_tit']), $html);
        if($is_last){
            $html = str_replace('{__extra__}', '<div class="hidden"><a id="_next" href="1.htm">next</a></div>', $html);
        } else {
            $html = str_replace('{__extra__}', '', $html);
        }
        return $html;
    }

    private function _create_css($file = 'pdf.css'){
        copy($this->cfg['dir_tpl'].'/'.$file, $this->cfg['dir_htm'].'/'.$file);
    }

    private function _create_js($file = 'pdf.js'){
        copy($this->cfg['dir_tpl'].'/'.$file, $this->cfg['dir_htm'].'/'.$file);
    }

    private function _create_hhp(){
        $files = '';
        foreach($this->_get_sort_files($this->cfg['dir_htm']."/*.htm") as $file){
            $files .= basename($file)."\r\n";
        }
        $hhp = file_get_contents($this->cfg['tpl_hhp']);
        file_put_contents($this->cfg['dir_htm'].'/pdf.hhp', str_replace('{__files__}', $files, $hhp));
    }

    private function _create_hhc(){
        $hhc_cells = '';
        foreach($this->_get_sort_files($this->cfg['dir_htm']."/*.htm") as $key => $file){
            $name  = str_replace('{__name__}', $key, $this->cfg['tpl_tit']);
            $local = basename($file);
            $num   = 11;
            $hhc_cells .= str_replace(array('{__title__}', '{__file__}', '{__number__}'), array($name, $local, $num), $this->cfg['hhc_cell']);
        }
        $hhc = file_get_contents($this->cfg['tpl_hhc']);
        file_put_contents($this->cfg['dir_htm'].'/pdf.hhc', str_replace('{__hhc__}', $hhc_cells, $hhc));
    }

    private function _create_hhk(){
        $hhk_cells = '';
        foreach($this->_get_sort_files($this->cfg['dir_htm']."/*.htm") as $key => $file){
            $name  = str_replace('{__name__}', $key, $this->cfg['tpl_tit']);
            $local = basename($file);
            $hhk_cells .= str_replace(array('{__title__}', '{__file__}'), array($name, $local), $this->cfg['hhk_cell']);
        }
        $hhk = file_get_contents($this->cfg['tpl_hhk']);
        file_put_contents($this->cfg['dir_htm'].'/pdf.hhk', str_replace('{__hhk__}', $hhk_cells, $hhk));
    }

    private function _get_sort_files($pattern){
        $files = glob($pattern);
        $arrs  = array();
        foreach($files as $file){
            $k = substr(basename($file), 0, -4);
            $arrs[$k] = $file;
        }
        ksort($arrs);
        return $arrs;
    }

    public function __destruct(){
        exit('### Done ###');
    }
}
