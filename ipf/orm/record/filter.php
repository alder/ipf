<?php

abstract class IPF_ORM_Record_Filter
{
    protected $_table;

    public function setTable(IPF_ORM_Table $table)
    {
        $this->_table = $table;
    }

    public function getTable()
    {
        return $this->_table;
    }

    abstract public function filterSet(IPF_ORM_Record $record, $name, $value);
    abstract public function filterGet(IPF_ORM_Record $record, $name);
}