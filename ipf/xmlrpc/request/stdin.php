<?php

class IPF_XmlRpc_Request_Stdin extends IPF_XmlRpc_Request
{
    protected $_xml;

    public function __construct()
    {
        $fh = fopen('php://stdin', 'r');
        if (!$fh) {
            $this->_fault = new IPF_XmlRpc_Fault(630);
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
}
