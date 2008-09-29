<?php

class IPF_XmlRpc_Value_Integer extends IPF_XmlRpc_Value_Scalar
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_INTEGER;
        $this->_value = (int)$value;    // Make sure this value is integer
    }

    public function getValue()
    {
        return $this->_value;
    }

}

