<?php

class IPF_Router
{
    public static function dispatch($query='')
    {
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
    }

    public static function match($req)
    {
        foreach (IPF::get('urls') as $url) {
            $prefix = $url['prefix'];
            foreach ($url['urls'] as $suburl){
                $regex = $prefix.$suburl['regex'];
                //print "Regex = $regex<br>";
                //print "Query = {$req->query}<br>";
                $match = array();
                if (preg_match($regex, $req->query, $match)) {
                    try{
                        IPF::loadFunction($suburl['func']);
                        $r = $suburl['func']($req, $match);
                        if (!is_a($r,'IPF_HTTP_Response'))
                            return new IPF_HTTP_Response_ServerError('function '.$suburl['func'].'() must return IPF_HTTP_Response instance');
                        return $r;
                    } catch (IPF_Exception $e) {
                        return new IPF_HTTP_Response_ServerErrorDebug($e);
                    }
                    /*
                    try{
                        IPF::loadFunction($suburl['func']);
                        return $suburl['func']($req, $match);
                    } catch (IPF_HTTP_Error404 $e) {
                        // Need to add a 404 error handler
                        // something like IPF::get('404_handler', 'class::method')
                    } catch (Exception $e) {
                        if (IPF::get('debug', false) == true) {
                            return new IPF_HTTP_Response_ServerErrorDebug($e);
                        } else {
                            return new IPF_HTTP_Response_ServerError($e);
                        }
                    }
                    */
                }
            }
        }
        return new IPF_HTTP_Response_NotFound();
    }
}

