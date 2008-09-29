<?php

abstract class IPF_XmlRpc_Value
{
    protected $_value;
    protected $_type;
    protected $_as_xml;
    protected $_as_dom;

    const AUTO_DETECT_TYPE = 'auto_detect';
    const XML_STRING = 'xml';

    const XMLRPC_TYPE_I4       = 'i4';
    const XMLRPC_TYPE_INTEGER  = 'int';
    const XMLRPC_TYPE_DOUBLE   = 'double';
    const XMLRPC_TYPE_BOOLEAN  = 'boolean';
    const XMLRPC_TYPE_STRING   = 'string';
    const XMLRPC_TYPE_DATETIME = 'dateTime.iso8601';
    const XMLRPC_TYPE_BASE64   = 'base64';
    const XMLRPC_TYPE_ARRAY    = 'array';
    const XMLRPC_TYPE_STRUCT   = 'struct';
    const XMLRPC_TYPE_NIL      = 'nil';

    public function getType()
    {
        return $this->_type;
    }

    abstract public function getValue();
    abstract public function saveXML();

    public function getAsDOM()
    {
        if (!$this->_as_dom) {
            $doc = new DOMDocument('1.0');
            $doc->loadXML($this->saveXML());
            $this->_as_dom = $doc->documentElement;
        }

        return $this->_as_dom;
    }

    protected function _stripXmlDeclaration(DOMDocument $dom)
    {
        return preg_replace('/<\?xml version="1.0"( encoding="[^\"]*")?\?>\n/u', '', $dom->saveXML());
    }

    public static function getXmlRpcValue($value, $type = self::AUTO_DETECT_TYPE)
    {
        switch ($type) {
            case self::AUTO_DETECT_TYPE:
                // Auto detect the XML-RPC native type from the PHP type of $value
                return self::_phpVarToNativeXmlRpc($value);

            case self::XML_STRING:
                // Parse the XML string given in $value and get the XML-RPC value in it
                return self::_xmlStringToNativeXmlRpc($value);

            case self::XMLRPC_TYPE_I4:
                // fall through to the next case
            case self::XMLRPC_TYPE_INTEGER:
                return new IPF_XmlRpc_Value_Integer($value);

            case self::XMLRPC_TYPE_DOUBLE:
                return new IPF_XmlRpc_Value_Double($value);

            case self::XMLRPC_TYPE_BOOLEAN:
                return new IPF_XmlRpc_Value_Boolean($value);

            case self::XMLRPC_TYPE_STRING:
                return new IPF_XmlRpc_Value_String($value);

            case self::XMLRPC_TYPE_BASE64:
                return new IPF_XmlRpc_Value_Base64($value);

            case self::XMLRPC_TYPE_NIL:
                return new IPF_XmlRpc_Value_Nil();

            case self::XMLRPC_TYPE_DATETIME:
                return new IPF_XmlRpc_Value_DateTime($value);

            case self::XMLRPC_TYPE_ARRAY:
                return new IPF_XmlRpc_Value_Array($value);

            case self::XMLRPC_TYPE_STRUCT:
                return new IPF_XmlRpc_Value_Struct($value);

            default:
                throw new IPF_Exception('Given type is not a '. __CLASS__ .' constant');
        }
    }

    private static function _phpVarToNativeXmlRpc($value)
    {
        switch (gettype($value)) {
            case 'object':
                // Check to see if it's an XmlRpc value
                if ($value instanceof IPF_XmlRpc_Value) {
                    return $value;
                }
                
                // Otherwise, we convert the object into a struct
                $value = get_object_vars($value);
                // Break intentionally omitted
            case 'array':
                // Default native type for a PHP array (a simple numeric array) is 'array'
                $obj = 'IPF_XmlRpc_Value_Array';

                // Determine if this is an associative array
                if (!empty($value) && is_array($value) && (array_keys($value) !== range(0, count($value) - 1))) {
                    $obj = 'IPF_XmlRpc_Value_Struct';
                }
                return new $obj($value);

            case 'integer':
                return new IPF_XmlRpc_Value_Integer($value);

            case 'double':
                return new IPF_XmlRpc_Value_Double($value);

            case 'boolean':
                return new IPF_XmlRpc_Value_Boolean($value);

            case 'NULL':
            case 'null':
                return new IPF_XmlRpc_Value_Nil();

            case 'string':
                // Fall through to the next case
            default:
                // If type isn't identified (or identified as string), it treated as string
                return new IPF_XmlRpc_Value_String($value);
        }
    }

