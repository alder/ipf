<?php

class IPF_Mail_Transport_Smtp extends IPF_Mail_Transport_Abstract
{
    public $EOL = "\n";
    protected $_host;
    protected $_port;
    protected $_name = 'localhost';
    protected $_auth;
    protected $_config;
    protected $_connection;

    public function __construct($host = '127.0.0.1', $config = array())
    {
        if (isset($config['name'])) {
            $this->_name = $config['name'];
        }
        if (isset($config['port'])) {
            $this->_port = $config['port'];
        }
        if (isset($config['auth'])) {
            $this->_auth = $config['auth'];
        }

        $this->_host = $host;
        $this->_config = $config;
    }

    public function __destruct()
    {
        if ($this->_connection instanceof IPF_Mail_Protocol_Smtp) {
            try {
                $this->_connection->quit();
            } catch (IPF_Exception_Mail $e) {
            	// ignore
            }
            $this->_connection->disconnect();
        }
    }

    public function setConnection(IPF_Mail_Protocol_Abstract $connection)
    {
        $this->_connection = $connection;
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    public function _sendMail()
    {
        // If sending multiple messages per session use existing adapter
        if (!($this->_connection instanceof IPF_Mail_Protocol_Smtp)) {
            // Check if authentication is required and determine required class
            $connectionClass = 'IPF_Mail_Protocol_Smtp';
            if ($this->_auth) {
                $connectionClass .= '_Auth_' . ucwords($this->_auth);
            }
            $this->setConnection(new $connectionClass($this->_host, $this->_port, $this->_config));
            $this->_connection->connect();
            $this->_connection->helo($this->_name);
        } else {
            // Reset connection to ensure reliable transaction
            $this->_connection->rset();
        }

        // Set mail return path from sender email address
        $this->_connection->mail($this->_mail->getReturnPath());

        // Set recipient forward paths
        foreach ($this->_mail->getRecipients() as $recipient) {
            $this->_connection->rcpt($recipient);
        }

        // Issue DATA command to client
        $this->_connection->data($this->header . IPF_Mime::LINEEND . $this->body);
    }

    protected function _prepareHeaders($headers)
    {
        if (!$this->_mail) {
            throw new IPF_Exception_Mail('_prepareHeaders requires a registered IPF_Mail object');
        }

        unset($headers['Bcc']);

        // Prepare headers
        parent::_prepareHeaders($headers);
    }
}
