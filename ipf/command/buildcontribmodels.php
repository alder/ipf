<?php

class IPF_Command_BuildContribModels
{
    public $command = 'buildcontribmodels';
    public $description = 'Build all contrib model classes';

    public function run($args=null)
    {
        print "Build all contrib model classes\n";

        $project = IPF_Project::getInstance();

        foreach ($project->frameworkApps() as $app)
            $app->generateModels();
    }
}

