<?php

abstract class IPF_ORM_Record extends IPF_ORM_Record_Abstract implements Countable, IteratorAggregate, Serializable
{
    const STATE_DIRTY       = 1;
    const STATE_TDIRTY      = 2;
    const STATE_CLEAN       = 3;
    const STATE_PROXY       = 4;
    const STATE_TCLEAN      = 5;
    const STATE_LOCKED     = 6;

    protected $_node;
    protected $_id           = array();
    protected $_data         = array();
    protected $_values       = array();
    protected $_state;
    protected $_modified     = array();
    protected $_errorStack;
    protected $_references     = array();
    protected $_pendingDeletes = array();
    protected $_custom         = array();
    private static $_index = 1;
    private $_oid;

    public function __construct($table = null, $isNewEntry = false)
    {
        if (isset($table) && $table instanceof IPF_ORM_Table) {
            $this->_table = $table;
            $exists = ( ! $isNewEntry);
        } else {
            // get the table of this class
            $class = get_class($this);
            $this->_table = IPF_ORM::getTable($class);
            $exists = false;
        }

        // Check if the current connection has the records table in its registry
        // If not this record is only used for creating table definition and setting up
        // relations.
        if ( ! $this->_table->getConnection()->hasTable($this->_table->getComponentName())) {
            return;
        }

        $this->_oid = self::$_index;

        self::$_index++;

        // get the data array
        $this->_data = $this->_table->getData();

        // get the column count
        $count = count($this->_data);

        $this->_values = $this->cleanData($this->_data);

        $this->prepareIdentifiers($exists);

        if ( ! $exists) {
            if ($count > count($this->_values)) {
                $this->_state = IPF_ORM_Record::STATE_TDIRTY;
            } else {
                $this->_state = IPF_ORM_Record::STATE_TCLEAN;
            }

            // set the default values for this record
            $this->assignDefaultValues();
        } else {
            $this->_state = IPF_ORM_Record::STATE_CLEAN;

            if ($count < $this->_table->getColumnCount()) {
                $this->_state  = IPF_ORM_Record::STATE_PROXY;
            }
        }

        $repository = $this->_table->getRepository();
        $repository->add($this);

        $this->construct();
    }

    public static function _index()
    {
        return self::$_index;
    }

    public function setUp(){}
    public function construct(){}

    public function getOid()
    {
        return $this->_oid;
    }

    public function oid()
    {
        return $this->_oid;
    }

    public function isValid()
    {
        if ( ! $this->_table->getAttribute(IPF_ORM::ATTR_VALIDATE)) {
            return true;
        }
        // Clear the stack from any previous errors.
        $this->getErrorStack()->clear();

        // Run validation process
        $validator = new IPF_ORM_Validator();
        $validator->validateRecord($this);
        $this->validate();
        if ($this->_state == self::STATE_TDIRTY || $this->_state == self::STATE_TCLEAN) {
            $this->validateOnInsert();
        } else {
            $this->validateOnUpdate();
        }

        return $this->getErrorStack()->count() == 0 ? true : false;
    }

    protected function validate(){}

    protected function validateOnUpdate(){}

    protected function validateOnInsert(){}

    public function preSerialize($event){}

    public function postSerialize($event){}

    public function preUnserialize($event){}

    public function postUnserialize($event){}

    public function preSave($event){}

    public function postSave($event){}

    public function preDelete($event){}

    public function postDelete($event){}

    public function preUpdate($event){}

    public function postUpdate($event){}

    public function preInsert($event){}

    public function postInsert($event){}

    public function preDqlSelect($event){}

    public function preDqlUpdate($event){}

    public function preDqlDelete($event){}

    public function getErrorStack(){
        if ( ! $this->_errorStack) {
            $this->_errorStack = new IPF_ORM_Validator_ErrorStack(get_class($this));
        }

        return $this->_errorStack;
    }

    public function errorStack($stack = null)
    {
        if ($stack !== null) {
            if ( ! ($stack instanceof IPF_ORM_Validator_ErrorStack)) {
               throw new IPF_ORM_Exception('Argument should be an instance of IPF_ORM_Validator_ErrorStack.');
            }
            $this->_errorStack = $stack;
        } else {
            return $this->getErrorStack();
        }
    }

