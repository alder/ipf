<?php

class IPF_Mail_Protocol_Pop3
{
    public $hasTop = null;
    protected $_socket;
    protected $_timestamp;

    public function __construct($host = '', $port = null, $ssl = false)
    {
        if ($host) {
            $this->connect($host, $port, $ssl);
        }
    }

    public function __destruct()
    {
        $this->logout();
    }

    public function connect($host, $port = null, $ssl = false)
    {
        if ($ssl == 'SSL') {
            $host = 'ssl://' . $host;
        }

        if ($port === null) {
            $port = $ssl == 'SSL' ? 995 : 110;
        }

        $this->_socket = @fsockopen($host, $port);
        if (!$this->_socket) {
            throw new IPF_Exception_Mail('cannot connect to host');
        }

        $welcome = $this->readResponse();

        strtok($welcome, '<');
        $this->_timestamp = strtok('>');
        if (!strpos($this->_timestamp, '@')) {
            $this->_timestamp = null;
        } else {
            $this->_timestamp = '<' . $this->_timestamp . '>';
        }

        if ($ssl === 'TLS') {
            $this->request('STLS');
            $result = stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$result) {
                throw new IPF_Exception_Mail('cannot enable TLS');
            }
        }

        return $welcome;
    }

    public function sendRequest($request)
    {
        $result = @fputs($this->_socket, $request . "\r\n");
        if (!$result) {
            throw new IPF_Exception_Mail('send failed - connection closed?');
        }
    }

    public function readResponse($multiline = false)
    {
        $result = @fgets($this->_socket);
        if (!is_string($result)) {
            throw new IPF_Exception_Mail('read failed - connection closed?');
        }

        $result = trim($result);
        if (strpos($result, ' ')) {
            list($status, $message) = explode(' ', $result, 2);
        } else {
            $status = $result;
            $message = '';
        }

        if ($status != '+OK') {
            throw new IPF_Exception_Mail('last request failed');
        }

        if ($multiline) {
            $message = '';
            $line = fgets($this->_socket);
            while ($line && trim($line) != '.') {
                $message .= $line;
                $line = fgets($this->_socket);
            };
        }

        return $message;
    }

    public function request($request, $multiline = false)
    {
        $this->sendRequest($request);
        return $this->readResponse($multiline);
    }

    public function logout()
    {
        if (!$this->_socket) {
            return;
        }

        try {
            $this->request('QUIT');
        } catch (IPF_Exception_Mail $e) {
            // ignore error - we're closing the socket anyway
        }

        fclose($this->_socket);
        $this->_socket = null;
    }

    public function capa()
    {
        $result = $this->request('CAPA', true);
        return explode("\n", $result);
    }

    public function login($user, $password, $tryApop = true)
    {
        if ($tryApop && $this->_timestamp) {
            try {
                $this->request("APOP $user " . md5($this->_timestamp . $password));
                return;
            } catch (IPF_Exception_Mail $e) {
                // ignore
            }
        }

        $result = $this->request("USER $user");
        $result = $this->request("PASS $password");
    }

    public function status(&$messages, &$octets)
    {
        $messages = 0;
        $octets = 0;
        $result = $this->request('STAT');

        list($messages, $octets) = explode(' ', $result);
    }

    public function getList($msgno = null)
    {
        if ($msgno !== null) {
            $result = $this->request("LIST $msgno");

            list(, $result) = explode(' ', $result);
            return (int)$result;
        }

        $result = $this->request('LIST', true);
        $messages = array();
        $line = strtok($result, "\n");
        while ($line) {
            list($no, $size) = explode(' ', trim($line));
            $messages[(int)$no] = (int)$size;
            $line = strtok("\n");
        }

        return $messages;
    }

    public function uniqueid($msgno = null)
    {
        if ($msgno !== null) {
            $result = $this->request("UIDL $msgno");

            list(, $result) = explode(' ', $result);
            return $result;
        }

        $result = $this->request('UIDL', true);

        $result = explode("\n", $result);
        $messages = array();
        foreach ($result as $line) {
            if (!$line) {
                continue;
            }
            list($no, $id) = explode(' ', trim($line), 2);
            $messages[(int)$no] = $id;
        }

        return $messages;

    }

    public function top($msgno, $lines = 0, $fallback = false)
    {
        if ($this->hasTop === false) {
            if ($fallback) {
                return $this->retrieve($msgno);
            } else {
                throw new IPF_Exception_Mail('top not supported and no fallback wanted');
            }
        }
        $this->hasTop = true;

        $lines = (!$lines || $lines < 1) ? 0 : (int)$lines;

        try {
            $result = $this->request("TOP $msgno $lines", true);
        } catch (IPF_Exception_Mail $e) {
            $this->hasTop = false;
            if ($fallback) {
                $result = $this->retrieve($msgno);
            } else {
                throw $e;
            }
        }

        return $result;
    }

    public function retrive($msgno)
    {
        return $this->retrieve($msgno);
    }

    public function retrieve($msgno)
    {
        $result = $this->request("RETR $msgno", true);
        return $result;
    }

    public function noop()
    {
        $this->request('NOOP');
    }

    public function delete($msgno)
    {
        $this->request("DELE $msgno");
    }

    public function undelete()
    {
        $this->request('RSET');
    }
}
