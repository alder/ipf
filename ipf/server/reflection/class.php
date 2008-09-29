<?php

class IPF_Server_Reflection_Class
{
    protected $_config = array();
    protected $_methods = array();
    protected $_namespace = null;
    protected $_reflection;
    public function __construct(ReflectionClass $reflection, $namespace = null, $argv = false)
    {
        $this->_reflection = $reflection;
        $this->setNamespace($namespace);

        foreach ($reflection->getMethods() as $method) {
            // Don't aggregate magic methods
            if ('__' == substr($method->getName(), 0, 2)) {
                continue;
            }

            if ($method->isPublic()) {
                // Get signatures and description
                $this->_methods[] = new IPF_Server_Reflection_Method($this, $method, $this->getNamespace(), $argv);
            }
        }
    }

    public function __call($method, $args)
    {
        if (method_exists($this->_reflection, $method)) {
            return call_user_func_array(array($this->_reflection, $method), $args);
        }
        throw new IPF_Exception('Invalid reflection method');
    }

    public function __get($key)
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }

        return null;
    }

    public function __set($key, $value)
    {
        $this->_config[$key] = $value;
    }

    public function getMethods()
    {
        return $this->_methods;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function setNamespace($namespace)
    {
        if (empty($namespace)) {
            $this->_namespace = '';
            return;
        }

        if (!is_string($namespace) || !preg_match('/[a-z0-9_\.]+/i', $namespace)) {
            throw new IPF_Exception('Invalid namespace');
        }

        $this->_namespace = $namespace;
    }

    public function __wakeup()
    {
        $this->_reflection = new ReflectionClass($this->getName());
    }
}
