<?php

abstract class IPF_Application{
    
    protected $models = array();
    protected $name = null;
    
    public function __construct($data){
        $this->setName();
        if (array_key_exists('models',$data)){
    	    foreach($data['models'] as &$modelname){
    	        if (!IPF_Utils::isValidName($modelname))
        	        throw new IPF_Exception_Panic("Model name \"$name\" is incorrect");
    	        $this->models[] = $modelname;
            }
        }
    }

    protected function setName(){
        $this->name = str_replace('_App', '', get_class($this)); 
        if (strpos($this->name,'IPF_')===0)
            $this->path = IPF::get('ipf_path').DIRECTORY_SEPARATOR.'ipf'.DIRECTORY_SEPARATOR.strtolower(str_replace('_',DIRECTORY_SEPARATOR,str_replace('IPF_','',$this->name)));
        else
            $this->path = IPF::get('project_path').DIRECTORY_SEPARATOR.strtolower(str_replace('_',DIRECTORY_SEPARATOR,$this->name));
        $this->path .= DIRECTORY_SEPARATOR;
    } 

    public function generateSql(){
        if (count($this->models)==0)
            return;
        return IPF_ORM::generateSqlFromModels($this->path.'models');
    }
    
    public function modelList(){
        return $this->models;
    }

    public function getName(){
        return $this->name;
    }

    public function getLabel(){
        return str_replace('ipf_','',strtolower($this->name));
    }

    
    public function getTitle(){
        return $this->name;
    }

    public function getSlug(){
        $e = explode('_',$this->name);
        return strtolower($e[count($e)-1]);
    }

    public function createTablesFromModels(){
        if (count($this->models)==0)
            return;
        return IPF_ORM::createTablesFromModels($this->path.'models');
    }
    
    public function generateModels(){
        //if (count($this->models)==0)
        //   return;
        IPF_ORM::generateModelsFromYaml($this->path.'models.yml', $this->path.'models');
    }

    public function loadModels(){
        if (count($this->models)==0)
            return;
        IPF_ORM::loadModels($this->path.'models');
    }
}