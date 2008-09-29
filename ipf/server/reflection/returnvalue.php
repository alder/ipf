<?php

class IPF_Server_Reflection_ReturnValue
{
    protected $_type;
    protected $_description;

    public function __construct($type = 'mixed', $description = '')
    {
        $this->setType($type);
        $this->setDescription($description);
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
}
