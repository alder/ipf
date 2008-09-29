<?php

abstract class IPF_XmlRpc_Value_Scalar extends IPF_XmlRpc_Value
{
    public function saveXML()
    {
        if (!$this->_as_xml) {   // The XML code was not calculated yet
            $dom   = new DOMDocument('1.0');
            $value = $dom->appendChild($dom->createElement('value'));
            $type  = $value->appendChild($dom->createElement($this->_type));
            $type->appendChild($dom->createTextNode($this->getValue()));

            $this->_as_dom = $value;
            $this->_as_xml = $this->_stripXmlDeclaration($dom);
        }

        return $this->_as_xml;
    }
}

