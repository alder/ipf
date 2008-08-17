<?php

class IPF_ORM_Relation_ForeignKey extends IPF_ORM_Relation
{
    public function fetchRelatedFor(IPF_ORM_Record $record)
    {
        $id = array();
        $localTable = $record->getTable();
        foreach ((array) $this->definition['local'] as $local) {
           $value = $record->get($localTable->getFieldName($local));
           if (isset($value)) {
               $id[] = $value;
           }
        }
        if ($this->isOneToOne()) {
            if ( ! $record->exists() || empty($id) || 
                 ! $this->definition['table']->getAttribute(IPF_ORM::ATTR_LOAD_REFERENCES)) {
                
                $related = $this->getTable()->create();
            } else {
                $dql  = 'FROM ' . $this->getTable()->getComponentName()
                      . ' WHERE ' . $this->getCondition();

                $coll = $this->getTable()->getConnection()->query($dql, $id);
                $related = $coll[0];
            }

            $related->set($related->getTable()->getFieldName($this->definition['foreign']),
                    $record, false);
        } else {

            if ( ! $record->exists() || empty($id) || 
                 ! $this->definition['table']->getAttribute(IPF_ORM::ATTR_LOAD_REFERENCES)) {
                
                $related = new IPF_ORM_Collection($this->getTable());
            } else {
                $query      = $this->getRelationDql(1);
                $related    = $this->getTable()->getConnection()->query($query, $id);
            }
            $related->setReference($record, $this);
        }
        return $related;
    }

    public function getCondition($alias = null)
    {
        if ( ! $alias) {
           $alias = $this->getTable()->getComponentName();
        }
        $conditions = array();
        foreach ((array) $this->definition['foreign'] as $foreign) {
            $conditions[] = $alias . '.' . $foreign . ' = ?';
        }
        return implode(' AND ', $conditions);
    }
}