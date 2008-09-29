<?php

class IPF_XmlRpc_Value_Double extends IPF_XmlRpc_Value_Scalar
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_DOUBLE;
        $this->_value = sprintf('%f',(float)$value);    // Make sure this value is float (double) and without the scientific notation
    }

    public function getValue()
    {
        return (float)$this->_value;
    }

}

