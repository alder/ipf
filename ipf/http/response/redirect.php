<?php

class IPF_HTTP_Response_Redirect extends IPF_HTTP_Response
{
    function __construct($url)
    {
        $content = sprintf(__('<a href="%s">Please, click here to be redirected</a>.'), $url);
        parent::__construct($content);
        $this->headers['Location'] = $url;
        $this->status_code = 302;
    }
}
