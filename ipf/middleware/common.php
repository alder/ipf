<?php

class IPF_Middleware_Common
{
    function processRequest(&$request)
    {
        if (IPF::get('append_slash', true)) {
            $url = $request->http_host . IPF_HTTP_URL::getAction();
            if (substr($url,-1)!='/') {
                $url = $request->addUrlrotocol($url).'/';
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        return false;
    }
}

