<?php

abstract class IPF_Mail_Transport_Abstract 
{
    public $body = '';
    public $boundary = '';
    public $header = '';
    protected $_headers = array();
    protected $_isMultipart = false;
    protected $_mail = false;
    protected $_parts = array();
    public $recipients = '';
    public $EOL = "\r\n";

    abstract protected function _sendMail();

    protected function _getHeaders($boundary)
    {
        if (null !== $boundary) {
            // Build multipart mail
            $type = $this->_mail->getType();
            if (!$type) {
                if ($this->_mail->hasAttachments) {
                    $type = IPF_Mime::MULTIPART_MIXED;
                } elseif ($this->_mail->getBodyText() && $this->_mail->getBodyHtml()) {
                    $type = IPF_Mime::MULTIPART_ALTERNATIVE;
                } else {
                    $type = IPF_Mime::MULTIPART_MIXED;
                }
            }

            $this->_headers['Content-Type'] = array(
                $type . '; charset="' . $this->_mail->getCharset() . '";'
                . $this->EOL
                . " " . 'boundary="' . $boundary . '"'
            );
            $this->_headers['MIME-Version'] = array('1.0');

            $this->boundary = $boundary;
        }

        return $this->_headers;
    }

    protected static function _formatHeader(&$item, $key, $prefix)
    {
        $item = $prefix . ': ' . $item;
    }

    protected function _prepareHeaders($headers)
    {
        if (!$this->_mail) {
            throw new IPF_Exception_Mail('Missing IPF_Mail object in _mail property');
        }

        $this->header = '';

        foreach ($headers as $header => $content) {
            if (isset($content['append'])) {
                unset($content['append']);
                $value = implode(',' . $this->EOL . ' ', $content);
                $this->header .= $header . ': ' . $value . $this->EOL;
            } else {
                array_walk($content, array(get_class($this), '_formatHeader'), $header);
                $this->header .= implode($this->EOL, $content) . $this->EOL;
            }
        }

        // Sanity check on headers -- should not be > 998 characters
        $sane = true;
        foreach (explode($this->EOL, $this->header) as $line) {
            if (strlen(trim($line)) > 998) {
                $sane = false;
                break;
            }
        }
        if (!$sane) {
            throw new IPF_Exception_Mail('At least one mail header line is too long');
        }
    }

    protected function _buildBody()
    {
        if (($text = $this->_mail->getBodyText())
            && ($html = $this->_mail->getBodyHtml()))
        {
            // Generate unique boundary for multipart/alternative
            $mime = new IPF_Mime(null);
            $boundaryLine = $mime->boundaryLine($this->EOL);
            $boundaryEnd  = $mime->mimeEnd($this->EOL);

            $text->disposition = false;
            $html->disposition = false;

            $body = $boundaryLine
                  . $text->getHeaders($this->EOL)
                  . $this->EOL
                  . $text->getContent($this->EOL)
                  . $this->EOL
                  . $boundaryLine
                  . $html->getHeaders($this->EOL)
                  . $this->EOL
                  . $html->getContent($this->EOL)
                  . $this->EOL
                  . $boundaryEnd;

            $mp           = new IPF_Mime_Part($body);
            $mp->type     = IPF_Mime::MULTIPART_ALTERNATIVE;
            $mp->boundary = $mime->boundary();

            $this->_isMultipart = true;

            // Ensure first part contains text alternatives
            array_unshift($this->_parts, $mp);

            // Get headers
            $this->_headers = $this->_mail->getHeaders();
            return;
        }

        // If not multipart, then get the body
        if (false !== ($body = $this->_mail->getBodyHtml())) {
            array_unshift($this->_parts, $body);
        } elseif (false !== ($body = $this->_mail->getBodyText())) {
            array_unshift($this->_parts, $body);
        }

        if (!$body) {
            throw new IPF_Exception_Mail('No body specified');
        }

        // Get headers
        $this->_headers = $this->_mail->getHeaders();
        $headers = $body->getHeadersArray($this->EOL);
        foreach ($headers as $header) {
            // Headers in IPF_Mime_Part are kept as arrays with two elements, a
            // key and a value
            $this->_headers[$header[0]] = array($header[1]);
        }
    }

    public function send(IPF_Mail $mail)
    {
        $this->_isMultipart = false;
        $this->_mail        = $mail;
        $this->_parts       = $mail->getParts();
        $mime               = $mail->getMime();

        // Build body content
        $this->_buildBody();

        // Determine number of parts and boundary
        $count    = count($this->_parts);
        $boundary = null;
        if ($count < 1) {
            throw new IPF_Exception_Mail('Empty mail cannot be sent');
        }

        if ($count > 1) {
            // Multipart message; create new MIME object and boundary
            $mime     = new IPF_Mime($this->_mail->getMimeBoundary());
            $boundary = $mime->boundary();
        } elseif ($this->_isMultipart) {
            // multipart/alternative -- grab boundary
            $boundary = $this->_parts[0]->boundary;
        }

        // Determine recipients, and prepare headers
        $this->recipients = implode(',', $mail->getRecipients());
        $this->_prepareHeaders($this->_getHeaders($boundary));

        // Create message body
        // This is done so that the same IPF_Mail object can be used in
        // multiple transports
        $message = new IPF_Mime_Message();
        $message->setParts($this->_parts);
        $message->setMime($mime);
        $this->body = $message->generateMessage($this->EOL);

        // Send to transport!
        $this->_sendMail();
    }
}
