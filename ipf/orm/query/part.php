<?php

abstract class IPF_ORM_Query_Part
{
    protected $query;
    
    protected $_tokenizer;

    public function __construct($query, IPF_ORM_Query_Tokenizer $tokenizer = null)
    {
        $this->query = $query;
        if ( ! $tokenizer) {
            $tokenizer = new IPF_ORM_Query_Tokenizer();
        }
        $this->_tokenizer = $tokenizer;
    }

    public function getQuery()
    {
        return $this->query;
    }
}
