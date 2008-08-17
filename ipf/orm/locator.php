<?php

class IPF_ORM_Locator implements Countable, IteratorAggregate
{
    protected $_resources = array();
    protected $_classPrefix = 'IPF_ORM_';
    protected static $_instances = array();
    public function __construct(array $defaults = null)
    {
        if (null !== $defaults) {
            foreach ($defaults as $name => $resource) {
                if ($resource instanceof IPF_ORM_Locator_Injectable) {
                    $resource->setLocator($this);
                }
                $this->_resources[$name] = $resource;
            }
        }
        self::$_instances[] = $this;
    }

    public static function instance()
    {
        if (empty(self::$_instances)) {
            $obj = new IPF_ORM_Locator();
        }
        return current(self::$_instances);
    }

    public function setClassPrefix($prefix) 
    {
        $this->_classPrefix = $prefix;
    }

    public function getClassPrefix()
    {
        return $this->_classPrefix;
    }

    public function contains($name)
    {
        return isset($this->_resources[$name]);
    }

    public function bind($name, $value)
    {
        $this->_resources[$name] = $value;
        
        return $this;
    }

    public function locate($name)
    {
        if (isset($this->_resources[$name])) {
            return $this->_resources[$name];
        } else {
            $className = $name;

            if ( ! class_exists($className)) {

                $name = explode('.', $name);
                $name = array_map('strtolower', $name);
                $name = array_map('ucfirst', $name);
                $name = implode('_', $name);
                
                $className = $this->_classPrefix . $name;
                
                if ( ! class_exists($className)) {
                    throw new IPF_ORM_Exception_Locator("Couldn't locate resource " . $className);
                }
            }

            $this->_resources[$name] = new $className();

            if ($this->_resources[$name] instanceof IPF_ORM_Locator_Injectable) {
                $this->_resources[$name]->setLocator($this);
            }

            return $this->_resources[$name];
        }

        throw new IPF_ORM_Exception_Locator("Couldn't locate resource " . $name);
    }

    public function count()
    {
        return count($this->_resources);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_resources);
    }
}
