<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\systemdata;

class SystemDataCli extends SystemData
{
    public function checkJsonFile($file)
    {
        $this->enforceAdmin();

        if (\SugarAutoloader::fileExists($file)) {
            $uploadFile = new \UploadFile();
            $uploadFile->temp_file_location = $file;
            $content = $uploadFile->get_file_contents();

            return $this->isValidJson($content);
        }
    
        return false;
    }

    public function formatOutputData($data)
    {
        if (!empty($data)) {
            return $this->jsonEncode($data);
        }
    }

    public function getData($file)
    {
        $this->enforceAdmin();

        if (!empty($file) && \SugarAutoloader::fileExists($file)) {
            $uploadFile = new \UploadFile();
            $uploadFile->temp_file_location = $file;
            $content = $uploadFile->get_file_contents();

            return $this->jsonDecode($content);
        }
        
        return false;
    }
}
