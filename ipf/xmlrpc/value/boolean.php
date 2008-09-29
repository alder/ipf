<?php

class IPF_XmlRpc_Value_Boolean extends IPF_XmlRpc_Value_Scalar
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_BOOLEAN;
        $this->_value = (int)(bool)$value;
    }

    public function getValue()
    {
        return (bool)$this->_value;
    }

    public function saveXML()
    {
        if (! $this->_as_xml) {   // The XML was not generated yet
            $dom   = new DOMDocument('1.0', 'UTF-8');
            $value = $dom->appendChild($dom->createElement('value'));
            $type  = $value->appendChild($dom->createElement($this->_type));
            $type->appendChild($dom->createTextNode($this->_value));

            $this->_as_dom = $value;
            $this->_as_xml = $this->_stripXmlDeclaration($dom);
        }

        return $this->_as_xml;
    }
}

