<?php

class IPF_HTTP_URL
{
    public static function generate($action, $params=array(), $encode=true)
    {
        if ($encode) {
            $amp = '&amp;';
        } else {
            $amp = '&';
        }
        $url = $action;
        if (count($params) > 0) {
            $url .= '?';
            $params_list = array();
            foreach ($params as $key=>$value) {
                $params_list[] = urlencode($key).'='.urlencode($value);
            }
            $url .= implode($amp, $params_list);
        }
        return $url;
    }

    public static function getAction()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $pq = strpos($uri,'?');
            if ($pq!==false)
                $uri = substr($uri,0,$pq);
            return $uri;
        }
        return '/';
    }
}

function IPF_HTTP_URL_urlForView($view, $params=array(), $by_name=false, 
                                  $get_params=array(), $encoded=true)
{
    $action = IPF_HTTP_URL_reverse($view, $params, $by_name);
    return IPF_HTTP_URL::generate($action, $get_params, $encoded);
}

function IPF_HTTP_URL_reverse($view, $params=array(), $by_name=false)
{
    $regex = null;
    
    foreach (IPF::get('urls') as $url) {
        $prefix = $url['prefix'];
        foreach ($url['urls'] as $suburl){
            if ($suburl['func']==$view){
                $regex = $prefix.$suburl['regex'];
                break;
            }
        }
        if ($regex!==null)
            break;
    }
    if ($regex === null) {
        throw new IPF_Exception('Error, the view: '.$view.' has not been found.');
    }
    $url = IPF_HTTP_URL_buildReverseUrl($regex, $params);
    return IPF::get('app_base').$url;
}

function IPF_HTTP_URL_buildReverseUrl($url_regex, $params=array())
{
    $url_regex = str_replace('\\.', '.', $url_regex);
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
