<?php

class IPF_ORM_Table extends IPF_ORM_Configurable implements Countable
{
    protected $_data             = array();
    protected $_identifier = array();
    protected $_identifierType;
    protected $_conn;
    protected $_identityMap        = array();

    protected $_repository;
    protected $_columns          = array();
    protected $_fieldNames    = array();

    protected $_columnNames = array();

    protected $hasDefaultValues;

    protected $_options      = array('name'           => null,
                                     'tableName'      => null,
                                     'inheritanceMap' => array(),
                                     'enumMap'        => array(),
                                     'type'           => null,
                                     'charset'        => null,
                                     'collation'      => null,
                                     'indexes'        => array(),
                                     'parents'        => array(),
                                     'queryParts'     => array(),
                                     'versioning'     => null,
                                     'subclasses'     => array(),
                                     );

    protected $_parser;

    protected $_templates   = array();
    protected $_filters     = array();
    protected $_generators     = array();
    protected $_invokedMethods = array();

    public $listeners = array();

    public function __construct($name, IPF_ORM_Connection $conn)
    {
        if (empty($name) || !class_exists($name))
            throw new IPF_ORM_Exception("Couldn't find class " . $name);

        $this->_conn = $conn;
        $this->setParent($this->_conn);

        $this->_options['name'] = $name;
        $this->_parser = new IPF_ORM_Relation_Parser($this);

        $this->initParents($name);

        // create database table
        $name::setTableDefinition($this);

        if (!isset($this->_options['tableName'])) {
            $this->setTableName(IPF_ORM_Inflector::tableize($class->getName()));
        }

        $this->initIdentifier();

        $name::setUp($this);

        $this->_filters[]  = new IPF_ORM_Record_Filter_Standard();
        $this->_repository = new IPF_ORM_Table_Repository($this);
    }

    private function initParents($name)
    {
        $names = array();

        $class = $name;
        do {
            if ($class === 'IPF_ORM_Record')
                break;

            $name = $class;
            $names[] = $name;
        } while ($class = get_parent_class($class));

        if ($class === false)
            throw new IPF_ORM_Exception('Class "' . $name . '" must be a child class of IPF_ORM_Record');

        // reverse names
        $names = array_reverse($names);
        // save parents
        array_pop($names);
        $this->_options['parents'] = $names;
    }

    public function initIdentifier()
    {
        switch (count($this->_identifier)) {
            case 0:
                $definition = array('type' => 'integer',
                                    'length' => 20,
                                    'autoincrement' => true,
                                    'primary' => true);
                $this->setColumn('id', $definition['type'], $definition['length'], $definition, true);
                $this->_identifier = 'id';
                $this->_identifierType = IPF_ORM::IDENTIFIER_AUTOINC;
                break;
            case 1:
                foreach ($this->_identifier as $pk) {
                    $e = $this->getDefinitionOf($pk);

                    $found = false;

                    foreach ($e as $option => $value) {
                        if ($found) {
                            break;
                        }

                        $e2 = explode(':', $option);

                        switch (strtolower($e2[0])) {
                            case 'autoincrement':
                            case 'autoinc':
                                if ($value !== false) {
                                    $this->_identifierType = IPF_ORM::IDENTIFIER_AUTOINC;
                                    $found = true;
                                }
                                break;
                        }
                    }
                    if ( ! isset($this->_identifierType)) {
                        $this->_identifierType = IPF_ORM::IDENTIFIER_NATURAL;
                    }
                }

                $this->_identifier = $pk;

                break;
            default:
                $this->_identifierType = IPF_ORM::IDENTIFIER_COMPOSITE;
        }
    }

    public function isIdentifier($fieldName)
    {
        return ($fieldName === $this->getIdentifier() ||
                in_array($fieldName, (array) $this->getIdentifier()));
    }

    public function isIdentifierAutoincrement()
    {
        return $this->getIdentifierType() === IPF_ORM::IDENTIFIER_AUTOINC;
    }

    public function isIdentifierComposite()
    {
        return $this->getIdentifierType() === IPF_ORM::IDENTIFIER_COMPOSITE;
    }

