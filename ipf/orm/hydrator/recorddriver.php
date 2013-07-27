<?php

class IPF_ORM_Hydrator_RecordDriver
{
    protected $_collections = array();
    protected $_tables = array();
    private $_initializedRelations = array();

    public function getElementCollection($component)
    {
        $coll = new IPF_ORM_Collection($component);
        $this->_collections[] = $coll;

        return $coll;
    }

    public function getLastKey($coll) 
    {
        $coll->end();
        
        return $coll->key();
    }
    
    public function initRelated(IPF_ORM_Record $record, $name)
    {
        if ( ! isset($this->_initializedRelations[$record->getOid()][$name])) {
            $relation = $record->getTable()->getRelation($name);
            $coll = new IPF_ORM_Collection($relation->getTable()->getComponentName());
            $coll->setReference($record, $relation);
            $record[$name] = $coll;
            $this->_initializedRelations[$record->getOid()][$name] = true;
        }
        return true;
    }
    
    public function registerCollection(IPF_ORM_Collection $coll)
    {
        $this->_collections[] = $coll;
    }
    
    public function getNullPointer()
    {
        return IPF_ORM_Null::getInstance();
    }
    
    public function getElement(array $data, $component)
    {
        $component = $this->_getClassNameToReturn($data, $component);
        if ( ! isset($this->_tables[$component])) {
            $this->_tables[$component] = IPF_ORM::getTable($component);
            $this->_tables[$component]->setAttribute(IPF_ORM::ATTR_LOAD_REFERENCES, false);
        }

        $this->_tables[$component]->setData($data);
        $record = $this->_tables[$component]->getRecord();

        return $record;
    }
    
    public function flush()
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $key => $coll) {
            $coll->takeSnapshot();
        }
        foreach ($this->_tables as $table) {
            $table->setAttribute(IPF_ORM::ATTR_LOAD_REFERENCES, true);
        }
        $this->_initializedRelations = null;
        $this->_collections = null;
        $this->_tables = null;
    }
    
    protected function _getClassNameToReturn(array &$data, $component)
    {
        if ( ! isset($this->_tables[$component])) {
            $this->_tables[$component] = IPF_ORM::getTable($component);
            $this->_tables[$component]->setAttribute(IPF_ORM::ATTR_LOAD_REFERENCES, false);
        }
        
        if ( ! ($subclasses = $this->_tables[$component]->getOption('subclasses'))) {
            return $component;
        }
        
        foreach ($subclasses as $subclass) {
            $table = IPF_ORM::getTable($subclass);
            $inheritanceMap = $table->getOption('inheritanceMap');
            list($key, $value) = each($inheritanceMap);
            if ( ! isset($data[$key]) || $data[$key] != $value) {
                continue;
            } else {
                return $table->getComponentName();
            }
        }
        return $component;
    }
}
