<?php

class IPF_ORM_Relation_Nest extends IPF_ORM_Relation_Association
{
    public function getRelationDql($count, $context = 'record')
    {
        switch ($context) {
            case 'record':
                $identifierColumnNames = $this->definition['table']->getIdentifierColumnNames();
                $identifier = array_pop($identifierColumnNames);
                $sub    = 'SELECT '.$this->definition['foreign'] 
                        . ' FROM '.$this->definition['refTable']->getTableName()
                        . ' WHERE '.$this->definition['local']
                        . ' = ?';

                $sub2   = 'SELECT '.$this->definition['local']
                        . ' FROM '.$this->definition['refTable']->getTableName()
                        . ' WHERE '.$this->definition['foreign']
                        . ' = ?';

                $dql  = 'FROM ' . $this->definition['table']->getComponentName()
                      . '.' . $this->definition['refTable']->getComponentName()
                      . ' WHERE ' . $this->definition['table']->getComponentName()
                      . '.' . $identifier 
                      . ' IN (' . $sub . ')'
                      . ' || ' . $this->definition['table']->getComponentName() 
                      . '.' . $identifier
                      . ' IN (' . $sub2 . ')';
                break;
            case 'collection':
                $sub  = substr(str_repeat('?, ', $count),0,-2);
                $dql  = 'FROM '.$this->definition['refTable']->getComponentName()
                      . '.' . $this->definition['table']->getComponentName()
                      . ' WHERE '.$this->definition['refTable']->getComponentName()
                      . '.' . $this->definition['local'] . ' IN (' . $sub . ')';
        };

        return $dql;
    }

    public function fetchRelatedFor(IPF_ORM_Record $record)
    {
        $id = $record->getIncremented();


        if (empty($id) || ! $this->definition['table']->getAttribute(IPF_ORM::ATTR_LOAD_REFERENCES)) {
            return new IPF_ORM_Collection($this->getTable());
        } else {
            $q = new IPF_ORM_RawSql($this->getTable()->getConnection());

            $assocTable = $this->getAssociationFactory()->getTableName();
            $tableName  = $record->getTable()->getTableName();
            $identifierColumnNames = $record->getTable()->getIdentifierColumnNames();
            $identifier = array_pop($identifierColumnNames);
    
            $sub = 'SELECT ' . $this->getForeign()
                 . ' FROM ' . $assocTable 
                 . ' WHERE ' . $this->getLocal() 
                 . ' = ?';

            $condition[] = $tableName . '.' . $identifier . ' IN (' . $sub . ')';
            $joinCondition[] = $tableName . '.' . $identifier . ' = ' . $assocTable . '.' . $this->getForeign();

            if ($this->definition['equal']) {
                $sub2   = 'SELECT ' . $this->getLocal()
                        . ' FROM '  . $assocTable
                        . ' WHERE ' . $this->getForeign()
                        . ' = ?';

                $condition[] = $tableName . '.' . $identifier . ' IN (' . $sub2 . ')';
                $joinCondition[] = $tableName . '.' . $identifier . ' = ' . $assocTable . '.' . $this->getLocal();
            }
            $q->select('{'.$tableName.'.*}')
              ->addSelect('{'.$assocTable.'.*}')
              ->from($tableName . ' INNER JOIN ' . $assocTable . ' ON ' . implode(' OR ', $joinCondition))
              ->where(implode(' OR ', $condition));
            $q->addComponent($tableName,  $record->getTable()->getComponentName());
            $q->addComponent($assocTable, $record->getTable()->getComponentName(). '.' . $this->getAssociationFactory()->getComponentName());

            $params = ($this->definition['equal']) ? array($id, $id) : array($id);

            return $q->execute($params);
        }
    }
}
