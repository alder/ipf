<?php

class IPF_Command_BuildContribModels
{
    public $command = 'buildcontribmodels';
    public $description = 'Build all contrib model classes';

    public function run($args=null)
    {
        print "Build all contrib model classes\n";

        $project = IPF_Project::getInstance();

        $extraAllwedReferences = array();
        foreach ($project->frameworkApps() as $app) {
            $models = IPF_ORM::generateModelsFromYaml($app->path, $extraAllwedReferences);
            $extraAllwedReferences = array_merge($extraAllwedReferences, $models);
        }
    }
}

