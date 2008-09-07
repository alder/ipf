<?php

class IPF_Mail extends IPF_Mime_Message
{
    protected static $_defaultTransport = null;
    protected $_charset = null;
    protected $_headers = array();
    protected $_from = null;
    protected $_to = array();
    protected $_recipients = array();
    protected $_returnPath = null;
    protected $_subject = null;
    protected $_date = null;
    protected $_bodyText = false;
    protected $_bodyHtml = false;
    protected $_mimeBoundary = null;
    protected $_type = null;
    public $hasAttachments = false;
    public static function setDefaultTransport(IPF_Mail_Transport_Abstract $transport)
    {
        self::$_defaultTransport = $transport;
    }

    public function __construct($charset='iso-8859-1')
    {
        $this->_charset = $charset;
    }

    public function getCharset()
    {
        return $this->_charset;
    }

    public function setType($type)
    {
        $allowed = array(
            IPF_Mime::MULTIPART_ALTERNATIVE,
            IPF_Mime::MULTIPART_MIXED,
            IPF_Mime::MULTIPART_RELATED,
        );
        if (!in_array($type, $allowed)) {
            throw new IPF_Exception_Mail('Invalid content type "' . $type . '"');
        }

        $this->_type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setMimeBoundary($boundary)
    {
        $this->_mimeBoundary = $boundary;

        return $this;
    }

    public function getMimeBoundary()
    {
        return $this->_mimeBoundary;
    }

    public function setBodyText($txt, $charset = null, $encoding = IPF_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($charset === null) {
            $charset = $this->_charset;
        }

        $mp = new IPF_Mime_Part($txt);
        $mp->encoding = $encoding;
        $mp->type = IPF_Mime::TYPE_TEXT;
        $mp->disposition = IPF_Mime::DISPOSITION_INLINE;
        $mp->charset = $charset;

        $this->_bodyText = $mp;

        return $this;
    }

    public function getBodyText($textOnly = false)
    {
        if ($textOnly && $this->_bodyText) {
            $body = $this->_bodyText;
            return $body->getContent();
        }

        return $this->_bodyText;
    }

    public function setBodyHtml($html, $charset = null, $encoding = IPF_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($charset === null) {
            $charset = $this->_charset;
        }

        $mp = new IPF_Mime_Part($html);
        $mp->encoding = $encoding;
        $mp->type = IPF_Mime::TYPE_HTML;
        $mp->disposition = IPF_Mime::DISPOSITION_INLINE;
        $mp->charset = $charset;

        $this->_bodyHtml = $mp;

        return $this;
    }

    public function getBodyHtml($htmlOnly = false)
    {
        if ($htmlOnly && $this->_bodyHtml) {
            $body = $this->_bodyHtml;
            return $body->getContent();
        }

        return $this->_bodyHtml;
    }

    public function addAttachment(IPF_Mime_Part $attachment)
    {
        $this->addPart($attachment);
        $this->hasAttachments = true;

        return $this;
    }

    public function createAttachment($body,
                                     $mimeType    = IPF_Mime::TYPE_OCTETSTREAM,
                                     $disposition = IPF_Mime::DISPOSITION_ATTACHMENT,
                                     $encoding    = IPF_Mime::ENCODING_BASE64,
                                     $filename    = null)
    {

        $mp = new IPF_Mime_Part($body);
        $mp->encoding = $encoding;
        $mp->type = $mimeType;
        $mp->disposition = $disposition;
        $mp->filename = $filename;

        $this->addAttachment($mp);

        return $mp;
    }

    public function getPartCount()
    {
        return count($this->_parts);
    }

    protected function _encodeHeader($value)
    {
      if (IPF_Mime::isPrintable($value)) {
          return $value;
      } else {
          $quotedValue = IPF_Mime::encodeQuotedPrintable($value);
          $quotedValue = str_replace(array('?', ' '), array('=3F', '=20'), $quotedValue);
          return '=?' . $this->_charset . '?Q?' . $quotedValue . '?=';
      }
    }

    protected function _storeHeader($headerName, $value, $append=false)
    {
// ??        $value = strtr($value,"\r\n\t",'???');
        if (isset($this->_headers[$headerName])) {
            $this->_headers[$headerName][] = $value;
        } else {
            $this->_headers[$headerName] = array($value);
        }

        if ($append) {
            $this->_headers[$headerName]['append'] = true;
        }

    }

    protected function _addRecipient($email, $to = false)
    {
        // prevent duplicates
        $this->_recipients[$email] = 1;

        if ($to) {
            $this->_to[] = $email;
        }
    }

    protected function _addRecipientAndHeader($headerName, $name, $email)
    {
        $email = strtr($email,"\r\n\t",'???');
        $this->_addRecipient($email, ('To' == $headerName) ? true : false);
        if ($name != '') {
            $name = '"' . $this->_encodeHeader($name) . '" ';
        }

        $this->_storeHeader($headerName, $name .'<'. $email . '>', true);
    }

    public function addTo($email, $name='')
    {
        $this->_addRecipientAndHeader('To', $name, $email);
        return $this;
    }

    public function addCc($email, $name='')
    {
        $this->_addRecipientAndHeader('Cc', $name, $email);
        return $this;
    }

    public function addBcc($email)
    {
        $this->_addRecipientAndHeader('Bcc', '', $email);
        return $this;
    }

    public function getRecipients()
    {
        return array_keys($this->_recipients);
    }

    public function setFrom($email, $name = '')
    {
        if ($this->_from === null) {
            $email = strtr($email,"\r\n\t",'???');
            $this->_from = $email;
            $this->_storeHeader('From', $this->_encodeHeader('"'.$name.'"').' <'.$email.'>', true);
        } else {
            throw new IPF_Exception_Mail('From Header set twice');
        }
        return $this;
    }

    public function getFrom()
    {
        return $this->_from;
    }

    public function setReturnPath($email)
    {
        if ($this->_returnPath === null) {
            $email = strtr($email,"\r\n\t",'???');
            $this->_returnPath = $email;
            $this->_storeHeader('Return-Path', $email, false);
        } else {
            throw new IPF_Exception_Mail('Return-Path Header set twice');
        }
        return $this;
    }

    public function getReturnPath()
    {
        if (null !== $this->_returnPath) {
            return $this->_returnPath;
        }

        return $this->_from;
    }

    public function setSubject($subject)
    {
        if ($this->_subject === null) {
            $subject = strtr($subject,"\r\n\t",'???');
            $this->_subject = $this->_encodeHeader($subject);
            $this->_storeHeader('Subject', $this->_subject);
        } else {
            throw new IPF_Exception_Mail('Subject set twice');
        }
        return $this;
    }

    public function getSubject()
    {
        return $this->_subject;
    }
    
    public function setDate($date = null)
    {
        if ($this->_date === null) {
            if ($date === null) {
                $date = date('r');
            } else if (is_int($date)) {
                $date = date('r', $date);
            } else if (is_string($date)) {
            	$date = strtotime($date);
                if ($date === false || $date < 0) {
                    throw new IPF_Exception_Mail('String representations of Date Header must be ' .
                                                  'strtotime()-compatible');
                }
                $date = date('r', $date);
            } else {
                throw new IPF_Exception_Mail(__METHOD__ . ' only accepts UNIX timestamps and strtotime()-compatible strings');
            }
            $this->_date = $date;
            $this->_storeHeader('Date', $date);
        } else {
            throw new IPF_Exception_Mail('Date Header set twice');
        }
        return $this;
    }

    public function getDate()
    {
        return $this->_date;
    }

    public function addHeader($name, $value, $append = false)
    {
        if (in_array(strtolower($name), array('to', 'cc', 'bcc', 'from', 'subject', 'return-path', 'date'))) {
            throw new IPF_Exception_Mail('Cannot set standard header from addHeader()');
        }

        $value = strtr($value,"\r\n\t",'???');
        $value = $this->_encodeHeader($value);
        $this->_storeHeader($name, $value, $append);

        return $this;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function send($transport = null)
    {
        if ($transport === null) {
            if (! self::$_defaultTransport instanceof IPF_Mail_Transport_Abstract) {
                $transport = new IPF_Mail_Transport_Sendmail();
            } else {
                $transport = self::$_defaultTransport;
            }
        }

        if (is_null($this->_date)) {
            $this->setDate();
        }

        $transport->send($this);

        return $this;
    }

}
