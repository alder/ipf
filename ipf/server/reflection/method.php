<?php

class IPF_Server_Reflection_Method extends IPF_Server_Reflection_Function_Abstract
{
    protected $_class;
    protected $_classReflection;

    public function __construct(IPF_Server_Reflection_Class $class, ReflectionMethod $r, $namespace = null, $argv = array())
    {
        $this->_classReflection = $class;
        $this->_reflection      = $r;

        $classNamespace = $class->getNamespace();

        // Determine namespace
        if (!empty($namespace)) {
            $this->setNamespace($namespace);
        } elseif (!empty($classNamespace)) {
            $this->setNamespace($classNamespace);
        }

        // Determine arguments
        if (is_array($argv)) {
            $this->_argv = $argv;
        }

        // If method call, need to store some info on the class
        $this->_class = $class->getName();

        // Perform some introspection
        $this->_reflect();
    }

    public function getDeclaringClass()
    {
        return $this->_classReflection;
    }

    public function __wakeup()
    {
        $this->_classReflection = new IPF_Server_Reflection_Class(new ReflectionClass($this->_class), $this->getNamespace(), $this->getInvokeArguments());
        $this->_reflection = new ReflectionMethod($this->_classReflection->getName(), $this->getName());
    }

}
