<?php

class IPF_ORM_Connection_Profiler implements IPF_ORM_Overloadable, IteratorAggregate, Countable
{
    private $listeners  = array('query',
                                'prepare',
                                'commit',
                                'rollback',
                                'connect',
                                'begintransaction',
                                'exec',
                                'execute');

    private $events     = array();
    public function __construct() {
    }

    public function setFilterQueryType() {
    }                                         

    public function __call($m, $a)
    {
        if ( ! ($a[0] instanceof IPF_ORM_Event)) {
            throw new IPF_ORM_Exception_Profiler("Couldn't listen event. Event should be an instance of IPF_Event.");
        }


        if (substr($m, 0, 3) === 'pre') {
            // pre-event listener found
            $a[0]->start();

            if ( ! in_array($a[0], $this->events, true)) {
                $this->events[] = $a[0];
            }
        } else {
            // after-event listener found
            $a[0]->end();
        }
        /**
         * If filtering by query type is enabled, only keep the query if
         * it was one of the allowed types.
         */
         /*
        if ( !is_null($this->filterTypes)) {
            if ( ! ($a[0]->getQueryType() & $this->_filterTypes)) {

            }
        }
        */
    }

    public function get($key) 
    {
        if (isset($this->events[$key])) {
            return $this->events[$key];
        }
        return null;
    }

    public function getAll() 
    {
        return $this->events;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->events);
    }

    public function count() 
    {
        return count($this->events);
    }

    public function pop() 
    {
        return array_pop($this->events);
    }

    public function lastEvent()
    {
        if (empty($this->events)) {
            return false;
        }

        end($this->events);
        return current($this->events);
    }
}