<?php

class IPF_XmlRpc_Fault
{
    protected $_code;
    protected $_encoding = 'UTF-8';
    protected $_message;

    protected $_internal = array(
        404 => 'Unknown Error',

        // 610 - 619 reflection errors
        610 => 'Invalid method class',
        611 => 'Unable to attach function or callback; not callable',
        612 => 'Unable to load array; not an array',
        613 => 'One or more method records are corrupt or otherwise unusable',

        // 620 - 629 dispatch errors
        620 => 'Method does not exist',
        621 => 'Error instantiating class to invoke method',
        622 => 'Method missing implementation',
        623 => 'Calling parameters do not match signature',

        // 630 - 639 request errors
        630 => 'Unable to read request',
        631 => 'Failed to parse request',
        632 => 'Invalid request, no method passed; request must contain a \'methodName\' tag',
        633 => 'Param must contain a value',
        634 => 'Invalid method name',
        635 => 'Invalid XML provided to request',
        636 => 'Error creating xmlrpc value',

        // 640 - 649 system.* errors
        640 => 'Method does not exist',

        // 650 - 659 response errors
        650 => 'Invalid XML provided for response',
        651 => 'Failed to parse response',
        652 => 'Invalid response',
        653 => 'Invalid XMLRPC value in response',
    );

    public function __construct($code = 404, $message = '')
    {
        $this->setCode($code);
        $code = $this->getCode();

        if (empty($message) && isset($this->_internal[$code])) {
            $message = $this->_internal[$code];
        } elseif (empty($message)) {
            $message = 'Unknown error';
        }
        $this->setMessage($message);
    }

    public function setCode($code)
    {
        $this->_code = (int) $code;
        return $this;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function setMessage($message)
    {
        $this->_message = (string) $message;
        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    public function loadXml($fault)
    {
        if (!is_string($fault)) {
            throw new IPF_Exception('Invalid XML provided to fault');
        }

        try {
            $xml = @new SimpleXMLElement($fault);
        } catch (Exception $e) {
            // Not valid XML
            throw new IPF_Exception('Failed to parse XML fault: ' .  $e->getMessage(), 500);
        }

        // Check for fault
        if (!$xml->fault) {
            // Not a fault
            return false;
        }

        if (!$xml->fault->value->struct) {
            // not a proper fault
            throw new IPF_Exception('Invalid fault structure', 500);
        }

        $structXml = $xml->fault->value->asXML();
        $structXml = preg_replace('/<\?xml version=.*?\?>/i', '', $structXml);
        $struct    = IPF_XmlRpc_Value::getXmlRpcValue(trim($structXml), IPF_XmlRpc_Value::XML_STRING);
        $struct    = $struct->getValue();

        if (isset($struct['faultCode'])) {
            $code = $struct['faultCode'];
        }
        if (isset($struct['faultString'])) {
            $message = $struct['faultString'];
        }

        if (empty($code) && empty($message)) {
            throw new IPF_Exception('Fault code and string required');
        }

        if (empty($code)) {
            $code = '404';
        }

        if (empty($message)) {
            if (isset($this->_internal[$code])) {
                $message = $this->_internal[$code];
            } else {
                $message = 'Unknown Error';
            }
        }

        $this->setCode($code);
        $this->setMessage($message);

        return true;
    }

    public static function isFault($xml)
    {
        $fault = new self();
        try {
            $isFault = $fault->loadXml($xml);
        } catch (IPF_Exception $e) {
            $isFault = false;
        }

        return $isFault;
    }

    public function saveXML()
    {
        // Create fault value
        $faultStruct = array(
            'faultCode'   => $this->getCode(),
            'faultString' => $this->getMessage()
        );
        $value = IPF_XmlRpc_Value::getXmlRpcValue($faultStruct);
        $valueDOM = new DOMDocument('1.0', $this->getEncoding());
        $valueDOM->loadXML($value->saveXML());

        // Build response XML
        $dom  = new DOMDocument('1.0', 'ISO-8859-1');
        $r    = $dom->appendChild($dom->createElement('methodResponse'));
        $f    = $r->appendChild($dom->createElement('fault'));
        $f->appendChild($dom->importNode($valueDOM->documentElement, 1));

        return $dom->saveXML();
    }

    public function __toString()
    {
        return $this->saveXML();
    }
}
