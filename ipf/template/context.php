<?php

class IPF_Template_Context
{
    public $_vars;
    private $stack = array();

    function __construct($vars=array())
    {
        $this->_vars = new IPF_Template_ContextVars($vars);
    }

    function get($var)
    {
        if (isset($this->_vars[$var])) {
            return $this->_vars[$var];
        }
        return '';
    }

    function set($var, $value)
    {
        $this->_vars[$var] = $value;
    }

    public function push()
    {
        $vars = func_get_args();
        $frame = array();
        foreach ($vars as $var) {
            $frame[$var] = $this->get($var);
        }
        $this->stack[] = $frame;
    }

    public function pop()
    {
        $frame = array_pop($this->stack);
        foreach ($frame as $var => $val)
            $this->set($var, $val);
    }
}

