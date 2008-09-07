<?php

class IPF_Mail_Part implements RecursiveIterator
{
    protected $_headers;
    protected $_content;
    protected $_topLines = '';
    protected $_parts = array();
    protected $_countParts;
    protected $_iterationPos = 1;
    protected $_mail;
    protected $_messageNum = 0;

    public function __construct(array $params)
    {
        if (isset($params['handler'])) {
            if (!$params['handler'] instanceof IPF_Mail_Storage_Abstract) {
                throw new IPF_Exception_Mail('handler is not a valid mail handler');
            }
            if (!isset($params['id'])) {
                throw new IPF_Exception_Mail('need a message id with a handler');
            }

            $this->_mail       = $params['handler'];
            $this->_messageNum = $params['id'];
        }

        if (isset($params['raw'])) {
            IPF_Mime_Decode::splitMessage($params['raw'], $this->_headers, $this->_content);
        } else if (isset($params['headers'])) {
            if (is_array($params['headers'])) {
                $this->_headers = $params['headers'];
            } else {
                if (!empty($params['noToplines'])) {
                    IPF_Mime_Decode::splitMessage($params['headers'], $this->_headers, $null);
                } else {
                    IPF_Mime_Decode::splitMessage($params['headers'], $this->_headers, $this->_topLines);
                }
            }
            if (isset($params['content'])) {
                $this->_content = $params['content'];
            }
        }
    }

    public function isMultipart()
    {
        try {
            return stripos($this->contentType, 'multipart/') === 0;
        } catch(IPF_Exception_Mail $e) {
            return false;
        }
    }


    public function getContent()
    {
        if ($this->_content !== null) {
            return $this->_content;
        }

        if ($this->_mail) {
            return $this->_mail->getRawContent($this->_messageNum);
        } else {
            throw new IPF_Exception_Mail('no content');
        }
    }

    protected function _cacheContent()
    {
        // caching content if we can't fetch parts
        if ($this->_content === null && $this->_mail) {
            $this->_content = $this->_mail->getRawContent($this->_messageNum);
        }

        if (!$this->isMultipart()) {
            return;
        }

        // split content in parts
        $boundary = $this->getHeaderField('content-type', 'boundary');
        if (!$boundary) {
            throw new IPF_Exception_Mail('no boundary found in content type to split message');
        }
        $parts = IPF_Mime_Decode::splitMessageStruct($this->_content, $boundary);
        $counter = 1;
        foreach ($parts as $part) {
            $this->_parts[$counter++] = new self(array('headers' => $part['header'], 'content' => $part['body']));
        }
    }

    public function getPart($num)
    {
        if (isset($this->_parts[$num])) {
            return $this->_parts[$num];
        }

        if (!$this->_mail && $this->_content === null) {
            throw new IPF_Exception_Mail('part not found');
        }

        if ($this->_mail && $this->_mail->hasFetchPart) {
            // TODO: fetch part
            // return
        }

        $this->_cacheContent();

        if (!isset($this->_parts[$num])) {
            throw new IPF_Exception_Mail('part not found');
        }

        return $this->_parts[$num];
    }

    public function countParts()
    {
        if ($this->_countParts) {
            return $this->_countParts;
        }

        $this->_countParts = count($this->_parts);
        if ($this->_countParts) {
            return $this->_countParts;
        }

        if ($this->_mail && $this->_mail->hasFetchPart) {
            // TODO: fetch part
            // return
        }

        $this->_cacheContent();

        $this->_countParts = count($this->_parts);
        return $this->_countParts;
    }

    public function getHeaders()
    {
        if ($this->_headers === null) {
            if (!$this->_mail) {
                $this->_headers = array();
            } else {
                $part = $this->_mail->getRawHeader($this->_messageNum);
                IPF_Mime_Decode::splitMessage($part, $this->_headers, $null);
            }
        }

        return $this->_headers;
    }

    public function getHeader($name, $format = null)
    {
        if ($this->_headers === null) {
            $this->getHeaders();
        }

        $lowerName = strtolower($name);

        if (!isset($this->_headers[$lowerName])) {
            $lowerName = strtolower(preg_replace('%([a-z])([A-Z])%', '\1-\2', $name));
            if (!isset($this->_headers[$lowerName])) {
                throw new IPF_Exception_Mail("no Header with Name $name found");
            }
        }
        $name = $lowerName;

        $header = $this->_headers[$name];

        switch ($format) {
            case 'string':
                if (is_array($header)) {
                    $header = implode(IPF_Mime::LINEEND, $header);
                }
                break;
            case 'array':
                $header = (array)$header;
            default:
                // do nothing
        }

        return $header;
    }
    
    public function getHeaderField($name, $wantedPart = 0, $firstName = 0) {
    	return IPF_Mime_Decode::splitHeaderField(current($this->getHeader($name, 'array')), $wantedPart, $firstName);
    }

    public function __get($name)
    {
        return $this->getHeader($name, 'string');
    }

    public function __toString()
    {
        return $this->getContent();
    }

    public function hasChildren()
    {
        $current = $this->current();
        return $current && $current instanceof IPF_Mail_Part && $current->isMultipart();
    }

    public function getChildren()
    {
        return $this->current();
    }

    public function valid()
    {
        if ($this->_countParts === null) {
            $this->countParts();
        }
        return $this->_iterationPos && $this->_iterationPos <= $this->_countParts;
    }

    public function next()
    {
        ++$this->_iterationPos;
    }

    public function key()
    {
        return $this->_iterationPos;
    }

    public function current()
    {
        return $this->getPart($this->_iterationPos);
    }

    public function rewind()
    {
        $this->countParts();
        $this->_iterationPos = 1;
    }
}
