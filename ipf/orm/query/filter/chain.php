<?php

class IPF_ORM_Query_Filter_Chain
{
    protected $_filters = array();

    public function add(IPF_ORM_Query_Filter $filter)
    {
        $this->_filters[] = $filter;
    }

    public function get($key)
    {
        if ( ! isset($this->_filters[$key])) {
            throw new IPF_ORM_Exception('Unknown filter ' . $key);
        }
        return $this->_filters[$key];
    }

    public function set($key, IPF_ORM_Query_Filter $listener)
    {
        $this->_filters[$key] = $listener;
    }

    public function preQuery(IPF_ORM_Query $query)
    {
        foreach ($this->_filters as $filter) {
            $filter->preQuery($query);
        }
    }

    public function postQuery(IPF_ORM_Query $query)
    {
        foreach ($this->_filters as $filter) {
            $filter->postQuery($query);
        }
    }
}