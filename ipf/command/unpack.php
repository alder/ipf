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

        $archive = new Archive_Tar($inputFileName);
        $archive->extract('.');

        $restoreCommand = new IPF_Command_DBRestore;
        $restoreCommand->run(array('--quiet'));
        IPF_Shell::unlink('dump.sql');

        $uploadsDir = IPF::get('document_root') . IPF::getUploadUrl();
        $archive = new Archive_Tar('upload.tar');
        $archive->extract($uploadsDir . '/..');
        IPF_Shell::unlink('upload.tar');
    }
}

