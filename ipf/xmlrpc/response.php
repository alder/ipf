<?php

class IPF_XmlRpc_Response
{
    protected $_return;
    protected $_type;
    protected $_encoding = 'UTF-8';
    protected $_fault = null;

    public function __construct($return = null, $type = null)
    {
        $this->setReturnValue($return, $type);
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

    public function setReturnValue($value, $type = null)
    {
        $this->_return = $value;
        $this->_type = (string) $type;
    }

    public function getReturnValue()
    {
        return $this->_return;
    }

    protected function _getXmlRpcReturn()
    {
        return IPF_XmlRpc_Value::getXmlRpcValue($this->_return);
    }

    public function isFault()
    {
        return $this->_fault instanceof IPF_XmlRpc_Fault;
    }

    public function getFault()
    {
        return $this->_fault;
    }

    public function loadXml($response)
    {
        if (!is_string($response)) {
            $this->_fault = new IPF_XmlRpc_Fault(650);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            $xml = @new SimpleXMLElement($response);
        } catch (Exception $e) {
            // Not valid XML
            $this->_fault = new IPF_XmlRpc_Fault(651);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        if (!empty($xml->fault)) {
            // fault response
            $this->_fault = new IPF_XmlRpc_Fault();
            $this->_fault->setEncoding($this->getEncoding());
            $this->_fault->loadXml($response);
            return false;
        }

        if (empty($xml->params)) {
            // Invalid response
            $this->_fault = new IPF_XmlRpc_Fault(652);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            if (!isset($xml->params) || !isset($xml->params->param) || !isset($xml->params->param->value)) {
                throw new IPF_Exception('Missing XML-RPC value in XML');
            }
            $valueXml = $xml->params->param->value->asXML();
            $valueXml = preg_replace('/<\?xml version=.*?\?>/i', '', $valueXml);
            $value = IPF_XmlRpc_Value::getXmlRpcValue(trim($valueXml), IPF_XmlRpc_Value::XML_STRING);
        } catch (IPF_Exception $e) {
            $this->_fault = new IPF_XmlRpc_Fault(653);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->setReturnValue($value->getValue());
        return true;
    }

    public function saveXML()
    {
        $value = $this->_getXmlRpcReturn();
        $valueDOM = new DOMDocument('1.0', $this->getEncoding());
        $valueDOM->loadXML($value->saveXML());

        $dom      = new DOMDocument('1.0', $this->getEncoding());
        $response = $dom->appendChild($dom->createElement('methodResponse'));
        $params   = $response->appendChild($dom->createElement('params'));
        $param    = $params->appendChild($dom->createElement('param'));

        $param->appendChild($dom->importNode($valueDOM->documentElement, true));

        return $dom->saveXML();
    }

    public function __toString()
    {
        return $this->saveXML();
    }
}
