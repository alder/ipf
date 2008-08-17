<?php

class IPF_ORM_Connection_Module
{
    protected $conn;
    protected $moduleName;

    public function __construct($conn = null)
    {
        if ( ! ($conn instanceof IPF_ORM_Connection)) {
            $conn = IPF_ORM_Manager::getInstance()->getCurrentConnection();
        }
        $this->conn = $conn;

        $e = explode('_', get_class($this));

        $this->moduleName = $e[1];
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }
}