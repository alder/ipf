<?php

class IPF_XmlRpc_Value_Array extends IPF_XmlRpc_Value_Collection
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_ARRAY;
        parent::__construct($value);
    }

    public function saveXML()
    {
        if (!$this->_as_xml) {   // The XML code was not calculated yet
            $dom   = new DOMDocument('1.0');
            $value = $dom->appendChild($dom->createElement('value'));
            $array = $value->appendChild($dom->createElement('array'));
            $data  = $array->appendChild($dom->createElement('data'));

            if (is_array($this->_value)) {
                foreach ($this->_value as $val) {
                    /* @var $val IPF_XmlRpc_Value */
                    $data->appendChild($dom->importNode($val->getAsDOM(), true));
                }
            }

            $this->_as_dom = $value;
            $this->_as_xml = $this->_stripXmlDeclaration($dom);
        }

        return $this->_as_xml;
    }
}

