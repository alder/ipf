<?php

class IPF_Command_SyncDB
{
    public $command = 'syncdb';
    public $description = 'Create tables from model classes';

    public function run($args=null)
    {
        print "Create tables from model classes\n";

        $project = IPF_Project::getInstance();

        $models = array();
        foreach ($project->appList() as $app)
            $models = array_merge($models, $app->modelList());

        IPF_ORM::createTablesFromModels($models);
    }
}

