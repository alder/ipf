<?php

class IPF_ORM_Relation_Association extends IPF_ORM_Relation
{
    public function getAssociationFactory()
    {
        return $this->definition['refTable'];
    }
    public function getAssociationTable()
    {
        return $this->definition['refTable'];
    }

    public function getRelationDql($count, $context = 'record')
    {
        $table = $this->definition['refTable'];
        $component = $this->definition['refTable']->getComponentName();
        
        switch ($context) {
            case "record":
                $sub  = substr(str_repeat("?, ", $count),0,-2);
                $dql  = 'FROM ' . $this->getTable()->getComponentName();
                $dql .= '.' . $component;
                $dql .= ' WHERE ' . $this->getTable()->getComponentName()
                . '.' . $component . '.' . $this->definition['local'] . ' IN (' . $sub . ')';
                break;
            case "collection":
                $sub  = substr(str_repeat("?, ", $count),0,-2);
                $dql  = 'FROM ' . $component . '.' . $this->getTable()->getComponentName();
                $dql .= ' WHERE ' . $component . '.' . $this->definition['local'] . ' IN (' . $sub . ')';
                break;
        }

        return $dql;
    }

    public function fetchRelatedFor(IPF_ORM_Record $record)
    {
        $id = $record->getIncremented();
        if (empty($id) || ! $this->definition['table']->getAttribute(IPF_ORM::ATTR_LOAD_REFERENCES)) {
            $coll = new IPF_ORM_Collection($this->getTable());
        } else {
            $coll = $this->getTable()->getConnection()->query($this->getRelationDql(1), array($id));
        }
        $coll->setReference($record, $this);
        return $coll;
    }
}