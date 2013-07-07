<?php

class IPF_HTTP_Response_NotFound extends IPF_HTTP_Response
{
    function __construct($request=null)
    {
        try {
            $context = array(
                'title' => '404 Not Found',
                'query_string' => @$_SERVER['QUERY_STRING'],
                'MEDIA_URL' => IPF::get('media_url'),
            );
            $content = IPF_Shortcuts::RenderToString('404.html', $context, $request);
        } catch (IPF_Exception $e) {
            $content = '404 Not Found';
        }
        parent::__construct($content);
        $this->status_code = 404;
    }
}

