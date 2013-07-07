<?php

class IPF_Command_CollectStatic
{
    public $command = 'collectstatic';
    public $description = 'Collect static files';

    public function run($args=null)
    {
        if (!in_array('--quiet', $args))
            print "Collecting static files\n";

        $destination = IPF::get('document_root') . DIRECTORY_SEPARATOR . IPF::get('static_url');

        foreach (IPF_Project::getInstance()->appList() as $app) {
            $source = $app->getPath() . 'static';
            if (is_dir($source))
                IPF_Utils::copyDirectory($source, $destination);
        }
    }
}

