<?php
require_once str_replace('\\', '/', dirname(realpath(__FILE__))).'/inc/config.php';
require_once ROOT_PATH.'/inc/function.php';
require_once ROOT_PATH.'/inc/storage.php';
require_once ROOT_PATH.'/inc/robot.php';

$act = isset($_POST['act'])? $_POST['act']: '';
if(!in_array($act, array('generate', 'sampling', 'learning', 'recognizing', 'cracking'))){
    echo_json(array('code' => 101, 'msg' => '参数错误', 'obj' => array()));
}

if($act == 'generate'){
    $url = isset($_POST['url'])? $_POST['url']: '';
    $cnt = isset($_POST['cnt'])? $_POST['cnt']: '';
    $lib = isset($_POST['lib'])? $_POST['lib']: '';
    if(!preg_match('/^http/is', $url) || !preg_match('/^[1-9][0-9]*$/', $cnt) || !preg_match('/^[a-z0-9]+$/i', $lib)){
        echo_json(array('code' => 101, 'msg' => '参数错误', 'obj' => array()));
    }
    $storage = new Storage(LIBS_PATH.'/'.$lib);
    $storage->save('info', array('url'=>$url, 'lib'=>$lib));

    $libpath = LIBS_PATH.'/'.$lib;
    is_dir($libpath) || mkdir($libpath, 0777);
    $base = $success = $failure = 0;
    for($i=1; $i<=$cnt; $i++){
        $img = $libpath.'/'.($base+$i).'.jpg';
        while(is_file($img)){
            $base++;
            $img = $libpath.'/'.($base+$i).'.jpg';
        }
        if($content = @file_get_contents($url)){
            file_put_contents($img, $content);
            $success++;
        } else {
            $failure++;
        }
    }
    echo_json(array('code' => 0, 'msg' => '已完成，成功：'.$success.'，失败：'.$failure.'。', 'obj' => array()));
} else if($act == 'sampling'){
    $storage = new Storage(dirname(LIBS_PATH.'/'.$_POST['img']));
    $data = $storage->read('maps');
    $img = basename($_POST['img']);
    $robot = new Robot();
    $robot->learn($storage, dirname(LIBS_PATH.'/'.$_POST['img']), array('border' => 2), array($img => $_POST['code']));
    if(isset($data[$img]) && $data[$img] == $_POST['code']){
        echo_json(array('code' => 0, 'msg' => '', 'obj' => array()));
    } else {
        $data[$img] = $_POST['code'];
        $storage->save('maps', $data);
    }
    echo_json(array('code' => 0, 'msg' => '', 'obj' => array()));
} else if($act == 'learning'){
    $lib = isset($_POST['lib'])? $_POST['lib']: '';
    if(!preg_match('/^[a-z0-9]+$/i', $lib)){
        echo_json(array('code' => 101, 'msg' => '参数错误', 'obj' => array()));
    }
    require_once ROOT_PATH.'/inc/robot.php';
    $storage = new Storage(LIBS_PATH.'/'.$lib);
    $robot   = new Robot();
    $robot->learn($storage, LIBS_PATH.'/'.$lib, array('border' => 2));
    echo_json(array('code' => 0, 'msg' => '', 'obj' => array()));
} else if($act == 'recognizing'){
    require_once ROOT_PATH.'/inc/robot.php';
    $storage = new Storage(dirname(LIBS_PATH.'/'.$_POST['img']));
    $robot   = new Robot();
    $code = $robot->recognize($storage, LIBS_PATH.'/'.$_POST['img'], array('border' => 2));
    echo_json(array('code' => 0, 'msg' => '', 'obj' => array('code' => $code)));
} else if($act == 'cracking'){
    $lib = isset($_POST['lib'])? $_POST['lib']: '';
    if(!preg_match('/^[a-z0-9]+$/i', $lib)){
        echo_json(array('code' => 101, 'msg' => '参数错误', 'obj' => array()));
    }
    require_once ROOT_PATH.'/inc/robot.php';
    $storage = new Storage(LIBS_PATH.'/'.$lib);
    $robot   = new Robot();
    $info    = $storage->read('info');
    do{
        do{
            $content = @file_get_contents($info['url'].'?t='.time());
        } while(empty($content));
        file_put_contents(LIBS_PATH.'/'.$lib.'_cracking.jpg', $content);
        $code = $robot->recognize($storage, LIBS_PATH.'/'.$lib.'_cracking.jpg', array('border' => 2));
    } while(strpos($code, '*') !== false);
    echo_json(array('code' => 0, 'msg' => '', 'obj' => array('code' => $code)));
}

