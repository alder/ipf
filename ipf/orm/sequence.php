<?php

class IPF_ORM_Sequence extends IPF_ORM_Connection_Module
{
    public function nextId($seqName, $ondemand = true)
    {
        throw new IPF_ORM_Sequence_Exception('method not implemented');
    }

    public function lastInsertId($table = null, $field = null)
    {
        throw new IPF_ORM_Exception('method not implemented');
    }

    public function currId($seqName)
    {
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        return $this->nextId($seqName);
    }
}