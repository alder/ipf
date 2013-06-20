<?php

class IPF_Command_Fixtures
{
    public $command = 'fixtures';
    public $description = 'Load fixtures into database';

    public function run($args=null)
    {
        print "Load project fixtures to database\n";

        $project = IPF_Project::getInstance();

        $paths = array(IPF::get('project_path'));
        foreach ($project->customApps() as $app)
            $paths[] = $app->path;

        $fixtures = array();
        foreach ($paths as $path) {
            $path .= DIRECTORY_SEPARATOR.'fixtures.php';
            if (is_file($path))
                $fixtures = array_merge($fixtures, require $path);
        }

        if (!count($fixtures)) {
            echo "No fixtures found\n";
            return;
        }

        $project->loadModels();

        foreach ($fixtures as $fixture) {
            $modelClass = $fixture['model'];
            $key = $fixture['key'];
            $records = $fixture['records'];
            echo "Loading $modelClass ";
            foreach ($records as $record) {
                $model = IPF_ORM::getTable($modelClass)
                    ->createQuery()
                    ->where($key . ' = ?', array($record[$key]))
                    ->limit(1)
                    ->execute();

                if ($model)
                    $model = $model[0];
                else
                    $model = new $modelClass;

                foreach ($record as $k => $v)
                    $model->$k = $v;

                $model->save();
                echo '.';
            }
            echo "\n";
        }
    }
}

