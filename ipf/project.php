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
	    foreach( $this->apps as $appname=>&$app)
	        $this->getApp($appname)->generateModels();
	}
	
    public function createTablesFromModels(){
	    foreach( $this->apps as $appname=>&$app)
	        $this->getApp($appname)->createTablesFromModels();
    }	

	public function generateSql(){
	    $sql = '';
	    foreach( $this->apps as $appname=>&$app)
	        $sql .= $this->getApp($appname)->generateSql();
	        $sql .= "\n\n";
	    return $sql;
	}

    public function loadModels(){
	    foreach( $this->apps as $appname=>&$app){
	        $this->getApp($appname)->loadModels();
	    }
    }	
	
	private function cli(){
	    $cli = new IPF_Cli();
	    $cli->run();
	}

	public function run(){
	    $dsn = IPF::get('dsn');
	    if ($dsn=='')
	        throw new IPF_Exception_Panic('Specify dsn in config file');
        $conn = IPF_ORM_Manager::connection($dsn);

	    if (IPF::get('debug')){
            $this->sqlProfiler = new IPF_ORM_Connection_Profiler();
            IPF_ORM_Manager::getInstance()->getCurrentConnection()->setListener($this->sqlProfiler);
        }
        
        if (php_sapi_name()=='cli'){
            $this->cli();
            return;
        }
        $this->loadModels();
        IPF_ORM_Manager::getInstance()->setAttribute(IPF_ORM::ATTR_VALIDATE, IPF_ORM::VALIDATE_ALL);
        $this->router = new IPF_Router();
        $this->router->dispatch(IPF_HTTP_URL::getAction()); 
	}
}