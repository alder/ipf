<?php

class IPF_ORM_Expression
{
    protected $_expression;
    protected $_conn;
    protected $_tokenizer;

    public function __construct($expr, $conn = null)
    {
        $this->_tokenizer = new IPF_ORM_Query_Tokenizer();
        $this->setExpression($expr);
        if ($conn !== null) {
            $this->_conn = $conn;
        }
    }

    public function getConnection()
    {
        if ( ! isset($this->_conn)) {
            return IPF_ORM_Manager::connection();
        }

        return $this->_conn;
    }

    public function setExpression($clause)
    {
        $this->_expression = $this->parseClause($clause);
    }

    public function parseExpression($expr)
    {
        $pos  = strpos($expr, '(');
        $quoted = (substr($expr, 0, 1) === "'" && substr($expr, -1) === "'");
        if ($pos === false || $quoted) {
            return $expr;
        }

        // get the name of the function
        $name   = substr($expr, 0, $pos);
        $argStr = substr($expr, ($pos + 1), -1);

        // parse args
        foreach ($this->_tokenizer->bracketExplode($argStr, ',') as $arg) {
           $args[] = $this->parseClause($arg);
        }

        return call_user_func_array(array($this->getConnection()->expression, $name), $args);
    }

    public function parseClause($clause)
    {
        $e = $this->_tokenizer->bracketExplode($clause, ' ');

        foreach ($e as $k => $expr) {
            $e[$k] = $this->parseExpression($expr);
        }
        
        return implode(' ', $e);
    }

    public function getSql()
    {
        return $this->_expression;
    }

    public function __toString()
    {
        return $this->getSql();
    }
}
