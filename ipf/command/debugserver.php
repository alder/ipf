<?php

class IPF_Command_DebugServer
{
    public $command = 'run';
    public $description = 'Run debug server on 0.0.0.0:8000';

    public function run($args=null)
    {
        $root = IPF::get('document_root');
        IPF_Shell::call('php', '-S', '0.0.0.0:8000', '-t', $root, $root . '/index.php');
    }
}

