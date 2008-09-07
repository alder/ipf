<?php

class IPF_Mail_Message extends IPF_Mail_Part
{
    protected $_flags = array();

    public function __construct(array $params)
    {
        if (isset($params['file'])) {
            if (!is_resource($params['file'])) {
                $params['raw'] = @file_get_contents($params['file']);
                if ($params['raw'] === false) {
                    throw new IPF_Exception_Mail('could not open file');
                }
            } else {
                $params['raw'] = stream_get_contents($params['file']);
            }
        }

        if (!empty($params['flags'])) {
            // set key and value to the same value for easy lookup
            $this->_flags = array_combine($params['flags'], $params['flags']);
        }

        parent::__construct($params);
    }

    public function getTopLines()
    {
        return $this->_topLines;
    }

    public function hasFlag($flag)
    {
        return isset($this->_flags[$flag]);
    }

    public function getFlags()
    {
        return $this->_flags;
    }
}
