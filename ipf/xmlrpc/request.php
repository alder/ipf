<?php

class IPF_XmlRpc_Request
{
    protected $_encoding = 'UTF-8';
    protected $_method;
    protected $_xml;
    protected $_params = array();
    protected $_fault = null;
    protected $_types = array();
    protected $_xmlRpcParams = array();

    public function __construct($method = null, $params = null)
    {
        if ($method !== null) {
            $this->setMethod($method);
        }

        if ($params !== null) {
            $this->setParams($params);
        }
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

    public function setMethod($method)
    {
        if (!is_string($method) || !preg_match('/^[a-z0-9_.:\/]+$/i', $method)) {
            $this->_fault = new IPF_XmlRpc_Fault(634, 'Invalid method name ("' . $method . '")');
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->_method = $method;
        return true;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function addParam($value, $type = null)
    {
        $this->_params[] = $value;
        if (null === $type) {
            // Detect type if not provided explicitly
            if ($value instanceof IPF_XmlRpc_Value) {
                $type = $value->getType();
            } else {
                $xmlRpcValue = IPF_XmlRpc_Value::getXmlRpcValue($value);
                $type        = $xmlRpcValue->getType();
            }
        }
        $this->_types[]  = $type;
        $this->_xmlRpcParams[] = array('value' => $value, 'type' => $type);
    }

    public function setParams()
    {
        $argc = func_num_args();
        $argv = func_get_args();
        if (0 == $argc) {
            return;
        }

        if ((1 == $argc) && is_array($argv[0])) {
            $params     = array();
            $types      = array();
            $wellFormed = true;
            foreach ($argv[0] as $arg) {
                if (!is_array($arg) || !isset($arg['value'])) {
                    $wellFormed = false;
                    break;
                }
                $params[] = $arg['value'];

                if (!isset($arg['type'])) {
                    $xmlRpcValue = IPF_XmlRpc_Value::getXmlRpcValue($arg['value']);
                    $arg['type'] = $xmlRpcValue->getType();
                }
                $types[] = $arg['type'];
            }
            if ($wellFormed) {
                $this->_xmlRpcParams = $argv[0];
                $this->_params = $params;
                $this->_types  = $types;
            } else {
                $this->_params = $argv[0];
                $this->_types  = array();
                $xmlRpcParams  = array();
                foreach ($argv[0] as $arg) {
                    if ($arg instanceof IPF_XmlRpc_Value) {
                        $type = $arg->getType();
                    } else {
                        $xmlRpcValue = IPF_XmlRpc_Value::getXmlRpcValue($arg);
                        $type        = $xmlRpcValue->getType();
                    }
                    $xmlRpcParams[] = array('value' => $arg, 'type' => $type);
                    $this->_types[] = $type;
                }
                $this->_xmlRpcParams = $xmlRpcParams;
            }
            return;
        }

        $this->_params = $argv;
        $this->_types  = array();
        $xmlRpcParams  = array();
        foreach ($argv as $arg) {
            if ($arg instanceof IPF_XmlRpc_Value) {
                $type = $arg->getType();
            } else {
                $xmlRpcValue = IPF_XmlRpc_Value::getXmlRpcValue($arg);
                $type        = $xmlRpcValue->getType();
            }
            $xmlRpcParams[] = array('value' => $arg, 'type' => $type);
            $this->_types[] = $type;
        }
        $this->_xmlRpcParams = $xmlRpcParams;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getTypes()
    {
        return $this->_types;
    }

    public function loadXml($request)
    {
        if (!is_string($request)) {
            $this->_fault = new IPF_XmlRpc_Fault(635);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            $xml = @new SimpleXMLElement($request);
        } catch (Exception $e) {
            // Not valid XML
            $this->_fault = new IPF_XmlRpc_Fault(631);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        // Check for method name
        if (empty($xml->methodName)) {
            // Missing method name
            $this->_fault = new IPF_XmlRpc_Fault(632);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->_method = (string) $xml->methodName;

        // Check for parameters
        if (!empty($xml->params)) {
            $types = array();
            $argv  = array();
            foreach ($xml->params->children() as $param) {
                if (! $param->value instanceof SimpleXMLElement) {
                    $this->_fault = new IPF_XmlRpc_Fault(633);
                    $this->_fault->setEncoding($this->getEncoding());
                    return false;
                }

                try {
                    $param   = IPF_XmlRpc_Value::getXmlRpcValue($param->value, IPF_XmlRpc_Value::XML_STRING);
                    $types[] = $param->getType();
                    $argv[]  = $param->getValue();
                } catch (Exception $e) {
                    $this->_fault = new IPF_XmlRpc_Fault(636);
                    $this->_fault->setEncoding($this->getEncoding());
                    return false;
                }
            }

            $this->_types  = $types;
            $this->_params = $argv;
        }

        $this->_xml = $request;

        return true;
    }

    public function isFault()
    {
        return $this->_fault instanceof IPF_XmlRpc_Fault;
    }

    public function getFault()
    {
        return $this->_fault;
    }

    protected function _getXmlRpcParams()
    {
        $params = array();
        if (is_array($this->_xmlRpcParams)) {
            foreach ($this->_xmlRpcParams as $param) {
                $value = $param['value'];
                $type  = isset($param['type']) ? $param['type'] : IPF_XmlRpc_Value::AUTO_DETECT_TYPE;

                if (!$value instanceof IPF_XmlRpc_Value) {
                    $value = IPF_XmlRpc_Value::getXmlRpcValue($value, $type);
                }
                $params[] = $value;
            }
        }

        return $params;
    }

    public function saveXML()
    {
        $args   = $this->_getXmlRpcParams();
        $method = $this->getMethod();

        $dom = new DOMDocument('1.0', $this->getEncoding());
        $mCall = $dom->appendChild($dom->createElement('methodCall'));
        $mName = $mCall->appendChild($dom->createElement('methodName', $method));

        if (is_array($args) && count($args)) {
            $params = $mCall->appendChild($dom->createElement('params'));

            foreach ($args as $arg) {
                /* @var $arg IPF_XmlRpc_Value */
                $argDOM = new DOMDocument('1.0', $this->getEncoding());
                $argDOM->loadXML($arg->saveXML());

                $param = $params->appendChild($dom->createElement('param'));
                $param->appendChild($dom->importNode($argDOM->documentElement, 1));
            }
        }

        return $dom->saveXML();
    }

    public function __toString()
    {
        return $this->saveXML();
    }
}
