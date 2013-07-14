<?php

final class IPF_Project
{
    private $apps = array();
    public $router = null;
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
        $this->router = new IPF_Router;
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

    public function frameworkApps()
    {
        $result = array();
        foreach ($this->apps as $appname => &$app) {
            if (substr($appname, 0, 4) === 'IPF_')
                $result[] = $this->getApp($appname);
        }
        return $result;
    }

    public function customApps()
    {
        $result = array();
        foreach ($this->apps as $appname => &$app) {
            if (substr($appname, 0, 4) !== 'IPF_')
                $result[] = $this->getApp($appname);
        }
        return $result;
    }

    public function loadAllModels()
    {
        foreach ($this->appList() as $appname => $app)
            foreach ($app->modelList() as $modelName)
                new $modelName;
    }

    public function run()
    {
        if (IPF::get('debug')) {
            error_reporting(E_ALL);

            $this->sqlProfiler = new IPF_ORM_Connection_Profiler();
            IPF_ORM_Manager::getInstance()->dbListeners[] = $this->sqlProfiler;
        }

        IPF_ORM_Manager::getInstance()->openConnection(IPF::get('database', IPF::get('dsn')));

        if (php_sapi_name() === 'cli') {
            $cli = new IPF_Cli;
            $cli->run();
        } else {
            $this->loadAllModels();
            $this->router->dispatch(IPF_HTTP_URL::getAction());
        }

        return true;
    }
}