    public function getMethodOwner($method)
    {
        return (isset($this->_invokedMethods[$method])) ?
                      $this->_invokedMethods[$method] : false;
    }

    public function setMethodOwner($method, $class)
    {
        $this->_invokedMethods[$method] = $class;
    }

    public function getTemplates()
    {
        return $this->_templates;
    }

    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = array();
        $primary = array();

        foreach ($this->getColumns() as $name => $definition) {

            if (isset($definition['owner'])) {
                continue;
            }

            switch ($definition['type']) {
                case 'enum':
                    if (isset($definition['default'])) {
                        $definition['default'] = $this->enumIndex($name, $definition['default']);
                    }
                    break;
                case 'boolean':
                    if (isset($definition['default'])) {
                        $definition['default'] = $this->getConnection()->convertBooleans($definition['default']);
                    }
                    break;
            }
            $columns[$name] = $definition;

            if (isset($definition['primary']) && $definition['primary']) {
                $primary[] = $name;
            }
        }

        $options['foreignKeys'] = isset($this->_options['foreignKeys']) ?
                $this->_options['foreignKeys'] : array();

        if ($parseForeignKeys && $this->getAttribute(IPF_ORM::ATTR_EXPORT)
                & IPF_ORM::EXPORT_CONSTRAINTS) {

            $constraints = array();

            $emptyIntegrity = array('onUpdate' => null,
                                    'onDelete' => null);

            foreach ($this->getRelations() as $name => $relation) {
                $fk = $relation->toArray();
                $fk['foreignTable'] = $relation->getTable()->getTableName();

                if ($relation->getTable() === $this && in_array($relation->getLocal(), $primary)) {
                    if ($relation->hasConstraint()) {
                        throw new IPF_ORM_Exception("Badly constructed integrity constraints.");
                    }
                    continue;
                }

                $integrity = array('onUpdate' => $fk['onUpdate'],
                                   'onDelete' => $fk['onDelete']);

                if ($relation instanceof IPF_ORM_Relation_LocalKey) {
                    $def = array('local'        => $relation->getLocal(),
                                 'foreign'      => $relation->getForeign(),
                                 'foreignTable' => $relation->getTable()->getTableName());

                    if (($key = array_search($def, $options['foreignKeys'])) === false) {
                        $options['foreignKeys'][] = $def;
                        $constraints[] = $integrity;
                    } else {
                        if ($integrity !== $emptyIntegrity) {
                            $constraints[$key] = $integrity;
                        }
                    }
                }
            }

            foreach ($constraints as $k => $def) {
                $options['foreignKeys'][$k] = array_merge($options['foreignKeys'][$k], $def);
            }
        }

        $options['primary'] = $primary;
        
