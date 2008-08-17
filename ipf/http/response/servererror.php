<?php

class IPF_HTTP_Response_ServerError extends IPF_HTTP_Response
{
    function __construct($content='Server Error', $mimetype=null)
    {
        parent::__construct($content, $mimetype);
        $this->status_code = 500;
    }
}
