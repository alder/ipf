<?php

class IPF_ORM_Collection extends IPF_ORM_Access implements Countable, IteratorAggregate, Serializable
{
    protected $data = array();
    protected $_table;
    protected $_snapshot = array();
    protected $reference;
    protected $referenceField;
    protected $relation;
    protected $keyColumn;
    protected static $null;

    public function __construct($table, $keyColumn = null)
    {
        if ( ! ($table instanceof IPF_ORM_Table)) {
            $table = IPF_ORM::getTable($table);
        }

        $this->_table = $table;

        if ($keyColumn === null) {
            $keyColumn = $table->getBoundQueryPart('indexBy');
        }

        if ($keyColumn === null) {
        	$keyColumn = $table->getAttribute(IPF_ORM::ATTR_COLL_KEY);
        }

        if ($keyColumn !== null) {
            $this->keyColumn = $keyColumn;
        }
    }

    public static function initNullObject(IPF_ORM_Null $null)
    {
        self::$null = $null;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function setData(array $data) 
    {
        $this->data = $data;
    }

    public function serialize()
    {
        $vars = get_object_vars($this);

        unset($vars['reference']);
        unset($vars['reference_field']);
        unset($vars['relation']);
        unset($vars['expandable']);
        unset($vars['expanded']);
        unset($vars['generator']);

        $vars['_table'] = $vars['_table']->getComponentName();

        return serialize($vars);
    }

    public function unserialize($serialized)
    {
        $manager    = IPF_ORM_Manager::getInstance();
        $connection    = $manager->getCurrentConnection();

        $array = unserialize($serialized);

        foreach ($array as $name => $values) {
            $this->$name = $values;
        }

        $this->_table = $connection->getTable($this->_table);

        $keyColumn = isset($array['keyColumn']) ? $array['keyColumn'] : null;
        if ($keyColumn === null) {
            $keyColumn = $this->_table->getBoundQueryPart('indexBy');
        }

        if ($keyColumn !== null) {
            $this->keyColumn = $keyColumn;
        }
    }

    public function setKeyColumn($column)
    {
        $this->keyColumn = $column;
        
        return $this;
    }

    public function getKeyColumn()
    {
        return $this->keyColumn;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFirst()
    {
        return reset($this->data);
    }

    public function getLast()
    {
        return end($this->data);
    }

    public function end()
    {
        return end($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function setReference(IPF_ORM_Record $record, IPF_ORM_Relation $relation)
    {
        $this->reference = $record;
        $this->relation  = $relation;

        if ($relation instanceof IPF_ORM_Relation_ForeignKey || 
            $relation instanceof IPF_ORM_Relation_LocalKey) {

            $this->referenceField = $relation->getForeignFieldName();

            $value = $record->get($relation->getLocalFieldName());

            foreach ($this->data as $record) {
                if ($value !== null) {
                    $record->set($this->referenceField, $value, false);
                } else {
                    $record->set($this->referenceField, $this->reference, false);
                }
            }
        } elseif ($relation instanceof IPF_ORM_Relation_Association) {

        }
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function remove($key)
    {
        $removed = $this->data[$key];

        unset($this->data[$key]);
        return $removed;
    }

    public function contains($key)
    {
        return isset($this->data[$key]);
    }

    public function search(IPF_ORM_Record $record)
    {
        return array_search($record, $this->data, true);
    }

    public function get($key)
    {
        if ( ! isset($this->data[$key])) {
            $record = $this->_table->create();

            if (isset($this->referenceField)) {
                $value = $this->reference->get($this->relation->getLocalFieldName());

                if ($value !== null) {
                    $record->set($this->referenceField, $value, false);
                } else {
                    $record->set($this->referenceField, $this->reference, false);
                }
            }
            if ($key === null) {
                $this->data[] = $record;
            } else {
                $this->data[$key] = $record;
            }

            if (isset($this->keyColumn)) {
                $record->set($this->keyColumn, $key);
            }

            return $record;
        }

        return $this->data[$key];
    }

    public function getPrimaryKeys()
    {
        $list = array();
        $name = $this->_table->getIdentifier();

        foreach ($this->data as $record) {
            if (is_array($record) && isset($record[$name])) {
                $list[] = $record[$name];
            } else {
                $list[] = $record->getIncremented();
            }
        }
        return $list;
    }

    public function getKeys()
    {
        return array_keys($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function set($key, $record)
    {
        if (isset($this->referenceField)) {
            $record->set($this->referenceField, $this->reference, false);
        }

        $this->data[$key] = $record;
    }

    public function add($record, $key = null)
    {
        if (isset($this->referenceField)) {
            $value = $this->reference->get($this->relation->getLocalFieldName());

            if ($value !== null) {
                $record->set($this->referenceField, $value, false);
            } else {
                $record->set($this->referenceField, $this->reference, false);
            }
        }
        /**
         * for some weird reason in_array cannot be used here (php bug ?)
         *
         * if used it results in fatal error : [ nesting level too deep ]
         */
        foreach ($this->data as $val) {
            if ($val === $record) {
                return false;
            }
        }

        if (isset($key)) {
            if (isset($this->data[$key])) {
                return false;
            }
            $this->data[$key] = $record;
            return true;
        }

        if (isset($this->keyColumn)) {
            $value = $record->get($this->keyColumn);
            if ($value === null) {
                throw new IPF_ORM_Exception("Couldn't create collection index. Record field '".$this->keyColumn."' was null.");
            }
            $this->data[$value] = $record;
        } else {
            $this->data[] = $record;
        }

        return true;
    }
    
    public function merge(IPF_ORM_Collection $coll)
    {
        $localBase = $this->getTable()->getComponentName();
        $otherBase = $coll->getTable()->getComponentName();
        
        if ($otherBase != $localBase && !is_subclass_of($otherBase, $localBase) ) {
            throw new IPF_ORM_Exception("Can't merge collections with incompatible record types");
        }
        
        foreach ($coll->getData() as $record) {
            $this->add($record);
        }
        
        return $this;
    }

    public function loadRelated($name = null)
    {
        $list = array();
        $query   = new IPF_ORM_Query($this->_table->getConnection());

        if ( ! isset($name)) {
            foreach ($this->data as $record) {
                $value = $record->getIncremented();
                if ($value !== null) {
                    $list[] = $value;
                }
            }
            $query->from($this->_table->getComponentName());
            $query->where($this->_table->getComponentName() . '.id IN (' . substr(str_repeat("?, ", count($list)),0,-2) . ')');

            return $query;
        }

        $rel     = $this->_table->getRelation($name);

        if ($rel instanceof IPF_ORM_Relation_LocalKey || $rel instanceof IPF_ORM_Relation_ForeignKey) {
            foreach ($this->data as $record) {
                $list[] = $record[$rel->getLocal()];
            }
        } else {
            foreach ($this->data as $record) {
                $value = $record->getIncremented();
                if ($value !== null) {
                    $list[] = $value;
                }
            }
        }

        $dql     = $rel->getRelationDql(count($list), 'collection');

        $coll    = $query->query($dql, $list);

        $this->populateRelated($name, $coll);
    }

    public function populateRelated($name, IPF_ORM_Collection $coll)
    {
        $rel     = $this->_table->getRelation($name);
        $table   = $rel->getTable();
        $foreign = $rel->getForeign();
        $local   = $rel->getLocal();

        if ($rel instanceof IPF_ORM_Relation_LocalKey) {
            foreach ($this->data as $key => $record) {
                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $this->data[$key]->setRelated($name, $related);
                    }
                }
            }
        } elseif ($rel instanceof IPF_ORM_Relation_ForeignKey) {
            foreach ($this->data as $key => $record) {
                if ( ! $record->exists()) {
                    continue;
                }
                $sub = new IPF_ORM_Collection($table);

                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $sub->add($related);
                        $coll->remove($k);
                    }
                }

                $this->data[$key]->setRelated($name, $sub);
            }
        } elseif ($rel instanceof IPF_ORM_Relation_Association) {
            $identifier = $this->_table->getIdentifier();
            $asf        = $rel->getAssociationFactory();
            $name       = $table->getComponentName();

            foreach ($this->data as $key => $record) {
                if ( ! $record->exists()) {
                    continue;
                }
                $sub = new IPF_ORM_Collection($table);
                foreach ($coll as $k => $related) {
                    if ($related->get($local) == $record[$identifier]) {
                        $sub->add($related->get($name));
                    }
                }
                $this->data[$key]->setRelated($name, $sub);

            }
        }
    }

    public function getNormalIterator()
    {
        return new IPF_ORM_Collection_Iterator_Normal($this);
    }

    public function takeSnapshot()
    {
        $this->_snapshot = $this->data;
        
        return $this;
    }

    public function getSnapshot()
    {
        return $this->_snapshot;
    }

    public function processDiff() 
    {
        foreach (array_udiff($this->_snapshot, $this->data, array($this, "compareRecords")) as $record) {
            $record->delete();
        }

        return $this;
    }

    public function toArray($deep = false, $prefixKey = false)
    {
        $data = array();
        foreach ($this as $key => $record) {
            
            $key = $prefixKey ? get_class($record) . '_' .$key:$key;
            
            $data[$key] = $record->toArray($deep, $prefixKey);
        }
        
        return $data;
    }

    public function fromArray($array, $deep = true)
    {
        $data = array();
        foreach ($array as $rowKey => $row) {
            $this[$rowKey]->fromArray($row, $deep);
        }
    }

    public function synchronizeWithArray(array $array)
    {
        foreach ($this as $key => $record) {
            if (isset($array[$key])) {
                $record->synchronizeWithArray($array[$key]);
                unset($array[$key]);
            } else {
                // remove records that don't exist in the array
                $this->remove($key);
            }
        }
        // create new records for each new row in the array
        foreach ($array as $rowKey => $row) {
            $this[$rowKey]->fromArray($row);
        }
    }
    public function synchronizeFromArray(array $array)
    {
        return $this->synchronizeWithArray($array);
    }

    public function getDeleteDiff()
    {
        return array_udiff($this->_snapshot, $this->data, array($this, 'compareRecords'));
    }

    public function getInsertDiff()
    {
        return array_udiff($this->data, $this->_snapshot, array($this, "compareRecords"));
    }

    protected function compareRecords($a, $b)
    {
        if ($a->getOid() == $b->getOid()) {
            return 0;
        }
        
        return ($a->getOid() > $b->getOid()) ? 1 : -1;
    }

    public function save(IPF_ORM_Connection $conn = null)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }
        
        $conn->beginInternalTransaction();

        $conn->transaction->addCollection($this);

        $this->processDiff();

        foreach ($this->getData() as $key => $record) {
            $record->save($conn);
        }

        $conn->commit();

        return $this;
    }

    public function delete(IPF_ORM_Connection $conn = null, $clearColl = true)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }

        $conn->beginInternalTransaction();
        $conn->transaction->addCollection($this);

        foreach ($this as $key => $record) {
            $record->delete($conn);
        }

        $conn->commit();
        
        if ($clearColl) {
            $this->clear();
        }
        
        return $this;
    }
    
    public function clear()
    {
        $this->data = array();
    }

    public function free($deep = false)
    {
        foreach ($this->getData() as $key => $record) {
            if ( ! ($record instanceof IPF_ORM_Null)) {
                $record->free($deep);
            }
        }

        $this->data = array();

        if ($this->reference) {
            $this->reference->free($deep);
            $this->reference = null;
        }
    }

    public function getIterator()
    {
        $data = $this->data;
        return new ArrayIterator($data);
    }

    public function __toString()
    {
        return IPF_ORM_Utils::getCollectionAsString($this);
    }
    
    public function getRelation()
    {
        return $this->relation;
    }
}
