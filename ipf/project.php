<?php

final class IPF_Project
{
    private $apps = array();
    public $sqlProfiler = null;

    static private $instance = NULL;

    static function getInstance()
    {
        if (self::$instance == NULL)
            self::$instance = new IPF_Project;
        return self::$instance;
    }

    private function __construct()
    {
        $applist = IPF::get('applications');
        foreach ($applist as &$appname) {
            if (!IPF_Utils::isValidName($appname))
                throw new IPF_Exception_Panic("Application name \"$name\" is incorrect");
            $this->apps[$appname] = null;
        }
    }

    private function __clone()
    {
    }

    private function appClassName($name)
    {
        return $name . '_App';
    }

    public function appList()
    {
        foreach($this->apps as $appName => &$app) {
            if ($app == null) {
                $app = $this->getApp($appName);
            }
        }
        return $this->apps;
    }

    // Lazy Application Loader
    public function getApp($name)
    {
        if (!array_key_exists($name, $this->apps))
            throw new IPF_Exception_Panic("Application \"$name\" not found");
        if ($this->apps[$name] == null) {
            $className = $this->appClassName($name);
            $this->apps[$name] = new $className();
        }
        return $this->apps[$name];
    }

    public function checkApps()
    {
        foreach ($this->apps as $appname => &$app)
            $this->getApp($appname);
    }

    protected function frameworkApps()
    {
        $result = array();
        foreach ($this->apps as $appname => &$app) {
            if (substr($appname, 0, 4) === 'IPF_')
                $result[] = $this->getApp($appname);
        }
        return $result;
    }

    protected function customApps()
    {
        $result = array();
        foreach ($this->apps as $appname => &$app) {
            if (substr($appname, 0, 4) !== 'IPF_')
                $result[] = $this->getApp($appname);
        }
        return $result;
    }

    public function generateModels()
    {
        IPF_ORM::generateModelsFromYaml(
            IPF::get('project_path').DIRECTORY_SEPARATOR.'models.yml',
            IPF::get('project_path').DIRECTORY_SEPARATOR.'models'
        );
        foreach ($this->customApps() as $app)
            $app->generateModels();
    }

    public function generateContribModels()
    {
        foreach ($this->frameworkApps() as $app)
            $app->generateModels();
    }

    public function createTablesFromModels()
    {
        foreach ($this->frameworkApps() as $app)
            $app->createTablesFromModels();
        IPF_ORM::createTablesFromModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models');
        foreach ($this->customApps() as $app)
            $app->createTablesFromModels();
    }
    
    public function createPermissionsFromModels()
    {
        $pathes = array();

        foreach ($this->apps as $appname => &$app) {
            $app = $this->getApp($appName);
            $pathes[] = $app->getPath().'models';
        }

        $pathes[] = IPF::get('project_path').DIRECTORY_SEPARATOR.'models';

        return IPF_Auth_App::createPermissionsFromModels($pathes);
    }

    public function generateSql()
    {
        $sql = '';

        foreach ($this->frameworkApps() as $app)
            $sql .= $app->generateSql()."\n";

        $sql .= IPF_ORM::generateSqlFromModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models')."\n";

        foreach ($this->customApps() as $app)
            $sql .= $app->generateSql()."\n";

        return $sql;
    }

    public function loadFixtures()
    {
        $ficturesPath = IPF::get('project_path').DIRECTORY_SEPARATOR.'fixtures.php';
        if (!is_file($ficturesPath)) {
            echo "No fixtures found\n";
            return;
        }

        $this->loadModels();

        $fixtures = require $ficturesPath;

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

    public function loadModels()
    {
        foreach ($this->frameworkApps() as $app)
            $app->loadModels();

        IPF_ORM::loadModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models');

        foreach ($this->customApps() as $app)
            $app->loadModels();
    }

    private function cli()
    {
        $cli = new IPF_Cli();
        $cli->run();
    }

    public function run()
    {
        if (IPF::get('debug')) {
            $this->sqlProfiler = new IPF_ORM_Connection_Profiler();
            IPF_ORM_Manager::getInstance()->dbListeners[] = $this->sqlProfiler;
        }

        IPF_ORM_Manager::getInstance()->openConnection(IPF::get('database', IPF::get('dsn')));

        if (php_sapi_name() === 'cli') {
            $this->cli();
        } else {
            $this->loadModels();
            $this->router = new IPF_Router();
            $this->router->dispatch(IPF_HTTP_URL::getAction());
        }

        return true;
    }
}

