<?php

class IPF_Mail_Transport_Sendmail extends IPF_Mail_Transport_Abstract
{
    public $subject = null;
    public $parameters;
    public $EOL = PHP_EOL;

    public function __construct($parameters = null)
    {
        $this->parameters = $parameters;
    }

    public function _sendMail()
    {
        if ($this->parameters === null) {
            $result = mail(
                $this->recipients,
                $this->_mail->getSubject(),
                $this->body,
                $this->header);
        } else {
            $result = mail(
                $this->recipients,
                $this->_mail->getSubject(),
                $this->body,
                $this->header,
                $this->parameters);
        }
        if (!$result) {
            throw new IPF_Exception_Mail('Unable to send mail');
        }
    }

    protected function _prepareHeaders($headers)
    {
        if (!$this->_mail) {
            throw new IPF_Exception_Mail('_prepareHeaders requires a registered IPF_Mail object');
        }

        // mail() uses its $to parameter to set the To: header, and the $subject
        // parameter to set the Subject: header. We need to strip them out.
        if (0 === strpos(PHP_OS, 'WIN')) {
            // If the current recipients list is empty, throw an error
            if (empty($this->recipients)) {
                throw new IPF_Exception_Mail('Missing To addresses');
            }
        } else {
            // All others, simply grab the recipients and unset the To: header
            if (!isset($headers['To'])) {
                throw new IPF_Exception_Mail('Missing To header');
            }

            unset($headers['To']['append']);
            $this->recipients = implode(',', $headers['To']);
        }

        // Remove recipient header
        unset($headers['To']);

        // Remove subject header, if present
        if (isset($headers['Subject'])) {
            unset($headers['Subject']);
        }

        // Prepare headers
        parent::_prepareHeaders($headers);
    }

}

