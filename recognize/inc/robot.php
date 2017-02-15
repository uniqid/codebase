<?php
require_once str_replace('\\', '/', dirname(realpath(__FILE__))).'/config.php';
require_once ROOT_PATH.'/inc/function.php';
require_once ROOT_PATH.'/inc/storage.php';
class Robot{
    private $memories = array();
    private $configs  = array('border' => 0, 'minW' => 6, 'maxW' => 10, 'red' => 150, 'green' => 150, 'blue' => 150);
    public function __construct(){
        
    }

    public function learn($storage, $path, $configs = array(), $data = array()){
        foreach($configs as $key => $cfg){
            $this->configs[$key] = $cfg;
        }
        if(empty($data)){
            $data = $storage->read('maps');
            $this->memories = array();
        } else {
            $this->memories = $storage->read('memories');
        }
        foreach($data as $img => $code){
            $file = $path.'/'.$img;
            if(!is_file($file)){
                continue;
            }
            $codes = array_splice(preg_split('//', $code), 1, -1);
            $binaryCode = $this->getBinaryCode($file);
            $features   = $this->getFeatures($binaryCode, count($codes));
            //$this->echoBinaryCodeAndCodes($binaryCode);
            //$this->echoFeatruesAndCodes($features, $codes);
            if(count($features) != count($codes)){
                continue;
            }

            //convert to string
            $maps = array();
            foreach($features as $key => $feature){
                $maps[$key] = "";
                foreach($feature as $value){
                    $maps[$key] .= implode("", $value);
                }
            }
            //print_r($maps);exit;

            //Set the multistage matching mode
            foreach($codes as $key => $code){
                if(!isset($this->memories[0][$code])){
                    $this->memories[0][$code] = $maps[$key];
                    continue;
                }
                $i = 0;
                do{
                    $percent = 0.0;
                    similar_text($this->memories[$i][$code], $maps[$key], $percent);
                    $i++;
                    if($percent >= 96){
                        break;
                    }
                }while(isset($this->memories[$i][$code]));
                $percent >= 96 || $this->memories[$i][$code] = $maps[$key];
            }
        }
        $storage->save('memories', $this->memories);
        return true;
    }

    public function recognize($storage, $file, $configs = array()){
        foreach($configs as $key => $cfg){
            $this->configs[$key] = $cfg;
        }
        $binaryCode = $this->getBinaryCode($file);
        $features   = $this->getFeatures($binaryCode, 4);
        //$this->echoBinaryCodeAndCodes($binaryCode);
        //$this->echoFeatruesAndCodes($features);

        //convert to string
        $maps = array();
        foreach($features as $key => $feature){
            $maps[$key] = "";
            foreach($feature as $value){
                $maps[$key] .= implode("", $value);
            }
        }
        //print_r($maps);exit;

        //get matching mode
        $img_code = '';
        $this->memories = $storage->read('memories');
        foreach($maps as $str){
            $matched_val  = 0;
            $matched_code = '';
            foreach($this->memories as $memories){
                foreach($memories as $code => $memory_str){
                    similar_text($str, $memory_str, $percent);
                    if($matched_val < $percent){
                        $matched_val  = $percent;
                        $matched_code = $code;
                    }
                }
                if($matched_val >= 96){break;}
            }
            $img_code .= $matched_val>=96? $matched_code: '*';
        }
        return $img_code;
    }