    public function assignDefaultValues($overwrite = false)
    {
        if ( ! $this->_table->hasDefaultValues()) {
            return false;
        }
        foreach ($this->_data as $column => $value) {
            $default = $this->_table->getDefaultValueOf($column);

            if ($default === null) {
                continue;
            }

            if ($value === self::$_null || $overwrite) {
                $this->_data[$column] = $default;
                $this->_modified[]    = $column;
                $this->_state = IPF_ORM_Record::STATE_TDIRTY;
            }
        }
    }

    public function cleanData(&$data)
    {
        $tmp = $data;
        $data = array();

        foreach ($this->getTable()->getFieldNames() as $fieldName) {
            if (isset($tmp[$fieldName])) {
                $data[$fieldName] = $tmp[$fieldName];
            } else if (array_key_exists($fieldName, $tmp)) {
                $data[$fieldName] = self::$_null;
            } else if (!isset($this->_data[$fieldName])) {
                $data[$fieldName] = self::$_null;
            }
            unset($tmp[$fieldName]);
        }

        return $tmp;
    }

    public function hydrate(array $data)
    {
        $this->_values = array_merge($this->_values, $this->cleanData($data));
        $this->_data   = array_merge($this->_data, $data);
        $this->prepareIdentifiers(true);
    }

    private function prepareIdentifiers($exists = true)
    {
        switch ($this->_table->getIdentifierType()) {
            case IPF_ORM::IDENTIFIER_AUTOINC:
            case IPF_ORM::IDENTIFIER_SEQUENCE:
            case IPF_ORM::IDENTIFIER_NATURAL:
                $name = $this->_table->getIdentifier();
                if (is_array($name)) {
                    $name = $name[0];
                }
                if ($exists) {
                    if (isset($this->_data[$name]) && $this->_data[$name] !== self::$_null) {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }
                break;
            case IPF_ORM::IDENTIFIER_COMPOSITE:
                $names = $this->_table->getIdentifier();

                foreach ($names as $name) {
                    if ($this->_data[$name] === self::$_null) {
                        $this->_id[$name] = null;
                    } else {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }
                break;
        }
    }

    public function serialize()
    {
        $event = new IPF_ORM_Event($this, IPF_ORM_Event::RECORD_SERIALIZE);

        $this->preSerialize($event);

        $vars = get_object_vars($this);

        unset($vars['_references']);
        unset($vars['_table']);
        unset($vars['_errorStack']);
        unset($vars['_filter']);
        unset($vars['_node']);

        $name = $this->_table->getIdentifier();
        $this->_data = array_merge($this->_data, $this->_id);

        foreach ($this->_data as $k => $v) {
            if ($v instanceof IPF_ORM_Record && $this->_table->getTypeOf($k) != 'object') {
                unset($vars['_data'][$k]);
            } elseif ($v === self::$_null) {
                unset($vars['_data'][$k]);
            } else {
                switch ($this->_table->getTypeOf($k)) {
                    case 'array':
                    case 'object':
                        $vars['_data'][$k] = serialize($vars['_data'][$k]);
                        break;
                    case 'gzip':
                        $vars['_data'][$k] = gzcompress($vars['_data'][$k]);
                        break;
                    case 'enum':
                        $vars['_data'][$k] = $this->_table->enumIndex($k, $vars['_data'][$k]);
                        break;
                }
            }
        }

        $str = serialize($vars);

        $this->postSerialize($event);

        return $str;
    }

    public function unserialize($serialized)
    {
        $event = new IPF_ORM_Event($this, IPF_ORM_Event::RECORD_UNSERIALIZE);

        $this->preUnserialize($event);

        $manager    = IPF_ORM_Manager::getInstance();
        $connection = $manager->getConnectionForComponent(get_class($this));

        $this->_oid = self::$_index;
        self::$_index++;

        $this->_table = $connection->getTable(get_class($this));

        $array = unserialize($serialized);

        foreach($array as $k => $v) {
            $this->$k = $v;
        }

        foreach ($this->_data as $k => $v) {
            switch ($this->_table->getTypeOf($k)) {
                case 'array':
                case 'object':
                    $this->_data[$k] = unserialize($this->_data[$k]);
                    break;
                case 'gzip':
                   $this->_data[$k] = gzuncompress($this->_data[$k]);
                    break;
                case 'enum':
                    $this->_data[$k] = $this->_table->enumValue($k, $this->_data[$k]);
                    break;

            }
        }

        $this->_table->getRepository()->add($this);

        $this->cleanData($this->_data);

        $this->prepareIdentifiers($this->exists());

        $this->postUnserialize($event);
    }

    public function state($state = null)
    {
        if ($state == null) {
            return $this->_state;
        }

        $err = false;
        if (is_integer($state)) {
            if ($state >= 1 && $state <= 6) {
                $this->_state = $state;
            } else {
                $err = true;
            }
        } else if (is_string($state)) {
            $upper = strtoupper($state);

            $const = 'IPF_ORM_Record::STATE_' . $upper;
            if (defined($const)) {
                $this->_state = constant($const);
            } else {
                $err = true;
            }
        }

        if ($this->_state === IPF_ORM_Record::STATE_TCLEAN ||
                $this->_state === IPF_ORM_Record::STATE_CLEAN) {
            $this->_modified = array();
        }

        if ($err) {
            throw new IPF_ORM_Exception('Unknown record state ' . $state);
        }
    }

    public function refresh($deep = false)
    {
        $id = $this->identifier();
        if ( ! is_array($id)) {
            $id = array($id);
        }
        if (empty($id)) {
            return false;
        }
        $id = array_values($id);

        if ($deep) {
            $query = $this->getTable()->createQuery();
            foreach (array_keys($this->_references) as $name) {
                $query->leftJoin(get_class($this) . '.' . $name);
            }
            $query->where(implode(' = ? AND ', $this->getTable()->getIdentifierColumnNames()) . ' = ?');
            $this->clearRelated();
            $record = $query->fetchOne($id);
        } else {
            // Use FETCH_ARRAY to avoid clearing object relations
            $record = $this->getTable()->find($id, IPF_ORM::HYDRATE_ARRAY);
            if ($record) {
                $this->hydrate($record);
            }
        }

        if ($record === false) {
            throw new IPF_ORM_Exception('Failed to refresh. Record does not exist.');
        }

        $this->_modified = array();

        $this->prepareIdentifiers();

        $this->_state = IPF_ORM_Record::STATE_CLEAN;

        return $this;
    }

    public function refreshRelated($name = null)
    {
        if (is_null($name)) {
            foreach ($this->_table->getRelations() as $rel) {
                $this->_references[$rel->getAlias()] = $rel->fetchRelatedFor($this);
            }
        } else {
            $rel = $this->_table->getRelation($name);
            $this->_references[$name] = $rel->fetchRelatedFor($this);
        }
    }

    public function clearRelated()
    {
        $this->_references = array();
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function rawGet($fieldName)
    {
        if ( ! isset($this->_data[$fieldName])) {
            throw new IPF_ORM_Exception('Unknown property '. $fieldName);
        }
        if ($this->_data[$fieldName] === self::$_null) {
            return null;
        }

        return $this->_data[$fieldName];
    }

    public function load()
    {
        // only load the data from database if the IPF_ORM_Record is in proxy state
        if ($this->_state == IPF_ORM_Record::STATE_PROXY) {
            $this->refresh();
            $this->_state = IPF_ORM_Record::STATE_CLEAN;
            return true;
        }
        return false;
    }

    public function get($fieldName, $load = true)
    {
        $value = self::$_null;

        if (isset($this->_data[$fieldName])) {
            // check if the value is the IPF_ORM_Null object located in self::$_null)
            if ($this->_data[$fieldName] === self::$_null && $load) {
                $this->load();
            }
            if ($this->_data[$fieldName] === self::$_null) {
                $value = null;
            } else {
                $value = $this->_data[$fieldName];
            }
            return $value;
        }

        if (isset($this->_values[$fieldName])) {
            return $this->_values[$fieldName];
        }

        try {
            if ( ! isset($this->_references[$fieldName]) && $load) {
                $rel = $this->_table->getRelation($fieldName);
                $this->_references[$fieldName] = $rel->fetchRelatedFor($this);
            }
            return $this->_references[$fieldName];
        } catch (IPF_ORM_Exception_Table $e) {
            foreach ($this->_table->getFilters() as $filter) {
                if (($value = $filter->filterGet($this, $fieldName, $value)) !== null) {
                    return $value;
                }
            }
        }
    }

    public function mapValue($name, $value)
    {
        $this->_values[$name] = $value;
    }

    public function set($fieldName, $value, $load = true)
    {
        if (isset($this->_data[$fieldName])) {
            $type = $this->_table->getTypeOf($fieldName);
            if ($value instanceof IPF_ORM_Record) {
                $id = $value->getIncremented();

                if ($id !== null && $type !== 'object') {
                    $value = $id;
                }
            }

            if ($load) {
                $old = $this->get($fieldName, $load);
            } else {
                $old = $this->_data[$fieldName];
            }

            if ($this->_isValueModified($type, $old, $value)) {
                if ($value === null) {
                    $value = self::$_null;
                }

                $this->_data[$fieldName] = $value;
                $this->_modified[] = $fieldName;
                switch ($this->_state) {
                    case IPF_ORM_Record::STATE_CLEAN:
                        $this->_state = IPF_ORM_Record::STATE_DIRTY;
                        break;
                    case IPF_ORM_Record::STATE_TCLEAN:
                        $this->_state = IPF_ORM_Record::STATE_TDIRTY;
                        break;
                }
            }
        } else {
            try {
                $this->coreSetRelated($fieldName, $value);
            } catch (IPF_ORM_Exception_Table $e) {
                foreach ($this->_table->getFilters() as $filter) {
                    if (($value = $filter->filterSet($this, $fieldName, $value)) !== null) {
                        break;
                    }
                }
            }
        }

        return $this;
    }

    protected function _isValueModified($type, $old, $new)
    {
        if ($type == 'boolean' && (is_bool($old) || is_numeric($old)) && (is_bool($new) || is_numeric($new)) && $old == $new) {
            return false;
        } else {
            return $old !== $new;
        }
    }

    public function coreSetRelated($name, $value)
    {
        $rel = $this->_table->getRelation($name);

        if ($value === null) {
            $value = self::$_null;
        }

        // one-to-many or one-to-one relation
        if ($rel instanceof IPF_ORM_Relation_ForeignKey || $rel instanceof IPF_ORM_Relation_LocalKey) {
            if ( ! $rel->isOneToOne()) {
                // one-to-many relation found
                if ( ! ($value instanceof IPF_ORM_Collection)) {
                    throw new IPF_ORM_Exception("Couldn't call IPF_ORM::set(), second argument should be an instance of IPF_ORM_Collection when setting one-to-many references.");
                }
                if (isset($this->_references[$name])) {
                    $this->_references[$name]->setData($value->getData());
                    return $this;
                }
            } else {
                if ($value !== self::$_null) {
                    $relatedTable = $value->getTable();
                    $foreignFieldName = $relatedTable->getFieldName($rel->getForeign());
                    $localFieldName = $this->_table->getFieldName($rel->getLocal());

                    // one-to-one relation found
                    if ( ! ($value instanceof IPF_ORM_Record)) {
                        throw new IPF_ORM_Exception("Couldn't call IPF_ORM::set(), second argument should be an instance of IPF_ORM_Record or IPF_ORM_Null when setting one-to-one references.");
                    }
                    if ($rel instanceof IPF_ORM_Relation_LocalKey) {
                        if ( ! empty($foreignFieldName) && $foreignFieldName != $value->getTable()->getIdentifier()) {
                            $this->set($localFieldName, $value->rawGet($foreignFieldName), false);
                        } else {
                            $this->set($localFieldName, $value, false);
                        }
                    } else {
                        $value->set($foreignFieldName, $this, false);
                    }
                }
            }

        } else if ($rel instanceof IPF_ORM_Relation_Association) {
            // join table relation found
            if ( ! ($value instanceof IPF_ORM_Collection)) {
                throw new IPF_ORM_Exception("Couldn't call IPF_ORM::set(), second argument should be an instance of IPF_ORM_Collection when setting many-to-many references.");
            }
        }

        $this->_references[$name] = $value;
    }

    public function contains($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            // this also returns true if the field is a IPF_ORM_Null.
            // imho this is not correct behavior.
            return true;
        }
        if (isset($this->_id[$fieldName])) {
            return true;
        }
        if (isset($this->_values[$fieldName])) {
            return true;
        }
        if (isset($this->_references[$fieldName]) &&
            $this->_references[$fieldName] !== self::$_null) {

            return true;
        }
        return false;
    }

    public function __unset($name)
    {
        if (isset($this->_data[$name])) {
            $this->_data[$name] = array();
        } else if (isset($this->_references[$name])) {
            if ($this->_references[$name] instanceof IPF_ORM_Record) {
                $this->_pendingDeletes[] = $this->$name;
                $this->_references[$name] = self::$_null;
            } elseif ($this->_references[$name] instanceof IPF_ORM_Collection) {
                $this->_pendingDeletes[] = $this->$name;
                $this->_references[$name]->setData(array());
            }
        }
    }

    public function getPendingDeletes()
    {
        return $this->_pendingDeletes;
    }

    public function save(IPF_ORM_Connection $conn = null)
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }
        $conn->unitOfWork->saveGraph($this);
    }

