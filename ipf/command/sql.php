<?php

class IPF_Command_Sql
{
    public $command = 'sql';
    public $description = 'Show all SQL DDL from model classes';

    public function run($args=null)
    {
        print "Show all SQL DDL from model classes\n";

        $project = IPF_Project::getInstance();

        $sql = '';

        foreach ($project->frameworkApps() as $app)
            $sql .= $app->generateSql()."\n";

        $sql .= IPF_ORM::generateSqlFromModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models')."\n";

        foreach ($project->customApps() as $app)
            $sql .= $app->generateSql()."\n";

        print $sql;
    }
}

