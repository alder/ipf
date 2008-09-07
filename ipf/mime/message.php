<?php

class IPF_Mime_Message
{

    protected $_parts = array();
    protected $_mime = null;

    public function getParts()
    {
        return $this->_parts;
    }

    public function setParts($parts)
    {
        $this->_parts = $parts;
    }

    public function addPart(IPF_Mime_Part $part)
    {
        /**
         * @todo check for duplicate object handle
         */
        $this->_parts[] = $part;
    }

    public function isMultiPart()
    {
        return (count($this->_parts) > 1);
    }

    public function setMime(IPF_Mime $mime)
    {
        $this->_mime = $mime;
    }

    public function getMime()
    {
        if ($this->_mime === null) {
            $this->_mime = new IPF_Mime();
        }

        return $this->_mime;
    }

    public function generateMessage($EOL = IPF_Mime::LINEEND)
    {
        if (! $this->isMultiPart()) {
            $body = array_shift($this->_parts);
            $body = $body->getContent($EOL);
        } else {
            $mime = $this->getMime();

            $boundaryLine = $mime->boundaryLine($EOL);
            $body = 'This is a message in Mime Format.  If you see this, '
                  . "your mail reader does not support this format." . $EOL;

            foreach (array_keys($this->_parts) as $p) {
                $body .= $boundaryLine
                       . $this->getPartHeaders($p, $EOL)
                       . $EOL
                       . $this->getPartContent($p, $EOL);
            }

            $body .= $mime->mimeEnd($EOL);
        }

        return trim($body);
    }

    public function getPartHeadersArray($partnum)
    {
        return $this->_parts[$partnum]->getHeadersArray();
    }

    public function getPartHeaders($partnum, $EOL = IPF_Mime::LINEEND)
    {
        return $this->_parts[$partnum]->getHeaders($EOL);
    }

    public function getPartContent($partnum, $EOL = IPF_Mime::LINEEND)
    {
        return $this->_parts[$partnum]->getContent($EOL);
    }

    protected static function _disassembleMime($body, $boundary)
    {
        $start = 0;
        $res = array();
        // find every mime part limiter and cut out the
        // string before it.
        // the part before the first boundary string is discarded:
        $p = strpos($body, '--'.$boundary."\n", $start);
        if ($p === false) {
            // no parts found!
            return array();
        }

        // position after first boundary line
        $start = $p + 3 + strlen($boundary);

        while (($p = strpos($body, '--' . $boundary . "\n", $start)) !== false) {
            $res[] = substr($body, $start, $p-$start);
            $start = $p + 3 + strlen($boundary);
        }

        // no more parts, find end boundary
        $p = strpos($body, '--' . $boundary . '--', $start);
        if ($p===false) {
            throw new IPF_Exception('Not a valid Mime Message: End Missing');
        }

        // the remaining part also needs to be parsed:
        $res[] = substr($body, $start, $p-$start);
        return $res;
    }

    public static function createFromMessage($message, $boundary, $EOL = IPF_Mime::LINEEND)
    {
        $parts = IPF_Mime_Decode::splitMessageStruct($message, $boundary, $EOL);

        $res = new self();
        foreach ($parts as $part) {
            // now we build a new MimePart for the current Message Part:
            $newPart = new IPF_Mime_Part($part);
            foreach ($part['header'] as $key => $value) {
                /**
                 * @todo check for characterset and filename
                 */
                // list($key, $value) = $header;
                switch($key) {
                    case 'content-type':
                        $newPart->type = $value;
                        break;
                    case 'content-transfer-encoding':
                        $newPart->encoding = $value;
                        break;
                    case 'content-id':
                        $newPart->id = trim($value,'<>');
                        break;
                    case 'Content-Disposition':
                        $newPart->disposition = $value;
                        break;
                    case 'content-description':
                        $newPart->description = $value;
                        break;
                    default:
                        throw new IPF_Exception('Unknown header ignored for MimePart:' . $key);
                }
            }
            $res->addPart($newPart);
        }
        return $res;
    }
}
