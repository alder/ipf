<?php

class IPF_ORM_Pager
{
    protected $_query;
    protected $_countQuery;
    protected $_countQueryParams;
    protected $_numResults;
    protected $_maxPerPage;
    protected $_page;
    protected $_lastPage;
    protected $_executed;

    public function __construct($query, $page, $maxPerPage = 0)
    {
        $this->_setExecuted(false);

        $this->_setQuery($query);
        $this->_setPage($page);

        $this->setMaxPerPage($maxPerPage);
    }

    protected function _initialize($params = array())
    {
        // retrieve the number of items found
        $count = $this->getCountQuery()->count($this->getCountQueryParams($params));
        
        $this->_setNumResults($count);
        $this->_setExecuted(true); // _adjustOffset relies of _executed equals true = getNumResults()

        $this->_adjustOffset();
    }

    protected function _adjustOffset()
    {
        // Define new total of pages
        $this->_setLastPage(
            max(1, ceil($this->getNumResults() / $this->getMaxPerPage()))
        );
        $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

        // Assign new offset and limit to IPF_ORM_Query object
        $p = $this->getQuery();
        $p->offset($offset);
        $p->limit($this->getMaxPerPage());
    }

    public function getExecuted()
    {
        return $this->_executed;
    }

    protected function _setExecuted($executed)
    {
        $this->_executed = $executed;
    }

    public function getRange($rangeStyle, $options = array())
    {
        $class = 'IPF_ORM_Pager_Range_' . ucfirst($rangeStyle);
        return new $class($options, $this);
    }

    public function getNumResults()
    {
        if ($this->getExecuted()) {
            return $this->_numResults;
        }
        throw new IPF_ORM_Exception(
            'Cannot retrieve the number of results of a not yet executed Pager query'
        );
    }

    protected function _setNumResults($nb)
    {
        $this->_numResults = $nb;
    }

    public function getFirstPage()
    {
        return 1;
    }

    public function getLastPage()
    {
        if ($this->getExecuted()) {
            return $this->_lastPage;
        }

        throw new IPF_ORM_Exception(
            'Cannot retrieve the last page number of a not yet executed Pager query'
        );
    }

    protected function _setLastPage($page)
    {
        $this->_lastPage = $page;

        if ($this->getPage() > $page) {
            $this->_setPage($page);
        }
    }

    public function getPage()
    {
        return $this->_page;
    }

    public function getNextPage()
    {
        if ($this->getExecuted()) {
            return min($this->getPage() + 1, $this->getLastPage());
        }

        throw new IPF_ORM_Exception(
            'Cannot retrieve the last page number of a not yet executed Pager query'
        );
    }

    public function getPreviousPage()
    {
        if ($this->getExecuted()) {
            return max($this->getPage() - 1, $this->getFirstPage());
        }

        throw new IPF_ORM_Exception(
            'Cannot retrieve the previous page number of a not yet executed Pager query'
        );
    }

    public function getFirstIndice()
    {
        return ($this->getPage() - 1) * $this->getMaxPerPage() + 1;
    }

    public function getLastIndice()
    {
        return min($this->getNumResults(), ($this->getPage() * $this->getMaxPerPage()));
    }

    public function haveToPaginate()
    {
        if ($this->getExecuted()) {
            return $this->getNumResults() > $this->getMaxPerPage();
        }

        throw new IPF_ORM_Exception(
            'Cannot know if it is necessary to paginate a not yet executed Pager query'
        );
    }

    public function setPage($page)
    {
        $this->_setPage($page);
        $this->_setExecuted(false);
    }

    private function _setPage($page)
    {
        $page = intval($page);
        $this->_page = ($page <= 0) ? 1 : $page;
    }

    public function getMaxPerPage()
    {
        return $this->_maxPerPage;
    }

    public function setMaxPerPage($max)
    {
        if ($max > 0) {
            $this->_maxPerPage = $max;
        } else if ($max == 0) {
            $this->_maxPerPage = 25;
        } else {
            $this->_maxPerPage = abs($max);
        }

        $this->_setExecuted(false);
    }

    public function getResultsInPage()
    {
        $page = $this->getPage();

        if ($page != $this->getLastPage()) {
            return $this->getMaxPerPage();
        }

        $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

        return abs($this->getNumResults() - $offset);
    }

    public function getQuery()
    {
        return $this->_query;
    }

    protected function _setQuery($query)
    {
        if (is_string($query)) {
            $query = IPF_ORM_Query::create()->parseQuery($query);
        }

        $this->_query = $query;
    }

    public function getCountQuery()
    {
        return ($this->_countQuery !== null) ? $this->_countQuery : $this->_query;
    }

    public function setCountQuery($query, $params = null)
    {
        if (is_string($query)) {
            $query = IPF_ORM_Query::create()->parseQuery($query);
        }

        $this->_countQuery = $query;

        $this->setCountQueryParams($params);

        $this->_setExecuted(false);
    }

    public function getCountQueryParams($defaultParams = array())
    {
        return ($this->_countQueryParams !== null) ? $this->_countQueryParams : $defaultParams;
    }

    public function setCountQueryParams($params = array(), $append = false)
    {
        if ($append && is_array($this->_countQueryParams)) {
            $this->_countQueryParams = array_merge($this->_countQueryParams, $params);
        } else {
            if ($params !== null && !is_array($params)) {
                $params = array($params);
            }

            $this->_countQueryParams = $params;
        }

        $this->_setExecuted(false);
    }

    public function execute($params = array(), $hydrationMode = IPF_ORM::FETCH_RECORD)
    {
        if (!$this->getExecuted()) {
            $this->_initialize($params);
        }
        return $this->getQuery()->execute($params, $hydrationMode);
    }
}