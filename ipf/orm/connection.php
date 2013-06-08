<?php

abstract class IPF_ORM_Connection extends IPF_ORM_Configurable implements Countable, IteratorAggregate
{
    protected $dbh;
    protected $tables           = array();
    protected $_name;
    protected $driverName;
    protected $isConnected      = false;
    protected $supported        = array();
    protected $pendingAttributes  = array();

    private $modules = array('transaction' => false,
                             'expression'  => false,
                             'dataDict'    => false,
                             'export'      => false,
                             'import'      => false,
                             'sequence'    => false,
                             'unitOfWork'  => false,
                             'formatter'   => false,
                             'util'        => false,
                             );

    protected $properties = array('sql_comments'        => array(array('start' => '--', 'end' => "\n", 'escape' => false),
                                                                 array('start' => '/*', 'end' => '*/', 'escape' => false)),
                                  'identifier_quoting'  => array('start' => '"', 'end' => '"','escape' => '"'),
                                  'string_quoting'      => array('start' => "'",
                                                                 'end' => "'",
                                                                 'escape' => false,
                                                                 'escape_pattern' => false),
                                  'wildcards'           => array('%', '_'),
                                  'varchar_max_length'  => 255,
                                  );

    protected $serverInfo = array();
    protected $options    = array();
    private static $availableDrivers    = array(
                                        'Mysql',
                                        'Pgsql',
                                        'Oracle',
                                        'Informix',
                                        'Mssql',
                                        'Sqlite',
                                        'Firebird'
                                        );
    protected $_count = 0;

    public $dbListeners = array();

    public function __construct(IPF_ORM_Manager $manager, $adapter, $user = null, $pass = null)
    {
        if (is_object($adapter)) {
            if (!($adapter instanceof PDO)) {
                throw new IPF_ORM_Exception('First argument should be an instance of PDO');
            }
            $this->dbh = $adapter;
            $this->isConnected = true;
        } else if (is_array($adapter)) {
            $this->options['dsn']      = $adapter['dsn'];
            $this->options['username'] = $adapter['user'];
            $this->options['password'] = $adapter['pass'];
            
            $this->options['other'] = array();  
            if (isset($adapter['other'])) {
                $this->options['other'] = array(IPF_ORM::ATTR_PERSISTENT => $adapter['persistent']);
            }
        }

        $this->setParent($manager);
        $this->dbListeners = $manager->dbListeners;

        $this->setAttribute(IPF_ORM::ATTR_CASE, IPF_ORM::CASE_NATURAL);
        $this->setAttribute(IPF_ORM::ATTR_ERRMODE, IPF_ORM::ERRMODE_EXCEPTION);

        $this->notifyDBListeners('onOpen', $this);
    }