    public function getBinaryCode($file){
        $border = $this->configs['border'];
        $red    = $this->configs['red'];
        $green  = $this->configs['green'];
        $blue   = $this->configs['blue'];
        list($col, $row) = getimagesize($file);
        $res  = imagecreatefromjpeg($file);

        $colors = array();
        for($i=0; $i < $row; ++$i){
            for($j=0; $j < $col; ++$j){
                $rgba = imagecolorsforindex($res, imagecolorat($res, $j, $i));
                $colors[$i][$j] = $rgba['red'] * 256 * 256 + $rgba['green'] * 256 + $rgba['blue'];
            }
        }
        
        /*
        $filter_colors = array();
        foreach($colors as $i => $color){
            if($i == 0 || $i == $row - 1){
                continue;
            }
            foreach($color as $j => $val){
                if($j == 0 || $j == $col - 1){
                    continue;
                }
                $filter_colors[$i][$j] = intval(($colors[$i][$j-1] + $colors[$i][$j+1] + $colors[$i-1][$j] + $colors[$i+1][$j] + $colors[$i-1][$j-1] + $colors[$i+1][$j-1] + $colors[$i+1][$j-1] + $colors[$i+1][$j+1])/8);
            }
        }
        */

        $data = array();
        for($i=$border; $i < $row - $border; ++$i){
            for($j=$border; $j < $col - $border; ++$j){
                $rgba = imagecolorsforindex($res, imagecolorat($res, $j, $i));
                if($rgba['red'] < $red && ($rgba['green'] < $green || $rgba['blue'] < $blue)){
                    $data[$i][$j]=1;
                } else {
                    $data[$i][$j]=0;
                }
            }
        }

        for($i=$border; $i < $row - $border; ++$i){//erase isolated point & strikethrough line
            $sum = array_sum($data[$i]);
            $is_strikethrough = ($sum >= count($data[$i]) - $border);
            for($j=$border; $j < $col - $border; ++$j){
                if($is_strikethrough){
                    $data[$i][$j] = isset($data[$i-1][$j])? $data[$i-1][$j]: $data[$i+1][$j];
                    continue;
                }
                $num = 0;
                if($data[$i][$j] == 0){
                    continue;
                }
                isset($data[$i-1][$j]) && $num += $data[$i-1][$j]; //top
                isset($data[$i+1][$j]) && $num += $data[$i+1][$j]; //bottom
                isset($data[$i][$j-1]) && $num += $data[$i][$j-1]; //left
                isset($data[$i][$j+1]) && $num += $data[$i][$j+1]; //right
                isset($data[$i-1][$j-1]) && $num += $data[$i-1][$j-1]; //top left
                isset($data[$i-1][$j+1]) && $num += $data[$i-1][$j+1]; //top right
                isset($data[$i+1][$j-1]) && $num += $data[$i+1][$j-1]; //bottom left
                isset($data[$i+1][$j+1]) && $num += $data[$i+1][$j+1]; //bottom right
                $num == 0 && $data[$i][$j] = 0;
            }
        }
        return $data;
    }

    public function getFeatures($binaryCode, $code_num){
        $border = $this->configs['border'];
        $minW   = $this->configs['minW'];
        $maxW   = $this->configs['maxW'];
        $row    = count($binaryCode);
        $col    = $row? count($binaryCode[$border]): 0;
        $block_arr = array();
        $block_num = 0;
        $block_key = 0;
        for($i=$border; $i<$col + $border; ++$i){
            $is_valid = false;
            for($j=$border; $j<$row + $border; ++$j){
                if($binaryCode[$j][$i] == 1){
                    $is_valid = true;
                    if($block_key >= $maxW && $code_num - 1 > $block_num){
                        $block_key = 0;
                        $block_num++;
                    } else {
                        $block_key++;
                    }
                    break;
                }
            }
            if($is_valid == true){
                for($j = $border; $j < $row + $border; ++$j){
                    $block_arr[$block_num][$j][$block_key-1] = $binaryCode[$j][$i];
                }
            } else if($block_key > 0 && count($block_arr[$block_num][$border]) >= $minW && $code_num - 1 > $block_num){
                $block_key = 0;
                $block_num++;
            }
        }

        for($num = 0; $num < count($block_arr); ++$num){
            for($i=$border; $i < $row + $border; ++$i){
                $sum = array_sum($block_arr[$num][$i]);
                if($sum == 0 || $sum == count($block_arr[$num][$i])){
                    unset($block_arr[$num][$i]);
                }
            }
        }
        return array_values($block_arr);
    }

    public function echoBinaryCodeAndCodes($binaryCode, $codes = array()){
        foreach($binaryCode as $val){
            echo implode('', $val), "\n";
        }
        echo "\n";
        empty($codes) || print_r($codes);
    }

    public function echoFeatruesAndCodes($features, $codes = array()){
        foreach($features as $feature){
            foreach($feature as $val){
                echo implode('', $val), "\n";
            }
            echo "\n";
        }
        empty($codes) || print_r($codes);
    }

    public function __destruct(){
    
    }
}
