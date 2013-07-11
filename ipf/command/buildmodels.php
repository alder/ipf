<?php

class IPF_Command_BuildModels
{
    public $command = 'buildmodels';
    public $description = 'Build all model classes';

    public function run($args=null)
    {
        print "Build all model classes\n\n";

        $project = IPF_Project::getInstance();

        $extraAllwedReferences = $this->frameworkModels($project);
        foreach ($this->paths($project) as $p) {
            list($name, $path) = $p;
            print "$name\n";
            $models = IPF_ORM::generateModelsFromYaml($path, $extraAllwedReferences);
            $extraAllwedReferences = array_merge($extraAllwedReferences, $models);
        }
    }

    private function frameworkModels($project)
    {
        $models = array();
        foreach ($project->frameworkApps() as $app)
            $models = array_merge($models, $app->modelList());
        return $models;
    }

    private function paths($project)
    {
        $paths = array(
            array('Project', IPF::get('project_path')),
        );
        foreach ($project->customApps() as $app)
            $paths[] = array($app->getName(), $app->getPath());
        return $paths;
    }
}

