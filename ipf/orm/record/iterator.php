<?php

class IPF_ORM_Record_Iterator extends ArrayIterator
{
    private $record;
    private static $null;
    public function __construct(IPF_ORM_Record $record)
    {
        $this->record = $record;
        parent::__construct($record->getData());
    }

    public static function initNullObject(IPF_ORM_Null $null)
    {
        self::$null = $null;
    }

    public function current()
    {
        $value = parent::current();

        if ($value === self::$null) {
            return null;
        } else {
            return $value;
        }
    }
}