<?php

class IPF_ORM_Connection_Profiler
{
    private $listeners  = array('query',
                                'prepare',
                                'commit',
                                'rollback',
                                'connect',
                                'begintransaction',
                                'exec',
                                'execute');

    public $events = array();

    public function __call($m, $a)
    {
        if (!($a[0] instanceof IPF_ORM_Event))
            return;

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
    }
}

