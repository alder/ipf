<?php

class IPF_Command_SyncPerms
{
    public $command = 'syncperms';
    public $description = 'Create/Update permissions from model classes';

    public function run($args=null)
    {
        print "Create/Update permissions from model classes\n";
        IPF_Auth_App::createPermissionsFromModels();
    }
}

