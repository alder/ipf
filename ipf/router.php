<?php

class IPF_Router
{
    private $routes = array();

    public function __construct()
    {
        foreach (IPF::get('urls') as $url) {
            $prefix = $url['prefix'];
            foreach ($url['urls'] as $suburl) {
                if (isset($suburl['regex']))
                    $matcher = new IPF_Router_RegexMatch($prefix . $suburl['regex']);
                elseif (isset($suburl['expr']))
                    $matcher = RouteExpression::compile($prefix . $suburl['expr']);
                else
                    throw new IPF_Exception('Unsupported route type');

                $this->routes[] = array(
                    $matcher,
                    $suburl['func'],
                );
            }
        }
    }

    public static function response500($e)
    {
        error_log($e);
        if (IPF::get('debug'))
            return new IPF_HTTP_Response_ServerErrorDebug($e);
        else
            return new IPF_HTTP_Response_ServerError($e);
    }

    public function dispatch($query='')
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
                $response = $this->match($req);
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
            $response = self::response500($e);
            $response->render();
        }
   }

    public function match($req)
    {
        $func = null;
        foreach ($this->routes as $route) {
            $match = array();
            if ($route[0]->match($req->query, $match)) {
                $func = $route[1];
                break;
            }
        }

        if ($func) {
            try {
                IPF::loadFunction($func);
                $r = $func($req, $match);
                if (!is_a($r, 'IPF_HTTP_Response')) {
                    return self::response500(new IPF_Exception('function '.$func.'() must return IPF_HTTP_Response instance'));
                }
                return $r;
            } catch (IPF_HTTP_Error404 $e) {
                return new IPF_HTTP_Response_NotFound($req);
            } catch (IPF_Exception $e) {
                return self::response500($e);
            }
        }

        return new IPF_HTTP_Response_NotFound($req);
    }

    public function describe()
    {
        $result = array();
        foreach ($this->routes as $route) {
            $result[] = array(
                (string)$route[0],
                $route[1],
            );
        }
        return $result;
    }

    public function reverse($view, $params=array())
    { 
        foreach ($this->routes as $route)
            if ($route[1] == $view)
                return IPF::get('app_base') . $route[0]->reverse($params);
        throw new IPF_Exception('Error, the view: '.$view.' has not been found.');
    }
}

class IPF_Router_RegexMatch
{
    private $regex;

    public function __construct($regex)
    {
        $this->regex = $regex;
    }

    public function __toString()
    {
        return $this->regex;
    }

    public function match($query, &$matches)
    {
        return preg_match($this->regex, $query, $matches);
    }

    public function reverse($params)
    {
        $url_regex = str_replace('\\.', '.', $this->regex);
        $url_regex = str_replace('\\-', '-', $url_regex);
        $url = $url_regex;
        $groups = '#\(([^)]+)\)#';
        $matches = array();
        preg_match_all($groups, $url_regex, $matches);
        reset($params);
        if (count($matches[0]) && count($matches[0]) == count($params)) {
            // Test the params against the pattern
            foreach ($matches[0] as $pattern) {
                $in = current($params);
                if (0 === preg_match('#'.$pattern.'#', $in)) {
                    throw new IPF_Exception('Error, param: '.$in.' is not matching the pattern: '.$pattern);
                }
                next($params);
            }
            $func = create_function('$matches', 
                                    'static $p = '.var_export($params, true).'; '.
                                    '$a = current($p); '.
                                    'next($p); '.
                                    'return $a;');
            $url = preg_replace_callback($groups, $func, $url_regex);
        }
        $url = substr(substr($url, 2), 0, -2);
        if (substr($url, -1) !== '$') {
            return $url;
        }
        return substr($url, 0, -1);
    }
}

