<?php

final class IPF_Project{

    private $apps = array();
    public $sqlProfiler = null;

    static private $instance = NULL;

    static function getInstance(){
        if (self::$instance == NULL)
            self::$instance = new IPF_Project();
        return self::$instance;
    }

    private function __construct(){
        $applist = IPF::get('applications');
        foreach( $applist as &$appname){
            if (!IPF_Utils::isValidName($appname))
                throw new IPF_Exception_Panic("Application name \"$name\" is incorrect");
            $this->apps[$appname] = null;
        }
    }

    private function __clone(){
    }

    private function appClassName($name){
        return $name.'_App';
    }

    public function appList(){
        foreach($this->apps as $appName=>&$app){
            if ($app==null){
                $app = $this->getApp($appName);
            }
        }
        return $this->apps;
    }

    // Lazy Application Loader
    public function getApp($name){
        if (!array_key_exists($name,$this->apps))
            throw new IPF_Exception_Panic("Application \"$name\" not found");
        if ($this->apps[$name]==null){
                $className = $this->appClassName($name);
                $this->apps[$name] = new $className();
        }
        return $this->apps[$name];
    }

    public function checkApps(){
        foreach( $this->apps as $appname=>&$app)
            $this->getApp($appname);
    }

    public function generateModels(){
        IPF_ORM::generateModelsFromYaml(
            IPF::get('project_path').DIRECTORY_SEPARATOR.'models.yml',
            IPF::get('project_path').DIRECTORY_SEPARATOR.'models'
        );
    }

    public function generateContribModels(){
        foreach( $this->apps as $appname=>&$app){
            if (substr($appname,0,4)=='IPF_')
                $this->getApp($appname)->generateModels();
        }
    }

    public function createTablesFromModels(){
        foreach( $this->apps as $appname=>&$app){
            if (substr($appname,0,4)=='IPF_')
                $this->getApp($appname)->createTablesFromModels();
        }
        return IPF_ORM::createTablesFromModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models');
    }
    
    public function createPermissionsFromModels()
    {
        $pathes = array();
        
        foreach( $this->apps as $appname=>&$app)
        {
            if (substr($appname,0,4)=='IPF_')
                $pathes[] = $this->getApp($appname)->getPath().'models';
        }
        
        $pathes[] = IPF::get('project_path').DIRECTORY_SEPARATOR.'models';
        
        return IPF_Auth_App::createPermissionsFromModels($pathes);
    }

    public function generateSql(){
        $sql = '';
        foreach( $this->apps as $appname=>&$app){
            if (substr($appname,0,4)=='IPF_')
                $sql .= $this->getApp($appname)->generateSql()."\n";
        }
        $sql .= IPF_ORM::generateSqlFromModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models')."\n";
        return $sql;
    }

    public function loadModels(){
        foreach( $this->apps as $appname=>&$app){
            if (substr($appname,0,4)=='IPF_')
                $this->getApp($appname)->loadModels();
        }
        IPF_ORM::loadModels(IPF::get('project_path').DIRECTORY_SEPARATOR.'models');
    }

    private function cli(){
        $cli = new IPF_Cli();
        $cli->run();
    }

    public function run() {
        $dsn = IPF::get('dsn');
        if ($dsn=='')
            throw new IPF_Exception_Panic('Specify dsn in config file');

        if (IPF::get('debug')) {
            $this->sqlProfiler = new IPF_ORM_Connection_Profiler();
            IPF_ORM_Manager::getInstance()->dbListeners[] = $this->sqlProfiler;
        }

        IPF_ORM_Manager::getInstance()->openConnection($dsn, null, true, IPF::get('db_persistent', false));

        if (php_sapi_name() == 'cli'){
            $this->cli();
            return true;
        }
        if (php_sapi_name() == 'cli-server') {
            $parts = explode('?', $_SERVER['REQUEST_URI'], 2);
            $path = $_SERVER['DOCUMENT_ROOT'] . $parts[0];
            if (is_file($path))
                return false;
        }
        $this->loadModels();
        $this->router = new IPF_Router();
        $this->router->dispatch(IPF_HTTP_URL::getAction()); 
        return true;
    }
}

