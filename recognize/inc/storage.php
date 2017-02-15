<?php
require_once str_replace('\\', '/', dirname(realpath(__FILE__))).'/function.php';
class Storage{
    private $_libpath = '';
    public function __construct($libpath){
        $this->connect($libpath);
    }

    public function connect($libpath){
        if(!is_dir($libpath)){
            echo_json(array('code' => 101, 'msg'  => '存储路径不存在', 'obj'  => array()));
        }
        $this->_libpath = $libpath;
    }

    public function save($filename, $data){
        $content = serialize($data);
        if(!$fp=fopen($this->_libpath.'/'.$filename, 'w')){
            echo_json(array('code' => 101, 'msg'  => '创建存储文件失败', 'obj'  => array()));
        }

        if(!flock($fp, LOCK_EX)){
            echo_json(array('code' => 101, 'msg'  => '锁定存储文件失败', 'obj'  => array()));
        }

        if(!fwrite($fp, $content)){
            echo_json(array('code' => 101, 'msg'  => '写入存储文件失败', 'obj'  => array()));
        }
        flock($fp, LOCK_UN);//release the lock
        fclose($fp);
        return true;
    }

    public function read($filename){
        if(!is_file($this->_libpath.'/'.$filename)){
            return array();
        }
        if(!$content = file_get_contents($this->_libpath.'/'.$filename)){
            return array();
        }
        return unserialize($content);
    }
}
