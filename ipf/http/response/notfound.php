<?php

class IPF_HTTP_Response_NotFound extends IPF_HTTP_Response
{
    function __construct($content='404 Not Found', $mimetype=null)
    {
        try{
            $context = array(
                'title'=>'404 Not Found',
                'query_string'=>$_SERVER['QUERY_STRING'],
                'MEDIA_URL'=>IPF::get('media_url'),
                'ADMIN_MEDIA_URL'=>IPF::get('admin_media_url'),
            );
            $content = IPF_Shortcuts::RenderToString('404.html', $context);
        }catch(IPF_Exception $e){}
        parent::__construct($content, $mimetype);
        $this->status_code = 404;
    }
}
