<?php

class IPF_HTTP_Response_NotFound extends IPF_HTTP_Response
{
    function __construct($content='Not Found', $mimetype=null)
    {
        parent::__construct($content, $mimetype);
        $this->status_code = 404;
    }
}