    public function getOptions()
    {
      return $this->options;
    }

    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
    }

    public function setOption($option, $value)
    {
      return $this->options[$option] = $value;
    }

    public function getAttribute($attribute)
    {
        if (is_string($attribute)) {
            $stringAttribute = $attribute;
            $attribute = $this->getAttributeFromString($attribute);
        }

        if ($attribute >= 100) {
            if ( ! isset($this->attributes[$attribute])) {
                return parent::getAttribute($attribute);
            }
            return $this->attributes[$attribute];
        }

        if ($this->isConnected) {
            try {
                return $this->dbh->getAttribute($attribute);
            } catch (Exception $e) {
                throw new IPF_ORM_Exception('Attribute ' . $attribute . ' not found.');
            }
        } else {
            if ( ! isset($this->pendingAttributes[$attribute])) {
                $this->connect();
                $this->getAttribute($attribute);
            }

            return $this->pendingAttributes[$attribute];
        }
    }

    public static function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }

    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute)) {
            $attributeString = $attribute;
            $attribute = parent::getAttributeFromString($attribute);
        }

        if (is_string($value) && isset($attributeString)) {
            $value = parent::getAttributeValueFromString($attributeString, $value);
        }

        if ($attribute >= 100) {
            parent::setAttribute($attribute, $value);
        } else {
            if ($this->isConnected) {
                $this->dbh->setAttribute($attribute, $value);
            } else {
                $this->pendingAttributes[$attribute] = $value;
            }
        }

        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getDriverName()
    {
        return $this->driverName;
    }

    public function __get($name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        if ( ! isset($this->modules[$name])) {
            throw new IPF_ORM_Exception('Unknown module / property ' . $name);
        }
        if ($this->modules[$name] === false) {
            switch ($name) {
                case 'unitOfWork':
                    $this->modules[$name] = new IPF_ORM_Connection_UnitOfWork($this);
                    break;
                case 'formatter':
                    $this->modules[$name] = new IPF_ORM_Formatter($this);
                    break;
                default:
                    $class = 'IPF_ORM_' . ucwords($name) . '_' . $this->getDriverName();
                    $this->modules[$name] = new $class($this);
                }
        }

        return $this->modules[$name];
    }

    public function getManager()
    {
        return $this->getParent();
    }

    public function getDbh()
    {
        $this->connect();
        
        return $this->dbh;
    }

    public function connect()
    {
        if ($this->isConnected) {
            return false;
        }

        $event = new IPF_ORM_Event($this, IPF_ORM_Event::CONN_CONNECT);

        $this->notifyDBListeners('preConnect', $event);

        $e     = explode(':', $this->options['dsn']);
        $found = false;

        if (extension_loaded('pdo')) {
            if (in_array($e[0], PDO::getAvailableDrivers())) {
                try {
                    $this->dbh = new PDO($this->options['dsn'], $this->options['username'], 
                                     (!$this->options['password'] ? '':$this->options['password']), $this->options['other']);

                    $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    throw new IPF_ORM_Exception('PDO Connection Error: ' . $e->getMessage());
                }
                $found = true;
            }
        }

        if ( !$found) {
            throw new IPF_Exception_Panic("Couldn't locate driver named " . $e[0]);          
        }

        // attach the pending attributes to adapter
        foreach($this->pendingAttributes as $attr => $value) {
            $this->dbh->setAttribute($attr, $value);
        }

        $this->isConnected = true;

        $this->notifyDBListeners('postConnect', $event);
        return true;
    }
    
    public function incrementQueryCount() 
    {
        $this->_count++;
    }

    public function supports($feature)
    {
        return (isset($this->supported[$feature])
                  && ($this->supported[$feature] === 'emulated'
                   || $this->supported[$feature]));
    }

    public function replace(IPF_ORM_Table $table, array $fields, array $keys)
    {
        if (empty($keys)) {
            throw new IPF_ORM_Exception('Not specified which fields are keys');
        }
        $identifier = (array) $table->getIdentifier();
        $condition = array();

        foreach ($fields as $fieldName => $value) {
            if (in_array($fieldName, $keys)) {
                if ($value !== null) {
                    $condition[] = $table->getColumnName($fieldName) . ' = ?';
                    $conditionValues[] = $value;
                }
            }
        }

        $affectedRows = 0;
        if ( ! empty($condition) && ! empty($conditionValues)) {
            $query = 'DELETE FROM ' . $this->quoteIdentifier($table->getTableName())
                    . ' WHERE ' . implode(' AND ', $condition);

            $affectedRows = $this->exec($query, $conditionValues);
        }

        $this->insert($table, $fields);

        $affectedRows++;

        return $affectedRows;
    }

    public function delete(IPF_ORM_Table $table, array $identifier)
    {
        $tmp = array();

        foreach (array_keys($identifier) as $id) {
            $tmp[] = $this->quoteIdentifier($table->getColumnName($id)) . ' = ?';
        }

        $query = 'DELETE FROM '
               . $this->quoteIdentifier($table->getTableName())
               . ' WHERE ' . implode(' AND ', $tmp);
        
        return $this->exec($query, array_values($identifier));
    }

    public function update(IPF_ORM_Table $table, array $fields, array $identifier)
    {
        if (empty($fields)) {
            return false;
        }

        $set = array();
        foreach ($fields as $fieldName => $value) {
            if ($value instanceof IPF_ORM_Expression) {
                $set[] = $this->quoteIdentifier($table->getColumnName($fieldName)) . ' = ' . $value->getSql();
                unset($fields[$fieldName]);
            } else {
                $set[] = $this->quoteIdentifier($table->getColumnName($fieldName)) . ' = ?';
            }
        }

        $params = array_merge(array_values($fields), array_values($identifier));

        $sql  = 'UPDATE ' . $this->quoteIdentifier($table->getTableName())
              . ' SET ' . implode(', ', $set)
              . ' WHERE ' . implode(' = ? AND ', $table->getIdentifierColumnNames())
              . ' = ?';
          
        return $this->exec($sql, $params);
    }

    public function insert(IPF_ORM_Table $table, array $fields)
    {
        $tableName = $table->getTableName();

        // column names are specified as array keys
        $cols = array();
        // the query VALUES will contain either expresions (eg 'NOW()') or ?
        $a = array();
        foreach ($fields as $fieldName => $value) {
            $cols[] = $this->quoteIdentifier($table->getColumnName($fieldName));
            if ($value instanceof IPF_ORM_Expression) {
                $a[] = $value->getSql();
                unset($fields[$fieldName]);
            } else {
                $a[] = '?';
            }
        }

        // build the statement
        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName)
                . ' (' . implode(', ', $cols) . ')'
                . ' VALUES (' . implode(', ', $a) . ')';

        return $this->exec($query, array_values($fields));
    }

    public function setCharset($charset)
    {
    }

    public function quoteIdentifier($str, $checkOption = true)
    {
        // quick fix for the identifiers that contain a dot
        if (strpos($str, '.')) {
            $e = explode('.', $str);
            
            return $this->formatter->quoteIdentifier($e[0], $checkOption) . '.' 
                 . $this->formatter->quoteIdentifier($e[1], $checkOption);
        }
        return $this->formatter->quoteIdentifier($str, $checkOption);
    }
    
    public function quoteMultipleIdentifier($arr, $checkOption = true)
    {
        foreach ($arr as $k => $v) {
            $arr[$k] = $this->quoteIdentifier($v, $checkOption);
        }
        return $arr;
    }

    public function convertBooleans($item)
    {
        return $this->formatter->convertBooleans($item);
    }

    public function quote($input, $type = null)
    {
        return $this->formatter->quote($input, $type);
    }

    public function setDateFormat($format = null)
    {
    }

    public function fetchAll($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetchAll(IPF_ORM::FETCH_ASSOC);
    }

    public function fetchOne($statement, array $params = array(), $colnum = 0) 
    {
        return $this->execute($statement, $params)->fetchColumn($colnum);
    }

    public function fetchRow($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetch(IPF_ORM::FETCH_ASSOC);
    }

    public function fetchArray($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetch(IPF_ORM::FETCH_NUM);
    }

    public function fetchColumn($statement, array $params = array(), $colnum = 0) 
    {
        return $this->execute($statement, $params)->fetchAll(IPF_ORM::FETCH_COLUMN, $colnum);
    }

    public function fetchAssoc($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetchAll(IPF_ORM::FETCH_ASSOC);
    }

    public function fetchBoth($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetchAll(IPF_ORM::FETCH_BOTH);
    }

    public function query($query, array $params = array(), $hydrationMode = null)
    {
        $parser = new IPF_ORM_Query($this);
        $res = $parser->query($query, $params, $hydrationMode);
        $parser->free();

        return $res;
    }

    public function prepare($statement)
    {
        $this->connect();

        try {
            $event = new IPF_ORM_Event($this, IPF_ORM_Event::CONN_PREPARE, $statement);

            $this->notifyDBListeners('prePrepare', $event);

            $stmt = false;

            if ( ! $event->skipOperation) {
                $stmt = $this->dbh->prepare($statement);
            }

            $this->notifyDBListeners('postPrepare', $event);

            return new IPF_ORM_Connection_Statement($this, $stmt);
        } catch (IPF_ORM_Exception_Adapter $e) {
        } catch (PDOException $e) {
        }

        $this->rethrowException($e, $this);
    }

    public function queryOne($query, array $params = array()) 
    {
        $parser = new IPF_ORM_Query($this);

        $coll = $parser->query($query, $params);
        if ( ! $coll->contains(0)) {
            return false;
        }
        return $coll[0];
    }

    public function select($query, $limit = 0, $offset = 0)
    {
        if ($limit > 0 || $offset > 0) {
            $query = $this->modifyLimitQuery($query, $limit, $offset);
        }
        return $this->execute($query);
    }

    public function standaloneQuery($query, $params = array())
    {
        return $this->execute($query, $params);
    }

    public function execute($query, array $params = array())
    {
        $this->connect();

        try {
            if ( ! empty($params)) {
                $stmt = $this->prepare($query);
                $stmt->execute($params);

                return $stmt;
            } else {
                $event = new IPF_ORM_Event($this, IPF_ORM_Event::CONN_QUERY, $query, $params);

                $this->notifyDBListeners('preQuery', $event);

                if ( ! $event->skipOperation) {
                    $stmt = $this->dbh->query($query);
                    $this->_count++;
                }
                $this->notifyDBListeners('postQuery', $event);

                return $stmt;
            }
        } catch (IPF_ORM_Exception_Adapter $e) {
        } catch (PDOException $e) {
        }

        $this->rethrowException($e, $this);
    }

    public function exec($query, array $params = array())
    {
        $this->connect();

        try {
            if ( ! empty($params)) {
                $stmt = $this->prepare($query);
                $stmt->execute($params);

                return $stmt->rowCount();
            } else {
                $event = new IPF_ORM_Event($this, IPF_ORM_Event::CONN_EXEC, $query, $params);

                $this->notifyDBListeners('preExec', $event);
                if ( ! $event->skipOperation) {
                    $count = $this->dbh->exec($query);

                    $this->_count++;
                }
                $this->notifyDBListeners('postExec', $event);

                return $count;
            }
        } catch (IPF_ORM_Exception_Adapter $e) {
        } catch (PDOException $e) { }

        $this->rethrowException($e, $this);
    }

    public function rethrowException(Exception $e, $invoker)
    {
        $event = new IPF_ORM_Event($this, IPF_ORM_Event::CONN_ERROR);

        $this->notifyDBListeners('preError', $event);
        
        $name = 'IPF_ORM_Exception_' . $this->driverName;

        $exc  = new $name($e->getMessage(), (int) $e->getCode());
        if ( ! is_array($e->errorInfo)) {
            $e->errorInfo = array(null, null, null, null);
        }
        $exc->processErrorInfo($e->errorInfo);

         if ($this->getAttribute(IPF_ORM::ATTR_THROW_EXCEPTIONS)) {
            throw $exc;
        }
        
        $this->notifyDBListeners('postError', $event);
    }

    public function hasTable($name)
    {
        return isset($this->tables[$name]);
    }

    public function getTable($name)
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }
        $class = $name . 'Table';

        if (class_exists($class, $this->getAttribute(IPF_ORM::ATTR_AUTOLOAD_TABLE_CLASSES)) &&
                in_array('IPF_ORM_Table', class_parents($class))) {
            $table = new $class($name, $this, true);
        } else {
            $table = new IPF_ORM_Table($name, $this, true);
        }

        $this->tables[$name] = $table;

        return $table;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->tables);
    }

    public function count()
    {
        return $this->_count;
    }

    public function addTable(IPF_ORM_Table $table)
    {
        $name = $table->getComponentName();

        if (isset($this->tables[$name])) {
            return false;
        }
        $this->tables[$name] = $table;
        return true;
    }

    public function create($name)
    {
        return $this->getTable($name)->create();
    }
    
    public function createQuery()
    {
        return new IPF_ORM_Query($this);
    }

    public function flush()
    {
        $this->beginInternalTransaction();
        $this->unitOfWork->saveAll();
        $this->commit();
    }

    public function clear()
    {
        foreach ($this->tables as $k => $table) {
            $table->getRepository()->evictAll();
            $table->clear();
        }
    }

    public function evictTables()
    {
        $this->tables = array();
        $this->exported = array();
    }

    public function close()
    {
        $event = new IPF_ORM_Event($this, IPF_ORM_Event::CONN_CLOSE);

        $this->notifyDBListeners('preClose', $event);

        $this->clear();
        
        unset($this->dbh);
        $this->isConnected = false;

        $this->notifyDBListeners('postClose', $event);
    }

    public function getTransactionLevel()
    {
        return $this->transaction->getTransactionLevel();
    }

    public function errorCode()
    {
        $this->connect();

        return $this->dbh->errorCode();
    }

    public function errorInfo()
    {
        $this->connect();

        return $this->dbh->errorInfo();
    }
    
    public function getCacheDriver()
    {
        return $this->getResultCacheDriver();
    }
    
    public function getResultCacheDriver()
    {
        if ( ! $this->getAttribute(IPF_ORM::ATTR_RESULT_CACHE)) {
            throw new IPF_ORM_Exception('Result Cache driver not initialized.');
        }

        return $this->getAttribute(IPF_ORM::ATTR_RESULT_CACHE);
    }
    
    public function getQueryCacheDriver()
    {
        if ( ! $this->getAttribute(IPF_ORM::ATTR_QUERY_CACHE)) {
            throw new IPF_ORM_Exception('Query Cache driver not initialized.');
        }

        return $this->getAttribute(IPF_ORM::ATTR_QUERY_CACHE);
    }

    public function lastInsertId($table = null, $field = null)
    {
        return $this->sequence->lastInsertId($table, $field);
    }

    public function beginTransaction($savepoint = null)
    {
        return $this->transaction->beginTransaction($savepoint);
    }
    
    public function beginInternalTransaction($savepoint = null)
    {
        return $this->transaction->beginInternalTransaction($savepoint);
    }

    public function commit($savepoint = null)
    {
        return $this->transaction->commit($savepoint);
    }

    public function rollback($savepoint = null)
    {
        $this->transaction->rollback($savepoint);
    }

    public function createDatabase()
    {
        if ( ! $dsn = $this->getOption('dsn')) {
            throw new IPF_ORM_Exception('You must create your IPF_ORM_Connection by using a valid IPF style dsn in order to use the create/drop database functionality');
        }

        // Parse pdo dsn so we are aware of the connection information parts
        $info = $this->getManager()->parsePdoDsn($dsn);

        // Get the temporary connection to issue the drop database command
        $tmpConnection = $this->getTmpConnection($info);

        try {
            // Issue create database command
            $tmpConnection->export->createDatabase($info['dbname']);
        } catch (Exception $e) {}

        // Close the temporary connection used to issue the drop database command
        $this->getManager()->closeConnection($tmpConnection);

        // Re-create IPF or PDO style dsn
        if ($info['unix_socket']) {
            $dsn = array($info['scheme'] . ':unix_socket=' . $info['unix_socket'] . ';dbname=' . $info['dbname'], $this->getOption('username'), $this->getOption('password'));
        } else {
            $dsn = $info['scheme'] . '://' . $this->getOption('username') . ':' . $this->getOption('password') . '@' . $info['host'] . '/' . $info['dbname'];
        }

        // Re-open connection with the newly created database
        $this->getManager()->openConnection($dsn, $this->getName(), true);

        if (isset($e)) {
            return $e;
        } else {
            return 'Successfully created database for connection "' . $this->getName() . '" named "' . $info['dbname'] . '"';
        }
    }

    public function dropDatabase()
    {
        if ( ! $dsn = $this->getOption('dsn')) {
            throw new IPF_ORM_Exception('You must create your IPF_ORM_Connection by using a valid IPF style dsn in order to use the create/drop database functionality');
        }

        // Parse pdo dsn so we are aware of the connection information parts
        $info = $this->getManager()->parsePdoDsn($dsn);

        // Get the temporary connection to issue the drop database command
        $tmpConnection = $this->getTmpConnection($info);

        try {
            // Issue drop database command
            $tmpConnection->export->dropDatabase($info['dbname']);
        } catch (Exception $e) {}

        // Close the temporary connection used to issue the drop database command
        $this->getManager()->closeConnection($tmpConnection);

        // Re-create IPF or PDO style dsn
        if ($info['unix_socket']) {
            $dsn = array($info['scheme'] . ':unix_socket=' . $info['unix_socket'] . ';dbname=' . $info['dbname'], $this->getOption('username'), $this->getOption('password'));
        } else {
            $dsn = $info['scheme'] . '://' . $this->getOption('username') . ':' . $this->getOption('password') . '@' . $info['host'] . '/' . $info['dbname'];
        }

        // Re-open connection with the newly created database
        $this->getManager()->openConnection($dsn, $this->getName(), true);

        if (isset($e)) {
            return $e;
        } else {
            return 'Successfully dropped database for connection "' . $this->getName() . '" named "' . $info['dbname'] . '"';
        }
    }

    public function getTmpConnection($info)
    {
        if ($info['unix_socket']) {
            $pdoDsn = $info['scheme'] . ':unix_socket=' . $info['unix_socket'];
        } else {
            $pdoDsn = $info['scheme'] . ':host=' . $info['host'];
        }

        if (isset($this->export->tmpConnectionDatabase) && $this->export->tmpConnectionDatabase) {
            $pdoDsn .= ';dbname=' . $this->export->tmpConnectionDatabase;
        }

        $username = $this->getOption('username');
        $password = $this->getOption('password');

        return $this->getManager()->openConnection(new PDO($pdoDsn, $username, $password), 'ipf_tmp_connection', false);
    }

    public function modifyLimitQuery($query, $limit = false, $offset = false, $isManip = false)
    {
        return $query;
    }
    
    public function modifyLimitSubquery(IPF_ORM_Table $rootTable, $query, $limit = false,
            $offset = false, $isManip = false)
    {
        return $this->modifyLimitQuery($query, $limit, $offset, $isManip);
    }

    public function __toString()
    {
        return IPF_ORM_Utils::getConnectionAsString($this);
    }

    public function notifyDBListeners($method, $event)
    {
        foreach ($this->dbListeners as $listener)
            if (is_callable(array($listener, $method)))
                $listener->$method($event);
    }
}

