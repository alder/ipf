<?php

class IPF_XmlRpc_Request_Http extends IPF_XmlRpc_Request
{
    protected $_headers;
    protected $_xml;
    public function __construct()
    {
        $fh = fopen('php://input', 'r');
        if (!$fh) {
            $this->_fault = new IPF_Exception(630);
            return;
        }

        $xml = '';
        while (!feof($fh)) {
            $xml .= fgets($fh);
        }
        fclose($fh);

        $this->_xml = $xml;

        $this->loadXml($xml);
    }

    public function getRawRequest()
    {
        return $this->_xml;
    }

    public function getHeaders()
    {
        if (null === $this->_headers) {
            $this->_headers = array();
            foreach ($_SERVER as $key => $value) {
                if ('HTTP_' == substr($key, 0, 5)) {
                    $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $this->_headers[$header] = $value;
                }
            }
        }

        return $this->_headers;
    }

    public function getFullRequest()
    {
        $request = '';
        foreach ($this->getHeaders() as $key => $value) {
            $request .= $key . ': ' . $value . "\n";
        }

        $request .= $this->_xml;

        return $request;
    }
}
