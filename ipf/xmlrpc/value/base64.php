<?php

class IPF_XmlRpc_Value_Base64 extends IPF_XmlRpc_Value_Scalar
{
    public function __construct($value, $already_encoded=false)
    {
        $this->_type = self::XMLRPC_TYPE_BASE64;

        $value = (string)$value;    // Make sure this value is string
        if (!$already_encoded) {
            $value = base64_encode($value);     // We encode it in base64
        }
        $this->_value = $value;
    }

    public function getValue()
    {
        return base64_decode($this->_value);
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

