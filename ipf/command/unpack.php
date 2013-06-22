<?php

class IPF_Command_Unpack
{
    public $command = 'unpack';
    public $description = 'Unpack database dump and uploaded files from an archive';

    public function run($args=null)
    {
        $inputFileName = 'working-data.tar';

        if (is_dir($inputFileName)) {
            print 'Error. File "'.$inputFileName.'" was not found.';
            return;
        }

        IPF_Shell::unlink('upload.tar');
        IPF_Shell::unlink('dump.sql');

        (new Archive_Tar($inputFileName))->extract('.');

        (new IPF_Command_DBRestore)->run(array('--quiet'));
        IPF_Shell::unlink('dump.sql');

        $uploadsDir = IPF::get('document_root') . IPF::getUploadUrl();
        (new Archive_Tar('upload.tar'))->extract($uploadsDir . '/..');
        IPF_Shell::unlink('upload.tar');
    }
}

