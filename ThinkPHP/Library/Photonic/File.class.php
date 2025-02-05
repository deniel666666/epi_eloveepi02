<?php
namespace Photonic;
use Think\Controller;

use Photonic\GoogleStorage;

class File extends Controller {
    function _initialize(){
    }

    public function try_get_files($file_paths=[]){
        $files = [];
        foreach ($file_paths as $value) {
            if(gettype($value)=='string'){
                $file = $this->try_get_file($value);
                array_push($files, $file);
            }else{
                array_push($files, $value);
            }
        }
        return $files;
    }
    public function try_get_file($file_path=''){
        $GoogleStorage = new GoogleStorage();
        $file = $GoogleStorage->show_image($file_path);
        return $file;
    }
}
?>