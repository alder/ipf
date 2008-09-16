<?php

class IPF_HTTP_Response_ServerError extends IPF_HTTP_Response
{
    function __construct($e, $mimetype=null)
    {
        parent::__construct('<h1>500 Server Error</h1><p>Our apologies…</p><p>Please return later</p>', $mimetype);
        $this->status_code = 500;
    }
}
