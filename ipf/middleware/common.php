<?php

class IPF_Middleware_Common
{
    function processRequest(&$request)
    {
        if (IPF::get('append_slash', true)) {
            $url = $request->absoluteUrl();
            if (substr($url, -1) !== '/')
                return new IPF_HTTP_Response_Redirect($url.'/');
        }
        return false;
    }
}

