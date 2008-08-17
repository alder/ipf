<?php

class IPF_ORM_Event
{
    const CONN_QUERY         = 1;
    const CONN_EXEC          = 2;
    const CONN_PREPARE       = 3;
    const CONN_CONNECT       = 4;
    const CONN_CLOSE         = 5;
    const CONN_ERROR         = 6;

    const STMT_EXECUTE       = 10;
    const STMT_FETCH         = 11;
    const STMT_FETCHALL      = 12;

    const TX_BEGIN           = 31;
    const TX_COMMIT          = 32;
    const TX_ROLLBACK        = 33;
    const SAVEPOINT_CREATE   = 34;
    const SAVEPOINT_ROLLBACK = 35;
    const SAVEPOINT_COMMIT   = 36;

    const HYDRATE            = 40;

    const RECORD_DELETE      = 21;
    const RECORD_SAVE        = 22;
    const RECORD_UPDATE      = 23;
    const RECORD_INSERT      = 24;
    const RECORD_SERIALIZE   = 25;
    const RECORD_UNSERIALIZE = 26;
    const RECORD_DQL_SELECT  = 28;
    const RECORD_DQL_DELETE  = 27;
    const RECORD_DQL_UPDATE  = 29;

    protected $_invoker;

    protected $_query;

    protected $_params;

    protected $_code;

    protected $_startedMicrotime;

    protected $_endedMicrotime;

    protected $_options = array();

    public function __construct($invoker, $code, $query = null, $params = array())
    {
        $this->_invoker = $invoker;
        $this->_code    = $code;
        $this->_query   = $query;
        $this->_params  = $params;
    }

    public function getQuery()
    {
        return $this->_query;
    }

    public function getName()
    {
        switch ($this->_code) {
            case self::CONN_QUERY:
                return 'query';
            case self::CONN_EXEC:
                return 'exec';
            case self::CONN_PREPARE:
                return 'prepare';
            case self::CONN_CONNECT:
                return 'connect';
            case self::CONN_CLOSE:
                return 'close';
            case self::CONN_ERROR:
                return 'error';

            case self::STMT_EXECUTE:
                return 'execute';
            case self::STMT_FETCH:
                return 'fetch';
            case self::STMT_FETCHALL:
                return 'fetch all';

            case self::TX_BEGIN:
                return 'begin';
            case self::TX_COMMIT:
                return 'commit';
            case self::TX_ROLLBACK:
                return 'rollback';

            case self::SAVEPOINT_CREATE:
                return 'create savepoint';
            case self::SAVEPOINT_ROLLBACK:
                return 'rollback savepoint';
            case self::SAVEPOINT_COMMIT:
                return 'commit savepoint';

            case self::RECORD_DELETE:
                return 'delete record';
            case self::RECORD_SAVE:
                return 'save record';
            case self::RECORD_UPDATE:
                return 'update record';
            case self::RECORD_INSERT:
                return 'insert record';
            case self::RECORD_SERIALIZE:
                return 'serialize record';
            case self::RECORD_UNSERIALIZE:
                return 'unserialize record';
            case self::RECORD_DQL_SELECT:
                return 'select records';
            case self::RECORD_DQL_DELETE:
                return 'delete records';
            case self::RECORD_DQL_UPDATE:
                return 'update records';
        }
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function __get($option)
    {
        if ( ! isset($this->_options[$option])) {
            return null;
        }

        return $this->_options[$option];
    }

    public function skipOperation()
    {
        $this->_options['skipOperation'] = true;

        return $this;
    }

    public function __set($option, $value)
    {
        $this->_options[$option] = $value;

        return $this;
    }

    public function set($option, &$value)
    {
        $this->_options[$option] =& $value;

        return $this;
    }

    public function start()
    {
        $this->_startedMicrotime = microtime(true);
    }

    public function hasEnded()
    {
        return ($this->_endedMicrotime != null);
    }

    public function end()
    {
        $this->_endedMicrotime = microtime(true);

        return $this;
    }

    public function getInvoker()
    {
        return $this->_invoker;
    }

    public function setInvoker($invoker)
    {
        $this->_invoker = $invoker;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getElapsedSecs()
    {
        if (is_null($this->_endedMicrotime)) {
            return false;
        }
        return ($this->_endedMicrotime - $this->_startedMicrotime);
    }
}