    public function trySave(IPF_ORM_Connection $conn = null) {
        try {
            $this->save($conn);
            return true;
        } catch (IPF_ORM_Exception_Validator $ignored) {
            return false;
        }
    }

    public function replace(IPF_ORM_Connection $conn = null)
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }

        if ($this->exists()) {
            return $this->save();
        } else {
            $identifier = (array) $this->getTable()->getIdentifier();
            return $conn->replace($this->_table, $this->toArray(), $identifier);
        }
    }

    public function getModified()
    {
        $a = array();

        foreach ($this->_modified as $k => $v) {
            $a[$v] = $this->_data[$v];
        }
        return $a;
    }

    public function modifiedFields()
    {
        $a = array();

        foreach ($this->_modified as $k => $v) {
            $a[$v] = $this->_data[$v];
        }
        return $a;
    }

    public function getPrepared(array $array = array())
    {
        $a = array();

        if (empty($array)) {
            $modifiedFields = $this->_modified;
        }

        foreach ($modifiedFields as $field) {
            $type = $this->_table->getTypeOf($field);

            if ($this->_data[$field] === self::$_null) {
                $a[$field] = null;
                continue;
            }

            switch ($type) {
                case 'array':
                case 'object':
                    $a[$field] = serialize($this->_data[$field]);
                    break;
                case 'gzip':
                    $a[$field] = gzcompress($this->_data[$field],5);
                    break;
                case 'boolean':
                    $a[$field] = $this->getTable()->getConnection()->convertBooleans($this->_data[$field]);
                break;
                case 'enum':
                    $a[$field] = $this->_table->enumIndex($field, $this->_data[$field]);
                    break;
                default:
                    if ($this->_data[$field] instanceof IPF_ORM_Record) {
                        $a[$field] = $this->_data[$field]->getIncremented();
                        if ($a[$field] !== null) {
                            $this->_data[$field] = $a[$field];
                        }
                    } else {
                        $a[$field] = $this->_data[$field];
                    }
                    /** TODO:
                    if ($this->_data[$v] === null) {
                        throw new IPF_ORM_Record_Exception('Unexpected null value.');
                    }
                    */
            }
        }
        $map = $this->_table->inheritanceMap;
        foreach ($map as $k => $v) {
            $k = $this->_table->getFieldName($k);
            $old = $this->get($k, false);

            if ((string) $old !== (string) $v || $old === null) {
                $a[$k] = $v;
                $this->_data[$k] = $v;
            }
        }

        return $a;
    }

    public function count()
    {
        return count($this->_data);
    }

    public function columnCount()
    {
        return $this->count();
    }

    public function toArray($deep = true, $prefixKey = false)
    {
        if ($this->_state == self::STATE_LOCKED) {
            return false;
        }

        $stateBeforeLock = $this->_state;
        $this->_state = self::STATE_LOCKED;

        $a = array();

        foreach ($this as $column => $value) {
            if ($value === self::$_null || is_object($value)) {
                $value = null;
            }

            $a[$column] = $value;
        }

        if ($this->_table->getIdentifierType() ==  IPF_ORM::IDENTIFIER_AUTOINC) {
            $i      = $this->_table->getIdentifier();
            $a[$i]  = $this->getIncremented();
        }

        if ($deep) {
            foreach ($this->_references as $key => $relation) {
                if (! $relation instanceof IPF_ORM_Null) {
                    $a[$key] = $relation->toArray($deep, $prefixKey);
                }
            }
        }

        // [FIX] Prevent mapped IPF_ORM_Records from being displayed fully
        foreach ($this->_values as $key => $value) {
            if ($value instanceof IPF_ORM_Record) {
                $a[$key] = $value->toArray($deep, $prefixKey);
            } else {
                $a[$key] = $value;
            }
        }

        $this->_state = $stateBeforeLock;

        return $a;
    }

    public function merge($data, $deep = true)
    {
        if ($data instanceof $this) {
            $array = $data->toArray($deep);
        } else if (is_array($data)) {
            $array = $data;
        }

        return $this->fromArray($array, $deep);
    }

    public function fromArray(array $array, $deep = true)
    {
        $refresh = false;
        foreach ($array as $key => $value) {
            if ($key == '_identifier') {
                $refresh = true;
                $this->assignIdentifier((array) $value);
                continue;
            }

            if ($deep && $this->getTable()->hasRelation($key)) {
                $this->$key->fromArray($value, $deep);
            } else if ($this->getTable()->hasField($key)) {
                $this->set($key, $value);
            }
        }

        if ($refresh) {
            $this->refresh();
        }
    }

    protected function _synchronizeWithArrayForRelation($key, $value)
    {
        $this->get($key)->synchronizeWithArray($value);
    }

    public function synchronizeWithArray(array $array, $deep = true)
    {
        $refresh = false;
        foreach ($array as $key => $value) {
            if ($key == '_identifier') {
                $refresh = true;
                $this->assignIdentifier((array) $value);
                continue;
            }
            if ($deep && $this->getTable()->hasRelation($key)) {
                $this->_synchronizeWithArrayForRelation($key, $value);
            } else if ($this->getTable()->hasField($key)) {
                $this->set($key, $value);
            }
        }
        // eliminate relationships missing in the $array
        foreach ($this->_references as $name => $obj) {
            if ( ! isset($array[$name])) {
                unset($this->$name);
            }
        }
        if ($refresh) {
            $this->refresh();
        }
    }

    public function exportTo($type, $deep = true)
    {
        if ($type == 'array') {
            return $this->toArray($deep);
        } else {
            return IPF_ORM_Parser::dump($this->toArray($deep, true), $type);
        }
    }

    public function importFrom($type, $data)
    {
        if ($type == 'array') {
            return $this->fromArray($data);
        } else {
            return $this->fromArray(IPF_ORM_Parser::load($data, $type));
        }
    }

    public function exists()
    {
        return ($this->_state !== IPF_ORM_Record::STATE_TCLEAN &&
                $this->_state !== IPF_ORM_Record::STATE_TDIRTY);
    }

    public function isModified()
    {
        return ($this->_state === IPF_ORM_Record::STATE_DIRTY ||
                $this->_state === IPF_ORM_Record::STATE_TDIRTY);
    }

    public function hasRelation($fieldName)
    {
        if (isset($this->_data[$fieldName]) || isset($this->_id[$fieldName])) {
            return true;
        }
        return $this->_table->hasRelation($fieldName);
    }

    public function getIterator()
    {
        return new IPF_ORM_Record_Iterator($this);
    }

    public function delete(IPF_ORM_Connection $conn = null)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }
        return $conn->unitOfWork->delete($this);
    }

    public function copy($deep = false)
    {
        $data = $this->_data;

        if ($this->_table->getIdentifierType() === IPF_ORM::IDENTIFIER_AUTOINC) {
            $id = $this->_table->getIdentifier();

            unset($data[$id]);
        }

        $ret = $this->_table->create($data);
        $modified = array();

        foreach ($data as $key => $val) {
            if ( ! ($val instanceof IPF_ORM_Null)) {
                $ret->_modified[] = $key;
            }
        }

        if ($deep) {
            foreach ($this->_references as $key => $value) {
                if ($value instanceof IPF_ORM_Collection) {
                    foreach ($value as $record) {
                        $ret->{$key}[] = $record->copy($deep);
                    }
                } else if($value instanceof IPF_ORM_Record) {
                    $ret->set($key, $value->copy($deep));
                }
            }
        }

        return $ret;
    }

    public function assignIdentifier($id = false)
    {
        if ($id === false) {
            $this->_id       = array();
            $this->_data     = $this->cleanData($this->_data);
            $this->_state    = IPF_ORM_Record::STATE_TCLEAN;
            $this->_modified = array();
        } elseif ($id === true) {
            $this->prepareIdentifiers(true);
            $this->_state    = IPF_ORM_Record::STATE_CLEAN;
            $this->_modified = array();
        } else {
            if (is_array($id)) {
                foreach ($id as $fieldName => $value) {
                    $this->_id[$fieldName] = $value;
                    $this->_data[$fieldName] = $value;
                }
            } else {
                $name = $this->_table->getIdentifier();
                $this->_id[$name] = $id;
                $this->_data[$name] = $id;
            }
            $this->_state = IPF_ORM_Record::STATE_CLEAN;
            $this->_modified = array();
        }
    }

    public function identifier()
    {
        return $this->_id;
    }

    final public function getIncremented()
    {
        $id = current($this->_id);
        if ($id === false) {
            return null;
        }

        return $id;
    }

    public function pk($sep='_')
    {
        $pk = '';
        foreach($this->_id as $val) {
            if ($pk!='')
                $pk .= $sep;
            $pk .= $val;
        }
        return $pk;
    }

    public function getLast()
    {
        return $this;
    }

    public function hasReference($name)
    {
        return isset($this->_references[$name]);
    }

    public function reference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }
    }

    public function obtainReference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }
        throw new IPF_ORM_Exception("Unknown reference $name");
    }

    public function getReferences()
    {
        return $this->_references;
    }

    final public function setRelated($alias, IPF_ORM_Access $coll)
    {
        $this->_references[$alias] = $coll;
    }

    public function loadReference($name)
    {
        $rel = $this->_table->getRelation($name);
        $this->_references[$name] = $rel->fetchRelatedFor($this);
    }

    public function call($callback, $column)
    {
        $args = func_get_args();
        array_shift($args);

        if (isset($args[0])) {
            $fieldName = $args[0];
            $args[0] = $this->get($fieldName);

            $newvalue = call_user_func_array($callback, $args);

            $this->_data[$fieldName] = $newvalue;
        }
        return $this;
    }

    public function getNode()
    {
        if ( ! $this->_table->isTree()) {
            return false;
        }

        if ( ! isset($this->_node)) {
            $this->_node = IPF_ORM_Node::factory($this,
                                              $this->getTable()->getOption('treeImpl'),
                                              $this->getTable()->getOption('treeOptions')
                                              );
        }

        return $this->_node;
    }

    public function unshiftFilter(IPF_ORM_Record_Filter $filter)
    {
        return $this->_table->unshiftFilter($filter);
    }

    public function unlink($alias, $ids = array())
    {
        $ids = (array) $ids;

        $q = new IPF_ORM_Query();

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof IPF_ORM_Relation_Association) {
            $q->delete()
              ->from($rel->getAssociationTable()->getComponentName())
              ->where($rel->getLocal() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getForeign(), $ids);
            }

            $q->execute();

        } else if ($rel instanceof IPF_ORM_Relation_ForeignKey) {
            $q->update($rel->getTable()->getComponentName())
              ->set($rel->getForeign(), '?', array(null))
              ->addWhere($rel->getForeign() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getTable()->getIdentifier(), $ids);
            }

            $q->execute();
        }
        if (isset($this->_references[$alias])) {
            foreach ($this->_references[$alias] as $k => $record) {
                if (in_array(current($record->identifier()), $ids)) {
                    $this->_references[$alias]->remove($k);
                }
            }
            $this->_references[$alias]->takeSnapshot();
        }
        return $this;
    }

    public function link($alias, $ids)
    {
        $ids = (array) $ids;

        if ( ! count($ids)) {
            return $this;
        }

        $identifier = array_values($this->identifier());
        $identifier = array_shift($identifier);

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof IPF_ORM_Relation_Association) {

            $modelClassName = $rel->getAssociationTable()->getComponentName();
            $localFieldName = $rel->getLocalFieldName();
            $localFieldDef  = $rel->getAssociationTable()->getColumnDefinition($localFieldName);
            if ($localFieldDef['type'] == 'integer') {
                $identifier = (integer) $identifier;
            }
            $foreignFieldName = $rel->getForeignFieldName();
            $foreignFieldDef  = $rel->getAssociationTable()->getColumnDefinition($foreignFieldName);
            if ($foreignFieldDef['type'] == 'integer') {
                for ($i = 0; $i < count($ids); $i++) {
                    $ids[$i] = (integer) $ids[$i];
                }
            }

            foreach ($ids as $id) {
                $record = new $modelClassName;
                $record[$localFieldName]   = $identifier;
                $record[$foreignFieldName] = $id;
                $record->save();
            }

        } else if ($rel instanceof IPF_ORM_Relation_ForeignKey) {

            $q = new IPF_ORM_Query();

            $q->update($rel->getTable()->getComponentName())
              ->set($rel->getForeign(), '?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getTable()->getIdentifier(), $ids);
            }

            $q->execute();

        } else if ($rel instanceof IPF_ORM_Relation_LocalKey) {

            $q = new IPF_ORM_Query();

            $q->update($this->getTable()->getComponentName())
              ->set($rel->getLocalFieldName(), '?', $ids);

            if (count($ids) > 0) {
                $q->whereIn($rel->getTable()->getIdentifier(), array_values($this->identifier()));
            }

            $q->execute();

        }

        return $this;
    }

    public function __call($method, $args)
    {
        if (($template = $this->_table->getMethodOwner($method)) !== false) {
            $template->setInvoker($this);
            return call_user_func_array(array($template, $method), $args);
        }

        foreach ($this->_table->getTemplates() as $template) {
            if (method_exists($template, $method)) {
                $template->setInvoker($this);
                $this->_table->setMethodOwner($method, $template);

                return call_user_func_array(array($template, $method), $args);
            }
        }

        throw new IPF_ORM_Exception(sprintf('Unknown method %s::%s', get_class($this), $method));
    }

    public function deleteNode()
    {
        $this->getNode()->delete();
    }

    public function free($deep = false)
    {
        if ($this->_state != self::STATE_LOCKED) {
            $this->_state = self::STATE_LOCKED;

            $this->_table->getRepository()->evict($this->_oid);
            $this->_table->removeRecord($this);
            $this->_data = array();
            $this->_id = array();

            if ($deep) {
                foreach ($this->_references as $name => $reference) {
                    if ( ! ($reference instanceof IPF_ORM_Null)) {
                        $reference->free($deep);
                    }
                }
            }

            $this->_references = array();
        }
    }

    public function toString()
    {
        return IPF_ORM::dump(get_object_vars($this));
    }

    public function __toString()
    {
        return (string) $this->_oid;
    }

    public function ModelAdmin(){
        $cn = get_class($this);
        if (isset(IPF_Admin_Model::$models[$cn]))
            return IPF_Admin_Model::$models[$cn];
        return null;
    }

    public function SetFromFormData($cleaned_values)
    {
        $names = $this->_table->getFieldNames();
        foreach ($cleaned_values as $key=>$val) {
            $validators = $this->getTable()->getFieldValidators($key);
            if (
                array_key_exists('image',$validators) ||
                array_key_exists('file',$validators) ||
                array_key_exists('email',$validators)
            ){
                if (($val!==null) && ($val==''))
                    continue;
            }
            if (array_search($key,$names)){
                $this->$key = $val;
            }
        }
    }

    public function SetCustom($name, $val){
        $this->_custom[$name] = $val;
    }

    public function GetCustom($name){
        if (isset($this->_custom[$name]))
            return $this->_custom[$name];
        return null;
    }

    public function _reorder($ids, $ord_field, $drop_id, $prev_ids, $ord=1){
        foreach($ids as $id){
            $item = $this->getTable()->find($id);
            $item[$ord_field] = $ord;
            $item->save();
            $ord++;
        }
    }
}