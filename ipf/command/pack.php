<?php

class IPF_Command_Pack
{
    public $command = 'pack';
    public $description = 'Pack database dump and uploaded files to a single archive';

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

        $dumpCommand = new IPF_Command_DBDump;
        $dumpCommand->run(array('--quiet'));

        IPF_Shell::unlink($outputFileName);

        $tar_object = new Archive_Tar($outputFileName);
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
        $tar_object->create('upload.tar dump.sql');

        IPF_Shell::unlink('upload.tar');
        IPF_Shell::unlink('dump.sql');
    }
}

