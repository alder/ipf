<?php

class IPF_ORM_Record_Filter_Standard extends IPF_ORM_Record_Filter
{
    public function filterSet(IPF_ORM_Record $record, $name, $value)
    {
        throw new IPF_ORM_Exception(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }

    public function filterGet(IPF_ORM_Record $record, $name)
    {
        throw new IPF_ORM_Exception(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }
}