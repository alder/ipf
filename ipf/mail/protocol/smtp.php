<?php

class IPF_Mail_Protocol_Smtp extends IPF_Mail_Protocol_Abstract
{
    protected $_transport = 'tcp';
    protected $_secure;
    protected $_sess = false;
    protected $_helo = false;
    protected $_auth = false;
    protected $_mail = false;
    protected $_rcpt = false;
    protected $_data = null;

    public function __construct($host = '127.0.0.1', $port = null, $config = array())
    {
        if (isset($config['ssl'])) {
            switch (strtolower($config['ssl'])) {
                case 'tls':
                    $this->_secure = 'tls';
                    break;

                case 'ssl':
                    $this->_transport = 'ssl';
                    $this->_secure = 'ssl';
                    if ($port == null) {
                        $port = 465;
                    }
                    break;

                default:
                    throw new IPF_Exception_Mail($config['ssl'] . ' is unsupported SSL type');
                    break;
            }
        }

        // If no port has been specified then check the master PHP ini file. Defaults to 25 if the ini setting is null.
        if ($port == null) {
            if (($port = ini_get('smtp_port')) == '') {
                $port = 25;
            }
        }

        parent::__construct($host, $port);
    }


    public function connect()
    {
        return $this->_connect($this->_transport . '://' . $this->_host . ':'. $this->_port);
    }

    public function helo($host = '127.0.0.1')
    {
        // Respect RFC 2821 and disallow HELO attempts if session is already initiated.
        if ($this->_sess === true) {
            throw new IPF_Exception_Mail('Cannot issue HELO to existing session');
        }

        // Validate client hostname
        if (!$this->_validHost->isValid($host)) {
            throw new IPF_Exception_Mail(join(', ', $this->_validHost->getMessage()));
        }

        // Initiate helo sequence
        $this->_expect(220, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        $this->_ehlo($host);

        // If a TLS session is required, commence negotiation
        if ($this->_secure == 'tls') {
            $this->_send('STARTTLS');
            $this->_expect(220, 180);
            if (!stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new IPF_Exception_Mail('Unable to connect via TLS');
            }
            $this->_ehlo($host);
        }

        $this->_startSession();
        $this->auth();
    }

    protected function _ehlo($host)
    {
        // Support for older, less-compliant remote servers. Tries multiple attempts of EHLO or HELO.
        try {
            $this->_send('EHLO ' . $host);
            $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        } catch (IPF_Exception_Mail $e) {
            $this->_send('HELO ' . $host);
            $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        } catch (IPF_Exception_Mail $e) {
            throw $e;
        }
    }

    public function mail($from)
    {
        if ($this->_sess !== true) {
            throw new IPF_Exception_Mail('A valid session has not been started');
        }

        $this->_send('MAIL FROM:<' . $from . '>');
        $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2

        // Set mail to true, clear recipients and any existing data flags as per 4.1.1.2 of RFC 2821
        $this->_mail = true;
        $this->_rcpt = false;
        $this->_data = false;
    }

    public function rcpt($to)
    {
        if ($this->_mail !== true) {
            throw new IPF_Exception_Mail('No sender reverse path has been supplied');
        }

        // Set rcpt to true, as per 4.1.1.3 of RFC 2821
        $this->_send('RCPT TO:<' . $to . '>');
        $this->_expect(array(250, 251), 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        $this->_rcpt = true;
    }

    public function data($data)
    {
        // Ensure recipients have been set
        if ($this->_rcpt !== true) {
            throw new IPF_Exception_Mail('No recipient forward path has been supplied');
        }

        $this->_send('DATA');
        $this->_expect(354, 120); // Timeout set for 2 minutes as per RFC 2821 4.5.3.2

        foreach (explode(IPF_Mime::LINEEND, $data) as $line) {
            if (strpos($line, '.') === 0) {
                // Escape lines prefixed with a '.'
                $line = '.' . $line;
            }
            $this->_send($line);
        }

        $this->_send('.');
        $this->_expect(250, 600); // Timeout set for 10 minutes as per RFC 2821 4.5.3.2
        $this->_data = true;
    }

    public function rset()
    {
        $this->_send('RSET');
        // MS ESMTP doesn't follow RFC, see [ZF-1377]
        $this->_expect(array(250, 220));

        $this->_mail = false;
        $this->_rcpt = false;
        $this->_data = false;
    }

    public function noop()
    {
        $this->_send('NOOP');
        $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
    }

    public function vrfy($user)
    {
        $this->_send('VRFY ' . $user);
        $this->_expect(array(250, 251, 252), 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
    }

    public function quit()
    {
        if ($this->_sess) {
            $this->_send('QUIT');
            $this->_expect(221, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
            $this->_stopSession();
        }
    }

    public function auth()
    {
        if ($this->_auth === true) {
            throw new IPF_Exception_Mail('Already authenticated for this session');
        }
    }

    public function disconnect()
    {
        $this->_disconnect();
    }

    protected function _startSession()
    {
        $this->_sess = true;
    }

    protected function _stopSession()
    {
        $this->_sess = false;
    }
}
