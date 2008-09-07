<?php

class IPF_Mail_Protocol_Smtp_Auth_Login extends IPF_Mail_Protocol_Smtp
{
    protected $_username;
    protected $_password;

    public function __construct($host = '127.0.0.1', $port = null, $config = null)
    {
        if (is_array($config)) {
            if (isset($config['username'])) {
                $this->_username = $config['username'];
            }
            if (isset($config['password'])) {
                $this->_password = $config['password'];
            }
        }

        parent::__construct($host, $port, $config);
    }

    public function auth()
    {
        // Ensure AUTH has not already been initiated.
        parent::auth();

        $this->_send('AUTH LOGIN');
        $this->_expect(334);
        $this->_send(base64_encode($this->_username));
        $this->_expect(334);
        $this->_send(base64_encode($this->_password));
        $this->_expect(235);
        $this->_auth = true;
    }
}
