<?php

class IPF_HTTP_Response
{
    public $short_session = false;
	public $content = '';
    public $headers = array();
    public $status_code = 200;
    public $cookies = array();
    public $status_code_list = array(
                                     '100' => 'CONTINUE',
                                     '101' => 'SWITCHING PROTOCOLS',
                                     '200' => 'OK',
                                     '201' => 'CREATED',
                                     '202' => 'ACCEPTED',
                                     '203' => 'NON-AUTHORITATIVE INFORMATION',
                                     '204' => 'NO CONTENT',
                                     '205' => 'RESET CONTENT',
                                     '206' => 'PARTIAL CONTENT',
                                     '300' => 'MULTIPLE CHOICES',
                                     '301' => 'MOVED PERMANENTLY',
                                     '302' => 'FOUND',
                                     '303' => 'SEE OTHER',
                                     '304' => 'NOT MODIFIED',
                                     '305' => 'USE PROXY',
                                     '306' => 'RESERVED',
                                     '307' => 'TEMPORARY REDIRECT',
                                     '400' => 'BAD REQUEST',
                                     '401' => 'UNAUTHORIZED',
                                     '402' => 'PAYMENT REQUIRED',
                                     '403' => 'FORBIDDEN',
                                     '404' => 'NOT FOUND',
                                     '405' => 'METHOD NOT ALLOWED',
                                     '406' => 'NOT ACCEPTABLE',
                                     '407' => 'PROXY AUTHENTICATION REQUIRED',
                                     '408' => 'REQUEST TIMEOUT',
                                     '409' => 'CONFLICT',
                                     '410' => 'GONE',
                                     '411' => 'LENGTH REQUIRED',
                                     '412' => 'PRECONDITION FAILED',
                                     '413' => 'REQUEST ENTITY TOO LARGE',
                                     '414' => 'REQUEST-URI TOO LONG',
                                     '415' => 'UNSUPPORTED MEDIA TYPE',
                                     '416' => 'REQUESTED RANGE NOT SATISFIABLE',
                                     '417' => 'EXPECTATION FAILED',
                                     '500' => 'INTERNAL SERVER ERROR',
                                     '501' => 'NOT IMPLEMENTED',
                                     '502' => 'BAD GATEWAY',
                                     '503' => 'SERVICE UNAVAILABLE',
                                     '504' => 'GATEWAY TIMEOUT',
                                     '505' => 'HTTP VERSION NOT SUPPORTED'
                                     );

    function __construct($content='', $mimetype=null)
    {
        if (is_null($mimetype)) {
            $mimetype = IPF::get('mimetype', 'text/html').'; charset=utf-8';
        }
        $this->content = $content;
        $this->headers['Content-Type'] = $mimetype;
        $this->headers['X-Powered-By'] = 'IPF - http://ipf.icmconsulting.com/';
        $this->status_code = 200;
        $this->cookies = array();
    }

    function render($output_body=true)
    {
        if ($this->status_code >= 200 
            && $this->status_code != 204 
            && $this->status_code != 304) {
            $this->headers['Content-Length'] = strlen($this->content);
        }
        $this->outputHeaders();
        if ($output_body) {
            echo($this->content);
        }
    }

    function outputHeaders()
    {
        if (!defined('IN_UNIT_TESTS')) {
            header('HTTP/1.1 '.$this->status_code.' '
                   .$this->status_code_list[$this->status_code],
                   true, $this->status_code);
            foreach ($this->headers as $header => $ch) {
                header($header.': '.$ch);
            }
            if ($this->short_session)
            	$exp = 0;
            else
            	$exp = time()+31536000; 
            foreach ($this->cookies as $cookie => $data) {
                // name, data, expiration, path, domain, secure, http only
                setcookie($cookie, $data, 
                          $exp, 
                          IPF::get('cookie_path', '/'), 
                          IPF::get('cookie_domain', null), 
                          IPF::get('cookie_secure', false), 
                          IPF::get('cookie_httponly', true)); 
            }
        } else {
            $_COOKIE = array();
            foreach ($this->cookies as $cookie => $data) {
                $_COOKIE[$cookie] = $data;
            }
        }
    }
}
