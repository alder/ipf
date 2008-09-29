<?php

class IPF_XmlRpc_Value_Nil extends IPF_XmlRpc_Value_Scalar
{
    public function __construct()
    {
        $this->_type = self::XMLRPC_TYPE_NIL;
        $this->_value = null;
    }

    public function getValue()
    {
        return null;
    }

    public function saveXML()
    {
        if (! $this->_as_xml) {   // The XML was not generated yet
            $dom   = new DOMDocument('1.0', 'UTF-8');
            $value = $dom->appendChild($dom->createElement('value'));
            $type  = $value->appendChild($dom->createElement($this->_type));

            $this->_as_dom = $value;
            $this->_as_xml = $this->_stripXmlDeclaration($dom);
        }

        return $this->_as_xml;
    }
}

