<?php

class IPF_Command_Pack
{
    public $command = 'pack';
    public $description = 'Pack database dump and uploaded files to a single archive.';

    public function run($args=null)
    {
        $outputFileName = 'working-data.tar';

        $uploadsDir = IPF::get('document_root') . IPF::getUploadUrl();
        if (is_dir($uploadsDir)) {
            $workingDirectory = getcwd();
            chdir($uploadsDir . '/..');
            $tar_object = new Archive_Tar($workingDirectory . '/upload.tar');
            $tar_object->setErrorHandling(PEAR_ERROR_PRINT);  
            $tar_object->create('upload');
            chdir($workingDirectory);
        }

        (new IPF_Command_DBDump)->run(array('quiet'));

        unlink($outputFileName);

        $tar_object = new Archive_Tar($outputFileName);
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);  
        $tar_object->create('upload.tar dump.sql');

        unlink('upload.tar');
        unlink('dump.sql');
    }
}

