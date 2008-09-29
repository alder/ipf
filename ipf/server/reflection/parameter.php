<?php

class IPF_Server_Reflection_Parameter
{
    protected $_reflection;
    protected $_position;
    protected $_type;
    protected $_description;

    public function __construct(ReflectionParameter $r, $type = 'mixed', $description = '')
    {
        $this->_reflection = $r;
        $this->setType($type);
        $this->setDescription($description);
    }

    public function __call($method, $args)
    {
        if (method_exists($this->_reflection, $method)) {
            return call_user_func_array(array($this->_reflection, $method), $args);
        }
        throw new IPF_Exception('Invalid reflection method');
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($type)
    {
        if (!is_string($type) && (null !== $type)) {
            throw new IPF_Exception('Invalid parameter type');
        }
        $this->_type = $type;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setDescription($description)
    {
        if (!is_string($description) && (null !== $description)) {
            throw new IPF_Exception('Invalid parameter description');
        }
        $this->_description = $description;
    }

    public function setPosition($index)
    {
        $this->_position = (int) $index;
    }

    public function getPosition()
    {
        return $this->_position;
    }
}
