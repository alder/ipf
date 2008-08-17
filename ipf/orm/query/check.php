<?php

class IPF_ORM_Query_Check
{
    protected $table;

    protected $sql;
    
    protected $_tokenizer;

    public function __construct($table)
    {
        if ( ! ($table instanceof IPF_ORM_Table)) {
            $table = IPF_ORM_Manager::getInstance()
                        ->getCurrentConnection()
                        ->getTable($table);
        }
        $this->table = $table;
        $this->_tokenizer = new IPF_ORM_Query_Tokenizer();
    }

    public function getTable()
    {
        return $this->table;
    }

    public function parse($dql)
    {
        $this->sql = $this->parseClause($dql);
    }

    public function parseClause($dql)
    {
        $parts = $this->_tokenizer->sqlExplode($dql, ' AND ');

        if (count($parts) > 1) {
            $ret = array();
            foreach ($parts as $part) {
                $ret[] = $this->parseSingle($part);
            }

            $r = implode(' AND ', $ret);
        } else {
            $parts = $this->_tokenizer->quoteExplode($dql, ' OR ');
            if (count($parts) > 1) {
                $ret = array();
                foreach ($parts as $part) {
                    $ret[] = $this->parseClause($part);
                }

                $r = implode(' OR ', $ret);
            } else {
                $ret = $this->parseSingle($dql);
                return $ret;
            }
        }
        return '(' . $r . ')';
    }
    
    public function parseSingle($part)
    {
        $e = explode(' ', $part);
        
        $e[0] = $this->parseFunction($e[0]);

        switch ($e[1]) {
            case '>':
            case '<':
            case '=':
            case '!=':
            case '<>':

            break;
            default:
                throw new IPF_ORM_Exception('Unknown operator ' . $e[1]);
        }

        return implode(' ', $e);
    }
    public function parseFunction($dql) 
    {
        if (($pos = strpos($dql, '(')) !== false) {
            $func  = substr($dql, 0, $pos);
            $value = substr($dql, ($pos + 1), -1);
            
            $expr  = $this->table->getConnection()->expression;

            if ( ! method_exists($expr, $func)) {
                throw new IPF_ORM_Exception('Unknown function ' . $func);
            }
            
            $func  = $expr->$func($value);
        }
        return $func;
    }

    public function getSql()
    {
        return $this->sql;
    }
}