<?php

class IPF_XmlRpc_Value_String extends IPF_XmlRpc_Value_Scalar
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_STRING;

        // Make sure this value is string and all XML characters are encoded
        $this->_value = $this->_xml_entities($value);
    }

    public function getValue()
    {
        return html_entity_decode($this->_value, ENT_QUOTES, 'UTF-8');
    }

    private function _xml_entities($str)
    {
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }

}

