<?php
require_once str_replace('\\', '/', dirname(realpath(__FILE__))).'/inc/config.php';
require_once INC_PATH.'/storage.php';
$lib = isset($_GET['lib']) && preg_match('/^[a-z0-9]+$/i', $_GET['lib'])? $_GET['lib']: '';
$lib_info = array();
if(!empty($lib)){
    $storage  = new Storage(LIBS_PATH.'/'.$lib);
    $lib_info = $storage->read('info');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Validation code recognition</title>
    <style>
        body{margin:0;}
        a{text-decoration:none;color:#21aeba;}
        #container{box-sizing:border-box;width:80%;padding:20px;margin:0 auto;}
        label{padding-right:10px;}
        .generate>div{text-align:center;width:100%;padding:5px 0;}
        .generate>div>input{width:300px;}
        #btn_generate{width:80px;margin:20px 0;}
        .lib{width:600px;margin:0 auto;height:50px;line-height:50px;text-align:center;border:1px solid #ccc;}
        .lib>span{display:inline-block;width:200px;text-align:left;}
        .lib>span.btn{display:inline-block;width:auto;color:#21aeba;cursor:pointer;}
        .sampling{width:200px;height:30px;padding:8px 0;float:left;border-bottom:1px solid #ccc;}
        .sampling>input{width:80px;margin-left:5px;}
        .clear{clear:both;}
    </style>
</head>
<body>
<div id="container">
    <form class="generate">
        <div><label>验证码地址:</label>
            <input type="text" name="url" value="<?php echo isset($lib_info['url'])?$lib_info['url']:''; ?>" />
        </div>
        <div><label>新增收集数:</label>
            <input type="text" name="cnt" value="1000" />
        </div>
        <div><label>本地库名称:</label>
            <input type="text" name="lib" value="<?php echo isset($lib_info['lib'])?$lib_info['lib']:''; ?>" />
        </div>
        <div><input id="btn_generate" type="button" value="Collect" /></div>
    </form>
<?php 
if(empty($lib) || !is_dir(LIBS_PATH.'/'.$lib)){
    foreach(glob(LIBS_PATH.'/*') as $_file){
        if(!is_dir($_file)) continue;
        $_lib = basename($_file);
        echo '<div class="lib">
                <span>',$_lib,'</span>
                <a href="?lib=',$_lib,'">sampling</a>
                <span class="btn learning" data-lib="',$_lib,'">learning</span>
                <span class="btn cracking" data-lib="',$_lib,'">cracking</span>
              </div>';
    }
} else {
    $maps = $storage->read('maps');
    foreach(glob(LIBS_PATH.'/'.$lib.'/*.*') as $_file){
        $_img = basename($_file);
        echo '<div class="sampling">
                  <img align="absmiddle" src="./library/',$lib,'/',$_img,'" />
                  <input type="hidden" name="img" value="',$lib,'/',$_img,'" />
                  <input type="text" name="code" value="'.(isset($maps[$_img])? $maps[$_img]: '').'" maxlength="4" />
              </div>';
    }
}
?>
    <div class="clear"></div>
</div>
<script src="./js/jquery-3.1.1.min.js"></script>
<script>
$(function(){
    $('#btn_generate').length && $('#btn_generate').on('click', function(){
        var $this = $(this);
            url   = $('.generate').find('input[name="url"]').val(),
            cnt   = $('.generate').find('input[name="cnt"]').val(),
            lib   = $('.generate').find('input[name="lib"]').val();
        $.post('./ajax.php', {act:'generate', url:url, cnt:cnt, lib:lib}, function(res){
            if(res.code){
                alert(res.msg);
            } else {
                alert(res.msg);
            }
        },'json');
    });

    $('.sampling').length && $('.sampling').find('input[type="text"]').on('keyup', function(evt){
        var $this = $(this),
            img   = $this.prev().val(),
            code  = $this.val().replace('*', '');
        if(evt.keyCode == 13 && (evt.ctrlKey || code.length == 4)) {
            var act = evt.ctrlKey? 'recognizing': 'sampling';
            $.post('./ajax.php', {act:act, img:img, code:code}, function(res){
                if(res.code){
                    alert(res.msg);
                } else {
                    if(evt.ctrlKey){
                        $this.val(res.obj.code);
                    } else {
                        $this.parent().next().find('input[type="text"]').focus();
                    }
                }
            },'json');
        }
    });

    $('.learning').length && $('.learning').on('click', function(){
        var $this = $(this), lib = $this.attr('data-lib');
        $.post('./ajax.php', {act:'learning', lib:lib}, function(res){
            if(res.code){
                alert(res.msg);
            } else {
                alert('Done');
            }
        },'json');
    });

    $('.cracking').length && $('.cracking').on('click', function(){
        var $this = $(this), lib = $this.attr('data-lib');
        $.post('./ajax.php', {act:'cracking', lib:lib}, function(res){
            if(res.code){
                alert(res.msg);
            } else {
                alert(res.obj.code);
            }
        },'json');
    });
});
</script>
</body>
</html>