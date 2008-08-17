<?php

class IPF_ORM_Record_Filter_Compound extends IPF_ORM_Record_Filter
{
    protected $_aliases = array();

    public function __construct(array $aliases)
    {
        $this->_aliases = $aliases;
    }
    public function init()
    {
    	// check that all aliases exist
    	foreach ($this->_aliases as $alias) {
            $this->_table->getRelation($alias);
    	}
    }

    public function filterSet(IPF_ORM_Record $record, $name, $value)
    {
        foreach ($this->_aliases as $alias) {
            if ( ! $record->exists()) {
                if (isset($record[$alias][$name])) {
                    $record[$alias][$name] = $value;
                    
                    return $record;
                }
            } else {
                // we do not want to execute N + 1 queries here, hence we cannot use get()
                if (($ref = $record->reference($alias)) !== null) {
                    if (isset($ref[$name])) {
                        $ref[$name] = $value;
                    }
                    
                    return $record;
                }
            }
        }
    }

    public function filterGet(IPF_ORM_Record $record, $name)
    {
        foreach ($this->_aliases as $alias) {
            if ( ! $record->exists()) {
                if (isset($record[$alias][$name])) {
                    return $record[$alias][$name];
                }
            } else {
                // we do not want to execute N + 1 queries here, hence we cannot use get()
                if (($ref = $record->reference($alias)) !== null) {
                    if (isset($ref[$name])) {
                        return $ref[$name];
                    }
                }
            }
        }
    }
}