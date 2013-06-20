<?php

class IPF_Command_SyncPerms
{
    public $command = 'syncperms';
    public $description = 'Create/Update permissions from model classes';

    public function run($args=null)
    {
        print "Create/Update permissions from model classes\n";

        $project = IPF_Project::getInstance();

        $pathes = array();

        foreach ($project->apps as $appname => &$app) {
            $app = $project->getApp($appName);
            $pathes[] = $app->getPath().'models';
        }

        $pathes[] = IPF::get('project_path').DIRECTORY_SEPARATOR.'models';

        return IPF_Auth_App::createPermissionsFromModels($pathes);
    }
}

