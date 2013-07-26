<?php

class IPF_ORM_Record_Iterator extends ArrayIterator
{
    private $record;

    public function __construct(IPF_ORM_Record $record)
    {
        $this->record = $record;
        parent::__construct($record->getData());
    }

    public function current()
    {
        $value = parent::current();

        if (IPF_ORM_Null::isNull($value)) {
            return null;
        } else {
            return $value;
        }
    }
}