    private static function _xmlStringToNativeXmlRpc($simple_xml)
    {
        if (!$simple_xml instanceof SimpleXMLElement) {
            try {
                $simple_xml = @new SimpleXMLElement($simple_xml);
            } catch (Exception $e) {
                // The given string is not a valid XML
                throw new IPF_Exception('Failed to create XML-RPC value from XML string: '.$e->getMessage(),$e->getCode());
            }
        }

        // Get the key (tag name) and value from the simple xml object and convert the value to an XML-RPC native value
        list($type, $value) = each($simple_xml);
        if (!$type) {    // If no type was specified, the default is string
            $type = self::XMLRPC_TYPE_STRING;
        }

        switch ($type) {
            // All valid and known XML-RPC native values
            case self::XMLRPC_TYPE_I4:
                // Fall through to the next case
            case self::XMLRPC_TYPE_INTEGER:
                $xmlrpc_val = new IPF_XmlRpc_Value_Integer($value);
                break;
            case self::XMLRPC_TYPE_DOUBLE:
                $xmlrpc_val = new IPF_XmlRpc_Value_Double($value);
                break;
            case self::XMLRPC_TYPE_BOOLEAN:
                $xmlrpc_val = new IPF_XmlRpc_Value_Boolean($value);
                break;
            case self::XMLRPC_TYPE_STRING:
                $xmlrpc_val = new IPF_XmlRpc_Value_String($value);
                break;
            case self::XMLRPC_TYPE_DATETIME:  // The value should already be in a iso8601 format
                $xmlrpc_val = new IPF_XmlRpc_Value_DateTime($value);
                break;
            case self::XMLRPC_TYPE_BASE64:    // The value should already be base64 encoded
                $xmlrpc_val = new IPF_XmlRpc_Value_Base64($value ,true);
                break;
            case self::XMLRPC_TYPE_NIL:    // The value should always be NULL
                $xmlrpc_val = new IPF_XmlRpc_Value_Nil();
                break;
            case self::XMLRPC_TYPE_ARRAY:
                // If the XML is valid, $value must be an SimpleXML element and contain the <data> tag
                if (!$value instanceof SimpleXMLElement) {
                    throw new IPF_Exception('XML string is invalid for XML-RPC native '. self::XMLRPC_TYPE_ARRAY .' type');
                } 

                // PHP 5.2.4 introduced a regression in how empty($xml->value) 
                // returns; need to look for the item specifically
                $data = null;
                foreach ($value->children() as $key => $value) {
                    if ('data' == $key) {
                        $data = $value;
                        break;
                    }
                }
                
                if (null === $data) {
                    throw new IPF_Exception('Invalid XML for XML-RPC native '. self::XMLRPC_TYPE_ARRAY .' type: ARRAY tag must contain DATA tag');
                }
                $values = array();
                // Parse all the elements of the array from the XML string
                // (simple xml element) to IPF_XmlRpc_Value objects
                foreach ($data->value as $element) {
                    $values[] = self::_xmlStringToNativeXmlRpc($element);
                }
                $xmlrpc_val = new IPF_XmlRpc_Value_Array($values);
                break;
            case self::XMLRPC_TYPE_STRUCT:
                // If the XML is valid, $value must be an SimpleXML
                if ((!$value instanceof SimpleXMLElement)) {
                    throw new IPF_Exception('XML string is invalid for XML-RPC native '. self::XMLRPC_TYPE_STRUCT .' type');
                }
                $values = array();
                // Parse all the memebers of the struct from the XML string
                // (simple xml element) to IPF_XmlRpc_Value objects
                foreach ($value->member as $member) {
                    // @todo? If a member doesn't have a <value> tag, we don't add it to the struct
                    // Maybe we want to throw an exception here ?
                    if ((!$member->value instanceof SimpleXMLElement) || empty($member->value)) {
                        continue;
                        //throw new IPF_XmlRpc_Value_Exception('Member of the '. self::XMLRPC_TYPE_STRUCT .' XML-RPC native type must contain a VALUE tag');
                    }
                    $values[(string)$member->name] = self::_xmlStringToNativeXmlRpc($member->value);
                }
                $xmlrpc_val = new IPF_XmlRpc_Value_Struct($values);
                break;
            default:
                throw new IPF_Exception('Value type \''. $type .'\' parsed from the XML string is not a known XML-RPC native type');
                break;
        }
        $xmlrpc_val->_setXML($simple_xml->asXML());

        return $xmlrpc_val;
    }

    private function _setXML($xml)
    {
        $this->_as_xml = $xml;
    }

}


