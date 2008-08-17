<?php

class IPF_ORM_Validator_Driver
{
    protected $_args = array();

    public function __get($arg)
    {
        if (isset($this->_args[$arg])) {
            return $this->_args[$arg];
        }
        return null;
    }

    public function __isset($arg)
    {
        return isset($this->_args[$arg]);
    }

    public function __set($arg, $value)
    {
        $this->_args[$arg] = $value;
        
        return $this;
    }

    public function getArg($arg)
    {
        if ( ! isset($this->_args[$arg])) {
            throw new IPF_ORM_Exception_Validator('Unknown option ' . $arg);
        }
        return $this->_args[$arg];
    }

    public function setArg($arg, $value)
    {
        $this->_args[$arg] = $value;
        
        return $this;
    }

    public function getArgs()
    {
        return $this->_args;
    }
}