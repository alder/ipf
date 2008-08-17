<?php

class IPF_ORM_Locator_Injectable
{
    protected $_locator;
    protected $_resources = array();

    protected static $_null;

    public function setLocator(IPF_ORM_Locator $locator)
    {
        $this->_locator = $locator;
        return $this;
    }

    public function getLocator()
    {
        if ( ! isset($this->_locator)) {
            $this->_locator = IPF_ORM_Locator::instance();

        }
        return $this->_locator;
    }

    public function locate($name)
    {
        if (isset($this->_resources[$name])) {
            if (is_object($this->_resources[$name])) {
                return $this->_resources[$name];
            } else {
                // get the name of the concrete implementation
                $concreteImpl = $this->_resources[$name];
                
                return $this->getLocator()->locate($concreteImpl);
            }
        } else {
            return $this->getLocator()->locate($name);
        }
    }

    public function bind($name, $resource)
    {
        $this->_resources[$name] = $resource;
        
        return $this;    
    }

    public static function initNullObject(IPF_ORM_Null $null)
    {
        self::$_null = $null;
    }

    public static function getNullObject()
    {
        return self::$_null;
    }
}