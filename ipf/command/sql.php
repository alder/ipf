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
            $sql .= IPF_ORM::generateSqlFromModels($app)."\n";

        foreach ($project->customApps() as $app)
            $sql .= IPF_ORM::generateSqlFromModels($app)."\n";

        print $sql;
    }
}

