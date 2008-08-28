<?php

class IPF_Middleware_Common
{
    function processRequest(&$request)
    {
        //print_r($request);
        if (IPF::get('append_slash')){
            $url = $request->http_host.$request->path_info;
            if (substr($url,-1)!='/'){
                $url = $request->addUrlrotocol($url).'/';
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        return false;
    }
}

