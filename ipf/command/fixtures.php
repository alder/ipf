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

        foreach ($fixtures as $fixture) {
            $modelClass = $fixture['model'];
            $key = $fixture['key'];
            if (!is_array($key))
                $key = array($key);
            $records = $fixture['records'];
            echo "Loading $modelClass ";
            $table = IPF_ORM::getTable($modelClass);
            foreach ($records as $record) {
                $query = $table
                    ->createQuery()
                    ->limit(1);
                foreach ($key as $k)
                    $query->addWhere($k . ' = ?', array($record[$k]));

                $model = $query->execute();
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

