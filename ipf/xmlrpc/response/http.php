<?php

class IPF_XmlRpc_Response_Http extends IPF_XmlRpc_Response
{
    public function __toString()
    {
        if (!headers_sent()) {
            header('Content-Type: text/xml; charset=' . strtolower($this->getEncoding()));
        }
        return parent::__toString();
    }
}
