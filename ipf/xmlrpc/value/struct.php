<?php

class IPF_XmlRpc_Value_Struct extends IPF_XmlRpc_Value_Collection
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_STRUCT;
        parent::__construct($value);
    }

    public function saveXML()
    {
        if (!$this->_as_xml) {   // The XML code was not calculated yet
            $dom    = new DOMDocument('1.0');
            $value  = $dom->appendChild($dom->createElement('value'));
            $struct = $value->appendChild($dom->createElement('struct'));

            if (is_array($this->_value)) {
                foreach ($this->_value as $name => $val) {
                    /* @var $val IPF_XmlRpc_Value */
                    $member = $struct->appendChild($dom->createElement('member'));
                    $member->appendChild($dom->createElement('name', $name));
                    $member->appendChild($dom->importNode($val->getAsDOM(), 1));
                }
            }

            $this->_as_dom = $value;
            $this->_as_xml = $this->_stripXmlDeclaration($dom);
        }

        return $this->_as_xml;
    }
}

