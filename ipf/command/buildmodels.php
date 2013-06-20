<?php

class IPF_Command_BuildModels
{
    public $command = 'buildmodels';
    public $description = 'Build all model classes';

    public function run($args=null)
    {
        print "Build All Model Classes\n";

        $project = IPF_Project::getInstance();

        IPF_ORM::generateModelsFromYaml(
            IPF::get('project_path').DIRECTORY_SEPARATOR.'models.yml',
            IPF::get('project_path').DIRECTORY_SEPARATOR.'models'
        );

        foreach ($project->customApps() as $app)
            $app->generateModels();
    }
}

