<?php

class IPF_ORM_Relation_LocalKey extends IPF_ORM_Relation
{
    public function fetchRelatedFor(IPF_ORM_Record $record)
    {
        $localFieldName = $record->getTable()->getFieldName($this->definition['local']);
        $id = $record->get($localFieldName);

        if (is_null($id) || ! $this->definition['table']->getAttribute(IPF_ORM::ATTR_LOAD_REFERENCES)) {
            $related = $this->getTable()->create();
        } else {
            $dql  = 'FROM ' . $this->getTable()->getComponentName()
                 . ' WHERE ' . $this->getCondition();

            $related = $this->getTable()
                            ->getConnection()
                            ->query($dql, array($id))
                            ->getFirst();
            
            if ( ! $related || empty($related)) {
                $related = $this->getTable()->create();
            }
        }

        $record->set($localFieldName, $related, false);

        return $related;
    }

    public function getCondition($alias = null)
    {
        if ( ! $alias) {
           $alias = $this->getTable()->getComponentName();
        }
        return $alias . '.' . $this->definition['foreign'] . ' = ?';
    }
}