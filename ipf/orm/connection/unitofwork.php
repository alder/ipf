<?php

class IPF_ORM_Connection_UnitOfWork extends IPF_ORM_Connection_Module
{
    public function saveGraph(IPF_ORM_Record $record)
    {
        $conn = $this->getConnection();

        $state = $record->state();
        if ($state === IPF_ORM_Record::STATE_LOCKED) {
            return false;
        }

        $record->state(IPF_ORM_Record::STATE_LOCKED);

        $conn->beginInternalTransaction();
        $saveLater = $this->saveRelated($record);

        $record->state($state);

        if ($record->isValid()) {
            $event = new IPF_ORM_Event($record, IPF_ORM_Event::RECORD_SAVE);
            $record->preSave($event);
            $record->getTable()->notifyRecordListeners('preSave', $event);
            $state = $record->state();

            if ( ! $event->skipOperation) {
                switch ($state) {
                    case IPF_ORM_Record::STATE_TDIRTY:
                        $this->insert($record);
                        break;
                    case IPF_ORM_Record::STATE_DIRTY:
                    case IPF_ORM_Record::STATE_PROXY:
                        $this->update($record);
                        break;
                    case IPF_ORM_Record::STATE_CLEAN:
                    case IPF_ORM_Record::STATE_TCLEAN:
                        break;
                }
            }

            // NOTE: what about referential integrity issues?
            foreach ($record->getPendingDeletes() as $pendingDelete) {
                $pendingDelete->delete();
            }

            $record->getTable()->notifyRecordListeners('postSave', $event);
            $record->postSave($event);
        } else {
            $conn->transaction->addInvalid($record);
        }

        $state = $record->state();

        $record->state(IPF_ORM_Record::STATE_LOCKED);

        foreach ($saveLater as $fk) {
            $alias = $fk->getAlias();

            if ($record->hasReference($alias)) {
                $obj = $record->$alias;
                // check that the related object is not an instance of IPF_ORM_Null
                if ( ! ($obj instanceof IPF_ORM_Null)) {
                    $obj->save($conn);
                }
            }
        }

        // save the MANY-TO-MANY associations
        $this->saveAssociations($record);

        $record->state($state);
        $conn->commit();

        return true;
    }

    public function save(IPF_ORM_Record $record)
    {
        $event = new IPF_ORM_Event($record, IPF_ORM_Event::RECORD_SAVE);

        $record->preSave($event);

        $record->getTable()->notifyRecordListeners('preSave', $event);

        if ( ! $event->skipOperation) {
            switch ($record->state()) {
                case IPF_ORM_Record::STATE_TDIRTY:
                    $this->insert($record);
                    break;
                case IPF_ORM_Record::STATE_DIRTY:
                case IPF_ORM_Record::STATE_PROXY:
                    $this->update($record);
                    break;
                case IPF_ORM_Record::STATE_CLEAN:
                case IPF_ORM_Record::STATE_TCLEAN:
                    // do nothing
                    break;
            }
        }

        $record->getTable()->notifyRecordListeners('postSave', $event);

        $record->postSave($event);
    }

    public function delete(IPF_ORM_Record $record)
    {
        $deletions = array();
        $this->_collectDeletions($record, $deletions);
        return $this->_executeDeletions($deletions);
    }

    private function _collectDeletions(IPF_ORM_Record $record, array &$deletions)
    {
        if ( ! $record->exists()) {
            return;
        }

        $deletions[$record->getOid()] = $record;
        $this->_cascadeDelete($record, $deletions);
    }

