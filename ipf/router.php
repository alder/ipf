<?php

class IPF_Router
{
    public static function response500($e)
    {
        if (IPF::get('debug'))
            return new IPF_HTTP_Response_ServerErrorDebug($e);
        else
            return new IPF_HTTP_Response_ServerError($e);
    }

    public static function dispatch($query='')
    {
        try{
            $query = preg_replace('#^(/)+#', '/', '/'.$query);
            $req = new IPF_HTTP_Request($query);
            $middleware = array();
            foreach (IPF::get('middlewares', array()) as $mw) {
                $middleware[] = new $mw();
            }
            $skip = false;
            foreach ($middleware as $mw) {
                if (method_exists($mw, 'processRequest')) {
                    $response = $mw->processRequest($req);
                    if ($response !== false) {
                        // $response is a response
                        $response->render($req->method != 'HEAD' and !defined('IN_UNIT_TESTS'));
                        $skip = true;
                        break;
                    }    
                }
            }
            if ($skip === false) {   
                $response = IPF_Router::match($req);
                if (!empty($req->response_vary_on)) {
                    $response->headers['Vary'] = $req->response_vary_on;
                }
                $middleware = array_reverse($middleware);
                foreach ($middleware as $mw) {
                    if (method_exists($mw, 'processResponse')) {
                        $response = $mw->processResponse($req, $response);
                    }    
                }
                //var_dump($response);
                $response->render($req->method != 'HEAD');
            }
            return array($req, $response);
        } catch (IPF_Exception $e) {
            $response = IPF_Router::response500($e);
            $response->render();
        }
   }

    public static function match($req)
    {
        foreach (IPF::get('urls') as $url) {
            $prefix = $url['prefix'];
            foreach ($url['urls'] as $suburl){
                $regex = $prefix.$suburl['regex'];
                $match = array();
                if (preg_match($regex, $req->query, $match)) {
                    try{
                        IPF::loadFunction($suburl['func']);
                        $r = $suburl['func']($req, $match);
                        if (!is_a($r,'IPF_HTTP_Response')){
                            return IPF_Router::response500(new IPF_Exception('function '.$suburl['func'].'() must return IPF_HTTP_Response instance'));
                        }
                        return $r;
                    } catch (IPF_HTTP_Error404 $e) {
                        return new IPF_HTTP_Response_NotFound();
                    } catch (IPF_Exception $e) {
                        return IPF_Router::response500($e);
                    }
                }
            }
        }
        return new IPF_HTTP_Response_NotFound();
    }

    public static function describe()
    {
        $routes = array();
        foreach (IPF::get('urls') as $url) {
            $prefix = $url['prefix'];
            foreach ($url['urls'] as $suburl) {
                $routes[] = array(
                    $prefix . $suburl['regex'],
                    $suburl['func'],
                );
            }
        }
        return $routes;
    }
}