        return array('tableName' => $this->getOption('tableName'),
                     'columns'   => $columns,
                     'options'   => array_merge($this->getOptions(), $options));
    }

    public function getRelationParser()
    {
        return $this->_parser;
    }

    public function __get($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        return null;
    }

    public function __isset($option)
    {
        return isset($this->_options[$option]);
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    public function addForeignKey(array $definition)
    {
        $this->_options['foreignKeys'][] = $definition;
    }

    public function addCheckConstraint($definition, $name)
    {
        if (is_string($name)) {
            $this->_options['checks'][$name] = $definition;
        } else {
            $this->_options['checks'][] = $definition;
        }

        return $this;
    }

    public function addIndex($index, array $definition)
    {
        $this->_options['indexes'][$index] = $definition;
    }

    public function getIndex($index)
    {
        if (isset($this->_options['indexes'][$index])) {
            return $this->_options['indexes'][$index];
        }

        return false;
    }

    public function bind($class, $alias, $type, array $options)
    {
        $this->_parser->bind($class, $alias, $type, $options);
    }

    public function ownsOne($class, $alias, $options=array())
    {
        $this->bind($class, $alias, IPF_ORM_Relation::ONE_COMPOSITE, $options);
    }

    public function ownsMany($class, $alias, $options=array())
    {
        $this->bind($class, $alias, IPF_ORM_Relation::MANY_COMPOSITE, $options);
    }

    public function hasOne($class, $alias, $options=array())
    {
        $this->bind($class, $alias, IPF_ORM_Relation::ONE_AGGREGATE, $options);
    }

    public function hasMany($class, $alias, $options=array())
    {
        $this->bind($class, $alias, IPF_ORM_Relation::MANY_AGGREGATE, $options);
    }

    public function hasRelation($alias)
    {
        return $this->_parser->hasRelation($alias);
    }

    public function getRelation($alias, $recursive = true)
    {
        return $this->_parser->getRelation($alias, $recursive);
    }

    public function getRelations()
    {
        return $this->_parser->getRelations();
    }

    public function createQuery($alias = '')
    {
        if ( ! empty($alias)) {
            $alias = ' ' . trim($alias);
        }
        return IPF_ORM_Query::create($this->_conn)->from($this->getComponentName() . $alias);
    }

    public function getRepository()
    {
        return $this->_repository;
    }

    public function setOption($name, $value)
    {
        switch ($name) {
            case 'name':
            case 'tableName':
                break;
            case 'enumMap':
            case 'inheritanceMap':
            case 'index':
                if (!is_array($value))
                    throw new IPF_ORM_Exception($name . ' should be an array.');
                break;
        }
        $this->_options[$name] = $value;
    }

    public function getOption($name)
    {
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
        }
        return null;
    }

    public function getColumnName($fieldName)
    {
        // FIX ME: This is being used in places where an array is passed, but it should not be an array
        // For example in places where IPF_ORM should support composite foreign/primary keys
        $fieldName = is_array($fieldName) ? $fieldName[0]:$fieldName;

        if (isset($this->_columnNames[$fieldName])) {
            return $this->_columnNames[$fieldName];
        }

        return strtolower($fieldName);
    }

    public function getColumnDefinition($columnName)
    {
        if ( ! isset($this->_columns[$columnName])) {
            return false;
        }
        return $this->_columns[$columnName];
    }

    public function getFieldName($columnName)
    {
        if (isset($this->_fieldNames[$columnName])) {
            return $this->_fieldNames[$columnName];
        }
        return $columnName;
    }
    public function setColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $this->setColumn($name, $options['type'], $options['length'], $options);
        }
    }

    public function setColumn($name, $type, $length = null, $options = array(), $prepend = false)
    {
        if (is_string($options)) {
            $options = explode('|', $options);
        }

        foreach ($options as $k => $option) {
            if (is_numeric($k)) {
                if (!empty($option)) {
                    $options[$option] = true;
                }
                unset($options[$k]);
            }
        }

        // extract column name & field name
        if (stripos($name, ' as '))
        {
            if (strpos($name, ' as')) {
                $parts = explode(' as ', $name);
            } else {
                $parts = explode(' AS ', $name);
            }

            if (count($parts) > 1) {
                $fieldName = $parts[1];
            } else {
                $fieldName = $parts[0];
            }

            $name = strtolower($parts[0]);
        } else {
            $fieldName = $name;
            $name = strtolower($name);
        }

        $name = trim($name);
        $fieldName = trim($fieldName);

        if ($prepend) {
            $this->_columnNames = array_merge(array($fieldName => $name), $this->_columnNames);
            $this->_fieldNames = array_merge(array($name => $fieldName), $this->_fieldNames);
        } else {
            $this->_columnNames[$fieldName] = $name;
            $this->_fieldNames[$name] = $fieldName;
        }

        if ($length == null) {
            switch ($type) {
                case 'string':
                case 'clob':
                case 'float':
                case 'double':
                case 'integer':
                case 'array':
                case 'object':
                case 'blob':
                case 'gzip':
                    // use php int max
                    $length = 2147483647;
                break;
                case 'boolean':
                    $length = 1;
                case 'date':
                    // YYYY-MM-DD ISO 8601
                    $length = 10;
                case 'time':
                    // HH:NN:SS+00:00 ISO 8601
                    $length = 14;
                case 'timestamp':
                    // YYYY-MM-DDTHH:MM:SS+00:00 ISO 8601
                    $length = 25;
                break;
            }
        }

        $options['type'] = $type;
        $options['length'] = $length;

        if ($prepend) {
            $this->_columns = array_merge(array($name => $options), $this->_columns);
        } else {
            $this->_columns[$name] = $options;
        }

        if (isset($options['primary']) && $options['primary']) {
            if (isset($this->_identifier)) {
                $this->_identifier = (array) $this->_identifier;
            }
            if ( ! in_array($fieldName, $this->_identifier)) {
                $this->_identifier[] = $fieldName;
            }
        }
        if (isset($options['default'])) {
            $this->hasDefaultValues = true;
        }
    }

    public function hasDefaultValues()
    {
        return $this->hasDefaultValues;
    }

    public function getDefaultValueOf($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        if ( ! isset($this->_columns[$columnName])) {
            throw new IPF_ORM_Exception("Couldn't get default value. Column ".$columnName." doesn't exist.");
        }
        if (isset($this->_columns[$columnName]['default'])) {
            return $this->_columns[$columnName]['default'];
        } else {
            return null;
        }
    }

    public function getIdentifier()
    {
        return $this->_identifier;
    }

    public function getIdentifierType()
    {
        return $this->_identifierType;
    }

    public function hasColumn($columnName)
    {
        return isset($this->_columns[strtolower($columnName)]);
    }

    public function hasField($fieldName)
    {
        return isset($this->_columnNames[$fieldName]);
    }

    public function setSubClasses(array $map)
    {
        $class = $this->getComponentName();
        if (isset($map[$class])) {
            $this->setOption('inheritanceMap', $map[$class]);
        } else {
            $this->setOption('subclasses', array_keys($map));
        }
    }

    public function setConnection(IPF_ORM_Connection $conn)
    {
        $this->_conn = $conn;

        $this->setParent($this->_conn);

        return $this;
    }

    public function getConnection()
    {
        return $this->_conn;
    }

    public function create(array $array = array())
    {
        $record = new $this->_options['name']($this, true);
        $record->fromArray($array);

        return $record;
    }

    public function find($id, $hydrationMode = null)
    {
        if (is_null($id)) {
            return false;
        }

        $id = is_array($id) ? array_values($id) : array($id);
        
        $q = $this->createQuery();
        $q->where(implode(' = ? AND ', (array) $this->getIdentifier()) . ' = ?', $id)
                ->limit(1);
        $res = $q->fetchOne(array(), $hydrationMode);
        $q->free();
        
        return $res;
    }

    public function findAll($hydrationMode = null)
    {
        return $this->createQuery()->execute(array(), $hydrationMode);
    }

    public function findBySql($dql, $params = array(), $hydrationMode = null)
    {
        return $this->createQuery()->where($dql)->execute($params, $hydrationMode);
    }

    public function findByDql($dql, $params = array(), $hydrationMode = null)
    {
        $parser = new IPF_ORM_Query($this->_conn);
        $component = $this->getComponentName();
        $query = 'FROM ' . $component . ' WHERE ' . $dql;

        return $parser->query($query, $params, $hydrationMode);
    }

    public function findOneByDql($dql, $params = array(), $hydrationMode = null)
    {
    	$results = $this->findByDql($dql, $params, $hydrationMode);
        if (is_array($results) && isset($results[0])) {
            return $results[0];
        } else if ($results instanceof IPF_ORM_Collection && $results->count() > 0) {
            return $results->getFirst();
        } else {
            return false;
        }
    }
    
    
    public function execute($queryKey, $params = array(), $hydrationMode = IPF_ORM::HYDRATE_RECORD)
    {
        return IPF_ORM_Manager::getInstance()
                            ->getQueryRegistry()
                            ->get($queryKey, $this->getComponentName())
                            ->execute($params, $hydrationMode);
    }

    public function executeOne($queryKey, $params = array(), $hydrationMode = IPF_ORM::HYDRATE_RECORD)
    {
        return IPF_ORM_Manager::getInstance()
                            ->getQueryRegistry()
                            ->get($queryKey, $this->getComponentName())
                            ->fetchOne($params, $hydrationMode);
    }

    public function clear()
    {
        $this->_identityMap = array();
    }

    public function addRecord(IPF_ORM_Record $record)
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->_identityMap[$id])) {
            return false;
        }

        $this->_identityMap[$id] = $record;

        return true;
    }

    public function removeRecord(IPF_ORM_Record $record)
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->_identityMap[$id])) {
            unset($this->_identityMap[$id]);
            return true;
        }

        return false;
    }

    public function getRecord()
    {
        if ( ! empty($this->_data)) {
            $identifierFieldNames = $this->getIdentifier();

            if ( ! is_array($identifierFieldNames)) {
                $identifierFieldNames = array($identifierFieldNames);
            }

            $found = false;
            foreach ($identifierFieldNames as $fieldName) {
                if ( ! isset($this->_data[$fieldName])) {
                    // primary key column not found return new record
                    $found = true;
                    break;
                }
                $id[] = $this->_data[$fieldName];
            }

            if ($found) {
                $recordName = $this->getComponentName();
                $record = new $recordName($this, true);
                $this->_data = array();
                return $record;
            }


            $id = implode(' ', $id);

            if (isset($this->_identityMap[$id])) {
                $record = $this->_identityMap[$id];
                $record->hydrate($this->_data);
            } else {
                $recordName = $this->getComponentName();
                $record = new $recordName($this);
                $this->_identityMap[$id] = $record;
            }
            $this->_data = array();
        } else {
            $recordName = $this->getComponentName();
            $record = new $recordName($this, true);
        }

        return $record;
    }

    final public function getProxy($id = null)
    {
        if ($id !== null) {
            $identifierColumnNames = $this->getIdentifierColumnNames();
            $query = 'SELECT ' . implode(', ', (array) $identifierColumnNames)
                . ' FROM ' . $this->getTableName()
                . ' WHERE ' . implode(' = ? && ', (array) $identifierColumnNames) . ' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge(array($id), array_values($this->_options['inheritanceMap']));

            $this->_data = $this->_conn->execute($query, $params)->fetch(PDO::FETCH_ASSOC);

            if ($this->_data === false)
                return false;
        }
        return $this->getRecord();
    }

    final public function applyInheritance($where)
    {
        if ( ! empty($this->_options['inheritanceMap'])) {
            $a = array();
            foreach ($this->_options['inheritanceMap'] as $field => $value) {
                $a[] = $this->getColumnName($field) . ' = ?';
            }
            $i = implode(' AND ', $a);
            $where .= ' AND ' . $i;
        }
        return $where;
    }

    public function count()
    {
        $a = $this->_conn->execute('SELECT COUNT(1) FROM ' . $this->_options['tableName'])->fetch(IPF_ORM::FETCH_NUM);
        return current($a);
    }

    public function getQueryObject()
    {
        $graph = new IPF_ORM_Query($this->getConnection());
        $graph->load($this->getComponentName());
        return $graph;
    }

    public function getEnumValues($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        if (isset($this->_columns[$columnName]['values'])) {
            return $this->_columns[$columnName]['values'];
        } else {
            return array();
        }
    }

    public function enumValue($fieldName, $index)
    {
        if (IPF_ORM_Null::isNull($index)) {
            return $index;
        }

        $columnName = $this->getColumnName($fieldName);
        if ( ! $this->_conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)
            && isset($this->_columns[$columnName]['values'][$index])
        ) {
            return $this->_columns[$columnName]['values'][$index];
        }

        return $index;
    }

    public function enumIndex($fieldName, $value)
    {
        $values = $this->getEnumValues($fieldName);

        $index = array_search($value, $values);
        if ($index === false || !$this->_conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
            return $index;
        }
        return $value;
    }

    public function getColumnCount()
    {
        return count($this->_columns);
    }

    public function getColumns()
    {
        return $this->_columns;
    }

    public function removeColumn($fieldName)
    {
        if ($this->hasField($fieldName)) {
            $columnName = $this->getColumnName($fieldName);
            unset($this->_columnNames[$fieldName], $this->_fieldNames[$columnName], $this->_columns[$columnName]);
            return true;
        } else {
            return false;
        }
    }

    public function getColumnNames(array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->_columns);
        } else {
           $columnNames = array();
           foreach ($fieldNames as $fieldName) {
               $columnNames[] = $this->getColumnName($fieldName);
           }
           return $columnNames;
        }
    }

    public function getIdentifierColumnNames()
    {
        return $this->getColumnNames((array) $this->getIdentifier());
    }

    public function getFieldNames()
    {
        return array_values($this->_fieldNames);
    }

    public function getDefinitionOf($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        return $this->getColumnDefinition($columnName);
    }

    public function getTypeOf($fieldName)
    {
        return $this->getTypeOfColumn($this->getColumnName($fieldName));
    }

    public function getTypeOfColumn($columnName)
    {
        return isset($this->_columns[$columnName]) ? $this->_columns[$columnName]['type'] : false;
    }

    public function setData(array $data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function prepareValue($fieldName, $value, $typeHint = null)
    {
        if (IPF_ORM_Null::isNull($value)) {
            return $value;
        } else if ($value === null) {
            return null;
        } else {
            $type = is_null($typeHint) ? $this->getTypeOf($fieldName) : $typeHint;

            switch ($type) {
                case 'integer':
                case 'string';
                    // don't do any casting here PHP INT_MAX is smaller than what the databases support
                break;
                case 'enum':
                    return $this->enumValue($fieldName, $value);
                break;
                case 'boolean':
                    return (boolean) $value;
                break;
                case 'array':
                case 'object':
                    if (is_string($value)) {
                        $value = empty($value) ? null:unserialize($value);

                        if ($value === false) {
                            throw new IPF_ORM_Exception('Unserialization of ' . $fieldName . ' failed.');
                        }
                        return $value;
                    }
                break;
                case 'gzip':
                    $value = gzuncompress($value);

                    if ($value === false) {
                        throw new IPF_ORM_Exception('Uncompressing of ' . $fieldName . ' failed.');
                    }
                    return $value;
                break;
            }
        }
        return $value;
    }

    public function getComponentName()
    {
        return $this->_options['name'];
    }

    public function getTableName()
    {
        return $this->_options['tableName'];
    }

    public function setTableName($tableName)
    {
        $this->setOption('tableName', $tableName);
    }

    public function getTemplate($template)
    {
        if (!isset($this->_templates[$template]))
            throw new IPF_ORM_Exception('Template ' . $template . ' not loaded');
        return $this->_templates[$template];
    }

    public function hasTemplate($template)
    {
        return isset($this->_templates[$template]);
    }

    public function addTemplate($tpl, array $options=array())
    {
        if (!is_object($tpl)) {
            $className = 'IPF_ORM_Template_' . $tpl;

            if (class_exists($className, true)) {
                $tpl = new $className($options);
            } else if (class_exists($tpl, true)) {
                $tpl = new $tpl($options);
            } else {
                throw new IPF_ORM_Record_Exception('Could not load behavior named: "' . $tpl . '"');
            }
        }

        if (!($tpl instanceof IPF_ORM_Template)) {
            throw new IPF_ORM_Record_Exception('Loaded behavior class is not an istance of IPF_ORM_Template.');
        }

        $className = get_class($tpl);
        $this->_templates[$className] = $tpl;

        $tpl->setTableDefinition($this);
    }

    public function getGenerators()
    {
        return $this->_generators;
    }

    public function getGenerator($generator)
    {
        if ( ! isset($this->_generators[$generator])) {
            throw new IPF_ORM_Exception('Generator ' . $generator . ' not loaded');
        }

        return $this->_generators[$generator];
    }

    public function hasGenerator($generator)
    {
        return isset($this->_generators[$generator]);
    }

    public function addGenerator(IPF_ORM_Record_Generator $generator, $name = null)
    {
        if ($name === null) {
            $this->_generators[] = $generator;
        } else {
            $this->_generators[$name] = $generator;
        }
        return $this;
    }

    public function bindQueryParts(array $queryParts)
    {
        $this->_options['queryParts'] = $queryParts;

        return $this;
    }

    public function bindQueryPart($queryPart, $value)
    {
        $this->_options['queryParts'][$queryPart] = $value;

        return $this;
    }

    public function getFieldValidators($fieldName)
    {
        $validators = array();
        $columnName = $this->getColumnName($fieldName);
        // this loop is a dirty workaround to get the validators filtered out of
        // the options, since everything is squeezed together currently
        
        if (!isset($this->_columns[$columnName]))
            return array();
        
        foreach ($this->_columns[$columnName] as $name => $args) {
             if (empty($name)
                    || $name == 'primary'
                    || $name == 'protected'
                    || $name == 'autoincrement'
                    || $name == 'default'
                    || $name == 'values'
                    || $name == 'zerofill'
                    || $name == 'owner'
                    || $name == 'scale'
                    || $name == 'type'
                    || $name == 'length'
                    || $name == 'fixed') {
                continue;
            }
            if ($name == 'notnull' && isset($this->_columns[$columnName]['autoincrement'])) {
                continue;
            }
            // skip it if it's explicitly set to FALSE (i.e. notnull => false)
            if ($args === false) {
                continue;
            }
            $validators[$name] = $args;
        }

        return $validators;
    }

    public function getBoundQueryPart($queryPart)
    {
        if ( ! isset($this->_options['queryParts'][$queryPart])) {
            return null;
        }

        return $this->_options['queryParts'][$queryPart];
    }

    public function unshiftFilter(IPF_ORM_Record_Filter $filter)
    {
        $filter->setTable($this);

        $filter->init();

        array_unshift($this->_filters, $filter);

        return $this;
    }

    public function getFilters()
    {
        return $this->_filters;
    }

    public function __toString()
    {
        return IPF_ORM_Utils::getTableAsString($this);
    }

    protected function findBy($fieldName, $value, $hydrationMode = null)
    {
        return $this->createQuery()->where($fieldName . ' = ?', array($value))->execute(array(), $hydrationMode);
    }

    protected function findOneBy($fieldName, $value, $hydrationMode = null)
    {
        $results = $this->createQuery()
                        ->where($fieldName . ' = ?',array($value))
                        ->limit(1)
                        ->execute(array(), $hydrationMode);

        if (is_array($results) && isset($results[0])) {
            return $results[0];
        } else if ($results instanceof IPF_ORM_Collection && $results->count() > 0) {
            return $results->getFirst();
        } else {
            return false;
        }
    }

    protected function _resolveFindByFieldName($name)
    {
        $fieldName = IPF_ORM_Inflector::tableize($name);
        if ($this->hasColumn($name) || $this->hasField($name)) {
            return $this->getFieldName($this->getColumnName($name));
        } else if ($this->hasColumn($fieldName) || $this->hasField($fieldName)) {
            return $this->getFieldName($this->getColumnName($fieldName));
        } else {
            return false;
        }
    }

    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'findBy') {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } else if (substr($method, 0, 9) == 'findOneBy') {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        }

        if (isset($by)) {
            if ( ! isset($arguments[0])) {
                throw new IPF_ORM_Exception('You must specify the value to findBy');
            }

            $fieldName = $this->_resolveFindByFieldName($by);
            $hydrationMode = isset($arguments[1]) ? $arguments[1]:null;
            if ($this->hasField($fieldName)) {
                return $this->$method($fieldName, $arguments[0], $hydrationMode);
            } else if ($this->hasRelation($by)) {
                $relation = $this->getRelation($by);

                if ($relation['type'] === IPF_ORM_Relation::MANY) {
                    throw new IPF_ORM_Exception('Cannot findBy many relationship.');
                }

                return $this->$method($relation['local'], $arguments[0], $hydrationMode);
            } else {
                throw new IPF_ORM_Exception('Cannot find by: ' . $by . '. Invalid column or relationship alias.');
            }
        }

        throw new IPF_ORM_Exception(sprintf('Unknown method %s::%s', get_class($this), $method));
    }

    public function notifyRecordListeners($method, $event)
    {
        foreach ($this->listeners as $listener)
            if (is_callable(array($listener, $method)))
                $listener->$method($event);
    }
}