    private function _executeDeletions(array $deletions)
    {
        // collect class names
        $classNames = array();
        foreach ($deletions as $record) {
            $classNames[] = $record->getTable()->getComponentName();
        }
        $classNames = array_unique($classNames);

        // order deletes
        $executionOrder = $this->buildFlushTree($classNames);

        // execute
        try {
            $this->conn->beginInternalTransaction();

            for ($i = count($executionOrder) - 1; $i >= 0; $i--) {
                $className = $executionOrder[$i];
                $table = $this->conn->getTable($className);

                // collect identifiers
                $identifierMaps = array();
                $deletedRecords = array();
                foreach ($deletions as $oid => $record) {
                    if ($record->getTable()->getComponentName() == $className) {
                        $veto = $this->_preDelete($record);
                        if ( ! $veto) {
                            $identifierMaps[] = $record->identifier();
                            $deletedRecords[] = $record;
                            unset($deletions[$oid]);
                        }
                    }
                }

                if (count($deletedRecords) < 1) {
                    continue;
                }

                // extract query parameters (only the identifier values are of interest)
                $params = array();
                $columnNames = array();
                foreach ($identifierMaps as $idMap) {
                    while (list($fieldName, $value) = each($idMap)) {
                        $params[] = $value;
                        $columnNames[] = $table->getColumnName($fieldName);
                    }
                }
                $columnNames = array_unique($columnNames);

                // delete
                $tableName = $table->getTableName();
                $sql = "DELETE FROM " . $this->conn->quoteIdentifier($tableName) . " WHERE ";

                if ($table->isIdentifierComposite()) {
                    $sql .= $this->_buildSqlCompositeKeyCondition($columnNames, count($identifierMaps));
                    $this->conn->exec($sql, $params);
                } else {
                    $sql .= $this->_buildSqlSingleKeyCondition($columnNames, count($params));
                    $this->conn->exec($sql, $params);
                }

                // adjust state, remove from identity map and inform postDelete listeners
                foreach ($deletedRecords as $record) {
                    $record->state(IPF_ORM_Record::STATE_TCLEAN);
                    $record->getTable()->removeRecord($record);
                    $this->_postDelete($record);
                }
            }

            $this->conn->commit();
            // trigger postDelete for records skipped during the deletion (veto!)
            foreach ($deletions as $skippedRecord) {
                $this->_postDelete($skippedRecord);
            }

            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    private function _buildSqlSingleKeyCondition($columnNames, $numRecords)
    {
        $idColumn = $this->conn->quoteIdentifier($columnNames[0]);
        return implode(' OR ', array_fill(0, $numRecords, "$idColumn = ?"));
    }

    private function _buildSqlCompositeKeyCondition($columnNames, $numRecords)
    {
        $singleCondition = "";
        foreach ($columnNames as $columnName) {
            $columnName = $this->conn->quoteIdentifier($columnName);
            if ($singleCondition === "") {
                $singleCondition .= "($columnName = ?";
            } else {
                $singleCondition .= " AND $columnName = ?";
            }
        }
        $singleCondition .= ")";
        $fullCondition = implode(' OR ', array_fill(0, $numRecords, $singleCondition));

        return $fullCondition;
    }

    protected function _cascadeDelete(IPF_ORM_Record $record, array &$deletions)
    {
        foreach ($record->getTable()->getRelations() as $relation) {
             if ($relation->isCascadeDelete()) {
                 $fieldName = $relation->getAlias();
                 // if it's a xToOne relation and the related object is already loaded
                 // we don't need to refresh.
                 if ( ! ($relation->getType() == IPF_ORM_Relation::ONE && isset($record->$fieldName))) {
                     $record->refreshRelated($relation->getAlias());
                 }
                 $relatedObjects = $record->get($relation->getAlias());
                 if ($relatedObjects instanceof IPF_ORM_Record && $relatedObjects->exists()
                        && ! isset($deletions[$relatedObjects->getOid()])) {
                     $this->_collectDeletions($relatedObjects, $deletions);
                 } else if ($relatedObjects instanceof IPF_ORM_Collection && count($relatedObjects) > 0) {
                     // cascade the delete to the other objects
                     foreach ($relatedObjects as $object) {
                         if ( ! isset($deletions[$object->getOid()])) {
                             $this->_collectDeletions($object, $deletions);
                         }
                     }
                 }
             }
         }
     }

    public function saveRelated(IPF_ORM_Record $record)
    {
        $saveLater = array();
        foreach ($record->getReferences() as $k => $v) {
            $rel = $record->getTable()->getRelation($k);

            $local = $rel->getLocal();
            $foreign = $rel->getForeign();

            if ($rel instanceof IPF_ORM_Relation_ForeignKey) {
                $saveLater[$k] = $rel;
            } else if ($rel instanceof IPF_ORM_Relation_LocalKey) {
                // ONE-TO-ONE relationship
                $obj = $record->get($rel->getAlias());

                // Protection against infinite function recursion before attempting to save
                if ($obj instanceof IPF_ORM_Record && $obj->isModified()) {
                    $obj->save($this->conn);

                    /** Can this be removed?
                    $id = array_values($obj->identifier());

                    foreach ((array) $rel->getLocal() as $k => $field) {
                        $record->set($field, $id[$k]);
                    }
                    */
                }
            }
        }

        return $saveLater;
    }

    public function saveAssociations(IPF_ORM_Record $record)
    {
        foreach ($record->getReferences() as $k => $v) {
            $rel = $record->getTable()->getRelation($k);
            //print get_class($rel);
            if ($rel instanceof IPF_ORM_Relation_Association) {
                $v->save($this->conn);

                $assocTable = $rel->getAssociationTable();

                foreach ($v->getDeleteDiff() as $r) {
                    $query = 'DELETE FROM ' . $assocTable->getTableName()
                           . ' WHERE ' . $rel->getForeign() . ' = ?'
                           . ' AND ' . $rel->getLocal() . ' = ?';

                    $this->conn->execute($query, array($r->getIncremented(), $record->getIncremented()));
                }

                foreach ($v->getInsertDiff() as $r) {
                    $assocRecord = $assocTable->create();
                    $assocRecord->set($assocTable->getFieldName($rel->getForeign()), $r);
                    $assocRecord->set($assocTable->getFieldName($rel->getLocal()), $record);
                    $this->saveGraph($assocRecord);
                }
            }
        }
    }

    private function _preDelete(IPF_ORM_Record $record)
    {
        $event = new IPF_ORM_Event($record, IPF_ORM_Event::RECORD_DELETE);
        $record->preDelete($event);
        $record->getTable()->notifyRecordListeners('preDelete', $event);

        return $event->skipOperation;
    }

    private function _postDelete(IPF_ORM_Record $record)
    {
        $event = new IPF_ORM_Event($record, IPF_ORM_Event::RECORD_DELETE);
        $record->postDelete($event);
        $record->getTable()->notifyRecordListeners('postDelete', $event);
    }

    public function saveAll()
    {
        // get the flush tree
        $tree = $this->buildFlushTree($this->conn->getTables());

        // save all records
        foreach ($tree as $name) {
            $table = $this->conn->getTable($name);
            foreach ($table->getRepository() as $record) {
                $this->saveGraph($record);
            }
        }
    }

    public function update(IPF_ORM_Record $record)
    {
        $event = new IPF_ORM_Event($record, IPF_ORM_Event::RECORD_UPDATE);
        $record->preUpdate($event);
        $table = $record->getTable();
        $table->notifyRecordListeners('preUpdate', $event);

        if ( ! $event->skipOperation) {
            $identifier = $record->identifier();
            $array = $record->getPrepared();
            $this->conn->update($table, $array, $identifier);
            $record->assignIdentifier(true);
        }

        $table->notifyRecordListeners('postUpdate', $event);

        $record->postUpdate($event);

        return true;
    }

    public function insert(IPF_ORM_Record $record)
    {
        // listen the onPreInsert event
        $event = new IPF_ORM_Event($record, IPF_ORM_Event::RECORD_INSERT);
        $record->preInsert($event);
        $table = $record->getTable();
        $table->notifyRecordListeners('preInsert', $event);

        if ( ! $event->skipOperation) {
            $this->processSingleInsert($record);
        }

        $table->addRecord($record);
        $table->notifyRecordListeners('postInsert', $event);
        $record->postInsert($event);

        return true;
    }

    public function processSingleInsert(IPF_ORM_Record $record)
    {
        $fields = $record->getPrepared();
        $table = $record->getTable();

        // Populate fields with a blank array so that a blank records can be inserted
        if (empty($fields)) {
            foreach ($table->getFieldNames() as $field) {
                $fields[$field] = null;
            }
        }

        $identifier = (array) $table->getIdentifier();

        $seq = $record->getTable()->sequenceName;

        if ( ! empty($seq)) {
            $id = $this->conn->sequence->nextId($seq);
            $seqName = $table->getIdentifier();
            $fields[$seqName] = $id;

            $record->assignIdentifier($id);
        }

        $this->conn->insert($table, $fields);

        if (empty($seq) && count($identifier) == 1 && $identifier[0] == $table->getIdentifier() &&
            $table->getIdentifierType() != IPF_ORM::IDENTIFIER_NATURAL) {
            if (strtolower($this->conn->getDriverName()) == 'Pgsql') {
                $seq = $table->getTableName() . '_' . $identifier[0];
            }

            $id = $this->conn->sequence->lastInsertId($seq);

            if ( ! $id) {
                throw new IPF_ORM_Exception("Couldn't get last insert identifier.");
            }
            $record->assignIdentifier($id);
        } else {
            $record->assignIdentifier(true);
        }
    }

    public function buildFlushTree(array $tables)
    {
        // determine classes to order. only necessary because the $tables param
        // can contain strings or table objects...
        $classesToOrder = array();
        foreach ($tables as $table) {
            if ( ! ($table instanceof IPF_ORM_Table)) {
                $table = $this->conn->getTable($table, false);
            }
            $classesToOrder[] = $table->getComponentName();
        }
        $classesToOrder = array_unique($classesToOrder);

        if (count($classesToOrder) < 2) {
            return $classesToOrder;
        }

        // build the correct order
        $flushList = array();
        foreach ($classesToOrder as $class) {
            $table = $this->conn->getTable($class, false);
            $currentClass = $table->getComponentName();

            $index = array_search($currentClass, $flushList);

            if ($index === false) {
                //echo "adding $currentClass to flushlist";
                $flushList[] = $currentClass;
                $index = max(array_keys($flushList));
            }

            $rels = $table->getRelations();

            // move all foreignkey relations to the beginning
            foreach ($rels as $key => $rel) {
                if ($rel instanceof IPF_ORM_Relation_ForeignKey) {
                    unset($rels[$key]);
                    array_unshift($rels, $rel);
                }
            }

            foreach ($rels as $rel) {
                $relatedClassName = $rel->getTable()->getComponentName();

                if ( ! in_array($relatedClassName, $classesToOrder)) {
                    continue;
                }

                $relatedCompIndex = array_search($relatedClassName, $flushList);
                $type = $rel->getType();

                // skip self-referenced relations
                if ($relatedClassName === $currentClass) {
                    continue;
                }

                if ($rel instanceof IPF_ORM_Relation_ForeignKey) {
                    // the related component needs to come after this component in
                    // the list (since it holds the fk)

                    if ($relatedCompIndex !== false) {
                        // the component is already in the list
                        if ($relatedCompIndex >= $index) {
                            // it's already in the right place
                            continue;
                        }

                        unset($flushList[$index]);
                        // the related comp has the fk. so put "this" comp immediately
                        // before it in the list
                        array_splice($flushList, $relatedCompIndex, 0, $currentClass);
                        $index = $relatedCompIndex;
                    } else {
                        $flushList[] = $relatedClassName;
                    }

                } else if ($rel instanceof IPF_ORM_Relation_LocalKey) {
                    // the related component needs to come before the current component
                    // in the list (since this component holds the fk).

                    if ($relatedCompIndex !== false) {
                        // already in flush list
                        if ($relatedCompIndex <= $index) {
                            // it's in the right place
                            continue;
                        }

                        unset($flushList[$relatedCompIndex]);
                        // "this" comp has the fk. so put the related comp before it
                        // in the list
                        array_splice($flushList, $index, 0, $relatedClassName);
                    } else {
                        array_unshift($flushList, $relatedClassName);
                        $index++;
                    }
                } else if ($rel instanceof IPF_ORM_Relation_Association) {
                    // the association class needs to come after both classes
                    // that are connected through it in the list (since it holds
                    // both fks)

                    $assocTable = $rel->getAssociationFactory();
                    $assocClassName = $assocTable->getComponentName();

                    if ($relatedCompIndex !== false) {
                        unset($flushList[$relatedCompIndex]);
                    }

                    array_splice($flushList, $index, 0, $relatedClassName);
                    $index++;

                    $index3 = array_search($assocClassName, $flushList);

                    if ($index3 !== false) {
                        if ($index3 >= $index) {
                            continue;
                        }

                        unset($flushList[$index]);
                        array_splice($flushList, $index3, 0, $assocClassName);
                        $index = $relatedCompIndex;
                    } else {
                        $flushList[] = $assocClassName;
                    }
                }
            }
        }

        return array_values($flushList);
    }

    private function _formatDataSet(IPF_ORM_Record $record)
    {
        $table = $record->getTable();
        $dataSet = array();
        $component = $table->getComponentName();
        $array = $record->getPrepared();

        foreach ($table->getColumns() as $columnName => $definition) {
            if ( ! isset($dataSet[$component])) {
                $dataSet[$component] = array();
            }

            $fieldName = $table->getFieldName($columnName);
            if (isset($definition['primary']) && $definition['primary']) {
                continue;
            }

            if ( ! array_key_exists($fieldName, $array)) {
                continue;
            }

            if (isset($definition['owner'])) {
                $dataSet[$definition['owner']][$fieldName] = $array[$fieldName];
            } else {
                $dataSet[$component][$fieldName] = $array[$fieldName];
            }
        }

        return $dataSet;
    }
}

