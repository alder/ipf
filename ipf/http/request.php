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

    function __construct($query)
    {
        $http = new IPF_HTTP();
        $http->removeTheMagic();
        $this->POST =& $_POST;
        $this->GET =& $_GET;
        $this->REQUEST =& $_REQUEST;
        $this->COOKIE =& $_COOKIE;
        $this->FILES =& $_FILES;
        $this->query = $query;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->remote_addr = $_SERVER['REMOTE_ADDR'];
        $this->http_host = $_SERVER['HTTP_HOST'];
        $this->SERVER =& $_SERVER;
    }
}
