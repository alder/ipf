<?php

class IPF_Command_SyncDB
{
    public $command = 'syncdb';
    public $description = 'Create tables from model classes';

    public function run($args=null)
    {
        print "Create tables from model classes\n";

        $project = IPF_Project::getInstance();

        foreach ($project->appList() as $app)
            IPF_ORM::createTablesFromModels($app);
    }
}

