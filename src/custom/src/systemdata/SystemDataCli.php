<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\systemdata;

class SystemDataCli extends SystemData {

    public function checkPath($path) {
        if(!is_dir($path)) {
            // mkdir recursively
            sugar_mkdir($path, null, true);
        }

        // does it have trailing slash?
        if(substr($path, -1) !== '/') {
            $path .= '/';
        }
    
        return $path;
    }

    public function checkJsonFile($file) {

        if(file_exists($file) && is_file($file)) {
            $content = file_get_contents($file);
            json_decode($content);
            return (json_last_error() == JSON_ERROR_NONE);
        }
    
        return false;
    }

    public function putData($file, $data, $print_only = false) {
        if(!empty($file)) {
            if($print_only) {
                print_r($data);
            } else {
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }

    public function getData($file) {
        if(!empty($file) && file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
    }
}
