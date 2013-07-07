<?php

abstract class IPF_Application
{
    protected $models = array();
    protected $name = null;

    public function __construct($data=array())
    {
        $this->name = str_replace('_App', '', get_class($this));

        if (strpos($this->name,'IPF_')===0)
            $this->path = IPF::get('ipf_path').DIRECTORY_SEPARATOR.strtolower(str_replace('_',DIRECTORY_SEPARATOR,$this->name));
        else
            $this->path = IPF::get('project_path').DIRECTORY_SEPARATOR.strtolower(str_replace('_',DIRECTORY_SEPARATOR,$this->name));
        $this->path .= DIRECTORY_SEPARATOR;

        if (array_key_exists('models',$data)) {
            foreach ($data['models'] as &$modelname) {
                if (!IPF_Utils::isValidName($modelname))
                    throw new IPF_Exception_Panic("Model name \"$modelname\" is incorrect");
                $this->models[] = $modelname;
            }
        } else {
            try {
                $it = new DirectoryIterator($this->path.DIRECTORY_SEPARATOR.'models');
                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName(), 2);
                    if (count($e) == 2 && $e[1] === 'php') {
                        $this->models[] = $e[0];
                    }
                }
            } catch(RuntimeException $e) {
                // nothing to do
            }
        }
    }

    public function generateSql()
    {
        return IPF_ORM::generateSqlFromModels($this->path.'models');
    }

    public function modelList()
    {
        return $this->models;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLabel()
    {
        return str_replace('ipf_','',strtolower($this->name));
    }

    public function getAdditions()
    {
        return array();
    }

    public function getTitle()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getSlug()
    {
        $e = explode('_',$this->name);
        return strtolower($e[count($e)-1]);
    }

    public function createTablesFromModels()
    {
        return IPF_ORM::createTablesFromModels($this->path.'models');
    }

    public function generateModels()
    {
        IPF_ORM::generateModelsFromYaml($this->path.'models.yml', $this->path.'models');
    }

    public function loadModels()
    {
        IPF_ORM::loadModels($this->path.'models');
    }

    /**
     * Returns additional context for templates
     *
     * @return array Dictionary of values injected into template context
     */
    public function templateContext($request)
    {
        return array();
    }
}

