<?php

class IPF_HTTP_Request
{
    public $POST = array();
    public $GET = array();
    public $REQUEST = array();
    public $COOKIE = array();
    public $FILES = array();
    public $query = '';
    public $method = '';
    public $uri = '';
    public $view = '';
    public $remote_addr = '';
    public $http_host = '';
    public $SERVER = array();
    public $is_secure = false;

    public function __construct()
    {
        $this->POST =& $_POST;
        $this->GET =& $_GET;
        $this->REQUEST =& $_REQUEST;
        $this->COOKIE =& $_COOKIE;
        $this->FILES =& $_FILES;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->remote_addr = $_SERVER['REMOTE_ADDR'];
        $this->http_host = $_SERVER['HTTP_HOST'];

        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $pq = strpos($uri,'?');
            if ($pq !== false)
                $uri = substr($uri, 0, $pq);
            $this->query = preg_replace('#^(//+)#', '/', '/'.$uri);
        } else {
            $this->query = '/';
        }

        if (isset($_SERVER['PATH_INFO']))
            $this->path_info = $_SERVER['PATH_INFO'];
        else
            $this->path_info = '/';
        
        $this->SERVER =& $_SERVER;
        $this->is_secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    public function absoluteUrl()
    {
        return ($this->is_secure ? 'https://' : 'http://') . $this->http_host . $this->query;
    }
}

