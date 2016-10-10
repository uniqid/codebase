<?php 
class Zip 
{    
    public $zipObj  = null;
    public $tmpDir  = "";
    public $zipBasePath   = "";
    public $unZipBasePath = "";
    public $unZipTopDir   =  "";
    public $ignored  = array();
    public $delFiles = array(); 
    public $delPaths = array(); 
    
    public function __construct($tmpDir = "") {
        $this->zipObj = new ZipArchive();
        if(is_dir($tmpDir)){
            $this->tmpDir = $tmpDir;
        }
    }

    /**
    * Create a zip file
    * 
    * @param $folder example: /var/tmp/folder
    * @param $file   example: /var/tmp/zipfile.zip
    * @param $ignored
    * @return bool
    */
    public function addFoldersToZip($folders, $zipFile, $recursive = true, $ignored = null) {
        $flags = is_file($zipFile)? ZIPARCHIVE::CHECKCONS: ZIPARCHIVE::CREATE;
        if (true !== $this->zipObj->open($zipFile, $flags)) {
            return false;
        }
        else{
            $this->delFiles[] = $zipFile;
        }
        $ignored && $this->ignored = (array) $ignored;
        
        foreach((array)$folders as $folder){
            $folder = str_replace("\\", "/", $folder);
            $folder = substr($folder, -1) == '/'? substr($folder, 0, -1): $folder;
            if(strstr($folder, '/')){
                $this->zipBasePath = substr($folder, 0, strrpos($folder, '/')+1);
                $folder = substr($folder, strrpos($folder, '/')+1);
            }
            if(!$this->_addFolderToZip($folder, $recursive)){
                return false;
            }
        }
        $this->zipObj->close();
        return true;
    }

    private function _addFolderToZip($folder, $recursive = true, $parent = "") {
        $zipPath  = $parent . $folder;
        $fullPath = $this->zipBasePath . $zipPath;
        if(true !== $this->zipObj->addEmptyDir($zipPath)){
            return false;
        }

        if($recursive){
            $dir = new DirectoryIterator($fullPath);
            foreach($dir as $file) {
                if($file->isDot()){
                    continue;
                }

                $filename = $file->getFilename();
                if(!in_array($filename, $this->ignored)){
                    if($file->isDir()) {
                        $this->_addFolderToZip($filename, true, $zipPath . '/');
                    } 
                    else {
                        return $this->zipObj->addFile($fullPath .'/'. $filename, $zipPath .'/'. $filename);
                    }
                }
            }
        }
        return true;
    }

    public function addFilesToZip($files, $zipFile) {
        $flags = is_file($zipFile)? ZIPARCHIVE::CHECKCONS: ZIPARCHIVE::CREATE;
        if (true !== $this->zipObj->open($zipFile, $flags)) {
            return false;
        }
        foreach($files as $file => $newFile){
            if(!$this->zipObj->addFile($file, $newFile)){
                $this->zipObj->close();
                return false;
            }
        }
        $this->zipObj->close();
        return true;
    }

    public function unZipFile($zipFile, $folder = null) {
        $zip = zip_open($zipFile);
        if(is_resource($zip)){
            if(!$this->unZipBasePath = ($folder? $folder: $this->_mkTmpDir())){
                return false;
            }
            while($zip_entry = zip_read($zip)){
                $entryName = str_replace("\\", "/", zip_entry_name($zip_entry));
                if(!$this->unZipTopDir){
                    $this->unZipTopDir = $this->unZipBasePath ."/". $entryName;
                    if(!is_dir($this->unZipTopDir) && !mkdir($this->unZipTopDir, 0777, true)){
                        return false;
                    }
                }
                if(substr($entryName, -1) === '/'){
                    continue;
                }
                $fullpath = $this->unZipBasePath ."/". $entryName;
                if(!is_dir($fullpath) && !mkdir($fullpath, 0777, true)){
                    return false;
                }
                $fp = fopen($fullpath, "w");
                if (zip_entry_open($zip, $zip_entry, "r")) {
                    $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    fwrite($fp, "$buf");
                    zip_entry_close($zip_entry);
                    fclose($fp);
                }
            }
            zip_close($zip);
            return true;
        }
        else{
            return false;
        }
    }

    private function _mkTmpDir()
    {
        $tmpFolder = $this->tmpDir . md5(session_id() . microtime() . rand());
        if(mkdir($tmpFolder)){
            $this->delPaths[] = $tmpFolder;
            return $tmpFolder;
        }
        else{
            return false;
        }
    }

    private function _delDir($dirName) {
		if(!is_dir($dirName)) {
			return false;
		}
		$handle = opendir($dirName);
		while(false !== ($file = readdir($handle))){
			if($file != '.' && $file != '..'){
				$dir = $dirName ."/". $file;
				is_dir($dir)? $this->_delDir($dir): unlink($dir);
			}
		}
		closedir($handle);
		return rmdir($dirName)? true: false;
    }
    
    public function __destruct() {
        if(!empty($this->delFiles)){
            foreach($this->delFiles as $delFile){
                is_file($delFile) && unlink($delFile);
            }
        }

        if(!empty($this->delPaths)){
            foreach($this->delPaths as $delPath){
                is_dir($delPath) && $this->_delDir($delPath);
            }
        }
    }
}
?>