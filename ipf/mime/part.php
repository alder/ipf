<?php

class IPF_Mime_Part {

    public $type = IPF_Mime::TYPE_OCTETSTREAM;
    public $encoding = IPF_Mime::ENCODING_8BIT;
    public $id;
    public $disposition;
    public $filename;
    public $description;
    public $charset;
    public $boundary;
    protected $_content;
    protected $_isStream = false;


    public function __construct($content)
    {
        $this->_content = $content;
        if (is_resource($content)) {
            $this->_isStream = true;
        }
    }

    public function isStream()
    {
      return $this->_isStream;
    }

    public function getEncodedStream()
    {
        if (!$this->_isStream) {
            throw new IPF_Exception('Attempt to get a stream from a string part');
        }

        //stream_filter_remove(); // ??? is that right?
        switch ($this->encoding) {
            case IPF_Mime::ENCODING_QUOTEDPRINTABLE:
                $filter = stream_filter_append(
                    $this->_content,
                    'convert.quoted-printable-encode',
                    STREAM_FILTER_READ,
                    array(
                        'line-length'      => 72,
                        'line-break-chars' => IPF_Mime::LINEEND
                    )
                );
                if (!is_resource($filter)) {
                    throw new IPF_Exception('Failed to append quoted-printable filter');
                }
                break;
            case IPF_Mime::ENCODING_BASE64:
                $filter = stream_filter_append(
                    $this->_content,
                    'convert.base64-encode',
                    STREAM_FILTER_READ,
                    array(
                        'line-length'      => 72,
                        'line-break-chars' => IPF_Mime::LINEEND
                    )
                );
                if (!is_resource($filter)) {
                    throw new IPF_Exception('Failed to append base64 filter');
                }
                break;
            default:
        }
        return $this->_content;
    }

    public function getContent($EOL = IPF_Mime::LINEEND)
    {
        if ($this->_isStream) {
            return stream_get_contents($this->getEncodedStream());
        } else {
            return IPF_Mime::encode($this->_content, $this->encoding, $EOL);
        }
    }

    public function getHeadersArray($EOL = IPF_Mime::LINEEND)
    {
        $headers = array();

        $contentType = $this->type;
        if ($this->charset) {
            $contentType .= '; charset="' . $this->charset . '"';
        }

        if ($this->boundary) {
            $contentType .= ';' . $EOL
                          . " boundary=\"" . $this->boundary . '"';
        }

        $headers[] = array('Content-Type', $contentType);

        if ($this->encoding) {
            $headers[] = array('Content-Transfer-Encoding', $this->encoding);
        }

        if ($this->id) {
            $headers[]  = array('Content-ID', '<' . $this->id . '>');
        }

        if ($this->disposition) {
            $disposition = $this->disposition;
            if ($this->filename) {
                $disposition .= '; filename="' . $this->filename . '"';
            }
            $headers[] = array('Content-Disposition', $disposition);
        }

        if ($this->description) {
            $headers[] = array('Content-Description', $this->description);
        }

        return $headers;
    }

    public function getHeaders($EOL = IPF_Mime::LINEEND)
    {
        $res = '';
        foreach ($this->getHeadersArray($EOL) as $header) {
            $res .= $header[0] . ': ' . $header[1] . $EOL;
        }

        return $res;
    }
}
