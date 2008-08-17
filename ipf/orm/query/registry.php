<?php

class IPF_ORM_Query_Registry
{
    protected $_queries = array();

    public function add($key, $query)
    {
    	if (strpos($key, '/') === false) {
            $this->_queries[$key] = $query;
        } else {
            // namespace found
            $e = explode('/', $key);

            $this->_queries[$e[0]][$e[1]] = $query;
        }
    }
    
    public function get($key, $namespace = null)
    {
        if (isset($namespace)) {
            if ( ! isset($this->_queries[$namespace][$key])) {
                throw new IPF_ORM_Exception('A query with the name ' . $namespace . '/' . $key . ' does not exist.');
            }
            $query = $this->_queries[$namespace][$key];
        } else {
            if ( ! isset($this->_queries[$key])) {
                throw new IPF_ORM_Exception('A query with the name ' . $key . ' does not exist.');
            }
            $query = $this->_queries[$key];
        }
        
        if ( ! ($query instanceof IPF_ORM_Query)) {
            $query = IPF_ORM_Query::create()->parseQuery($query);
        }
        
        return $query;
    }
}