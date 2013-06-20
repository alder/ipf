<?php

class IPF_Command_SyncDB
{
    public $command = 'syncdb';
    public $description = 'Create tables from model classes';

    public function run($args=null)
    {
        print "Create tables from model classes\n";

        $project = IPF_Project::getInstance();

        foreach ($project->frameworkApps() as $app)
            $app->createTablesFromModels();
        IPF_ORM::createTablesFromModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models');
        foreach ($project->customApps() as $app)
            $app->createTablesFromModels();
    }
}

