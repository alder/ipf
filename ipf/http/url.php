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

    public static function urlForView($view, $params=array(), $get_params=array(), $encoded=true)
    {
        $action = IPF_Project::getInstance()->router->reverse($view, $params);
        return self::generate($action, $get_params, $encoded);
    }
}

