<?php

abstract class IPF_Mail_Protocol_Abstract
{
    const EOL = "\r\n";
    const TIMEOUT_CONNECTION = 30;

    protected $_host;
    protected $_port;
    protected $_socket;
    protected $_request;
    protected $_response;
    protected $_template = '%d%s';
    private $_log;

    public function __construct($host = '127.0.0.1', $port = null)
    {
        $this->_host = $host;
        $this->_port = $port;
    }

    public function __destruct()
    {
        $this->_disconnect();
    }

    abstract public function connect();
    public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function getLog()
    {
        return $this->_log;
    }

    public function resetLog()
    {
        $this->_log = '';
    }

    protected function _connect($remote)
    {
        $errorNum = 0;
        $errorStr = '';

        // open connection
        $this->_socket = stream_socket_client($remote, $errorNum, $errorStr, self::TIMEOUT_CONNECTION);

        if ($this->_socket === false) {
            if ($errorNum == 0) {
                $errorStr = 'Could not open socket';
            }
            throw new IPF_Exception_Mail($errorStr);
        }

        if (($result = stream_set_timeout($this->_socket, self::TIMEOUT_CONNECTION)) === false) {
            throw new IPF_Exception_Mail('Could not set stream timeout');
        }

        return $result;
    }

    protected function _disconnect()
    {
        if (is_resource($this->_socket)) {
            fclose($this->_socket);
        }
    }

    protected function _send($request)
    {
        if (!is_resource($this->_socket)) {
            throw new IPF_Exception_Mail('No connection has been established to ' . $this->_host);
        }

        $this->_request = $request;

        $result = fwrite($this->_socket, $request . self::EOL);

        // Save request to internal log
        $this->_log .= $request . self::EOL;

        if ($result === false) {
            throw new IPF_Exception_Mail('Could not send request to ' . $this->_host);
        }

        return $result;
    }

    protected function _receive($timeout = null)
    {
        if (!is_resource($this->_socket)) {
            throw new IPF_Exception_Mail('No connection has been established to ' . $this->_host);
        }

        // Adapters may wish to supply per-commend timeouts according to appropriate RFC
        if ($timeout !== null) {
           stream_set_timeout($this->_socket, $timeout);
        }

        // Retrieve response
        $reponse = fgets($this->_socket, 1024);

        // Save request to internal log
        $this->_log .= $reponse;

        // Check meta data to ensure connection is still valid
        $info = stream_get_meta_data($this->_socket);

        if (!empty($info['timed_out'])) {
            throw new IPF_Exception_Mail($this->_host . ' has timed out');
        }

        if ($reponse === false) {
            throw new IPF_Exception_Mail('Could not read from ' . $this->_host);
        }

        return $reponse;
    }

    protected function _expect($code, $timeout = null)
    {
        $this->_response = array();
        $cmd = '';
        $msg = '';
        if (!is_array($code)) {
            $code = array($code);
        }
        do {
            $this->_response[] = $result = $this->_receive($timeout);
            sscanf($result, $this->_template, $cmd, $msg);

            if ($cmd === null || !in_array($cmd, $code)) {
                throw new IPF_Exception_Mail($result);
            }

        } while (strpos($msg, '-') === 0); // The '-' message prefix indicates an information string instead of a response string.
        return $msg;
    }
}
