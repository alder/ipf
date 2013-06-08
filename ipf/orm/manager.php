<?php

class IPF_ORM_Manager extends IPF_ORM_Configurable implements Countable, IteratorAggregate
{
    protected $_connections   = array();
    protected $_bound         = array();
    protected $_index         = 0;
    protected $_currIndex     = 0;
    protected $_queryRegistry;
    public $dbListeners = array();

    private function __construct()
    {
        IPF_ORM_Locator_Injectable::initNullObject(new IPF_ORM_Null);
    }

    public function setDefaultAttributes()
    {
        static $init = false;
        if ( ! $init) {
            $init = true;
            $attributes = array(
                        IPF_ORM::ATTR_CACHE                    => null,
                        IPF_ORM::ATTR_RESULT_CACHE             => null,
                        IPF_ORM::ATTR_QUERY_CACHE              => null,
                        IPF_ORM::ATTR_LOAD_REFERENCES          => true,
                        IPF_ORM::ATTR_THROW_EXCEPTIONS         => true,
                        IPF_ORM::ATTR_QUERY_LIMIT              => IPF_ORM::LIMIT_RECORDS,
                        IPF_ORM::ATTR_IDXNAME_FORMAT           => "%s_idx",
                        IPF_ORM::ATTR_SEQNAME_FORMAT           => "%s_seq",
                        IPF_ORM::ATTR_TBLNAME_FORMAT           => "%s",
                        IPF_ORM::ATTR_QUOTE_IDENTIFIER         => false,
                        IPF_ORM::ATTR_SEQCOL_NAME              => 'id',
                        IPF_ORM::ATTR_PORTABILITY              => IPF_ORM::PORTABILITY_ALL,
                        IPF_ORM::ATTR_EXPORT                   => IPF_ORM::EXPORT_ALL,
                        IPF_ORM::ATTR_DECIMAL_PLACES           => 2,
                        IPF_ORM::ATTR_DEFAULT_PARAM_NAMESPACE  => 'ipf',
                        IPF_ORM::ATTR_AUTOLOAD_TABLE_CLASSES   => false,
                        IPF_ORM::ATTR_USE_DQL_CALLBACKS        => false,
                        ); 
            foreach ($attributes as $attribute => $value) {
                $old = $this->getAttribute($attribute);
                if ($old === null) {
                    $this->setAttribute($attribute,$value);
                }
            }
            return true;
        }
        return false;
    }

    public static function getInstance()
    {
        static $instance;
        if ( ! isset($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    public function getQueryRegistry()
    {
      	if ( ! isset($this->_queryRegistry)) {
      	   $this->_queryRegistry = new IPF_ORM_Query_Registry;
      	}
        return $this->_queryRegistry;
    }

    public function setQueryRegistry(IPF_ORM_Query_Registry $registry)
    {
        $this->_queryRegistry = $registry;
        
        return $this;
    }

    public static function connection($adapter = null, $name = null)
    {
        if ($adapter == null) {
            return IPF_ORM_Manager::getInstance()->getCurrentConnection();
        } else {
            return IPF_ORM_Manager::getInstance()->openConnection($adapter, $name);
        }
    }

    public function openConnection($adapter, $name = null, $setCurrent = true, $persistent = false)
    {
        if (is_object($adapter)) {
            if ( ! ($adapter instanceof PDO) && ! in_array('IPF_ORM_Adapter_Interface', class_implements($adapter))) {
                throw new IPF_ORM_Exception("First argument should be an instance of PDO or implement IPF_ORM_Adapter_Interface");
            }

            $driverName = $adapter->getAttribute(IPF_ORM::ATTR_DRIVER_NAME);
        } else if (is_array($adapter)) {
            if ( ! isset($adapter[0])) {
                throw new IPF_ORM_Exception('Empty data source name given.');
            }
            $e = explode(':', $adapter[0]);

            if ($e[0] == 'uri') {
                $e[0] = 'odbc';
            }

            $parts['dsn']    = $adapter[0];
            $parts['scheme'] = $e[0];
            $parts['user']   = (isset($adapter[1])) ? $adapter[1] : null;
            $parts['pass']   = (isset($adapter[2])) ? $adapter[2] : null;
            $driverName = $e[0];
            $adapter = $parts;
        } else {
            $parts = $this->parseDsn($adapter);
            $driverName = $parts['scheme'];
            $adapter = $parts;
        }

        // Decode adapter information
        if (is_array($adapter)) {
            foreach ($adapter as $key => $value) {
                $adapter[$key]  = $value?urldecode($value):null;
            }
        }

        // initialize the default attributes
        $this->setDefaultAttributes();

        if ($name !== null) {
            $name = (string) $name;
            if (isset($this->_connections[$name])) {
                if ($setCurrent) {
                    $this->_currIndex = $name;
                }
                return $this->_connections[$name];
            }
        } else {
            $name = $this->_index;
            $this->_index++;
        }

        $drivers = array('mysql'    => 'IPF_ORM_Connection_Mysql',
                         //'sqlite'   => 'IPF_ORM_Connection_Sqlite',
                         //'pgsql'    => 'IPF_ORM_Connection_Pgsql',
                         //'oci'      => 'IPF_ORM_Connection_Oracle',
                         //'oci8'     => 'IPF_ORM_Connection_Oracle',
                         //'oracle'   => 'IPF_ORM_Connection_Oracle',
                         //'mssql'    => 'IPF_ORM_Connection_Mssql',
                         //'dblib'    => 'IPF_ORM_Connection_Mssql',
                         //'firebird' => 'IPF_ORM_Connection_Firebird',
                         //'informix' => 'IPF_ORM_Connection_Informix',
                         //'mock'     => 'IPF_ORM_Connection_Mock'
                         );

        if ( ! isset($drivers[$driverName])) {
            throw new IPF_ORM_Exception('Unknown driver ' . $driverName);
        }

        $className = $drivers[$driverName];
        $conn = new $className($this, $adapter);
        if ($persistent)
            $conn->setOption(IPF_ORM::ATTR_PERSISTENT, true);
        $conn->setName($name);
        $conn->setCharset('utf8');

        $this->_connections[$name] = $conn;

        if ($setCurrent) {
            $this->_currIndex = $name;
        }
        return $this->_connections[$name];
    }
    
    public function parsePdoDsn($dsn)
    {
        $parts = array();

        $names = array('dsn', 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment', 'unix_socket');

        foreach ($names as $name) {
            if ( ! isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        $e = explode(':', $dsn);
        $parts['scheme'] = $e[0];
        $parts['dsn'] = $dsn;

        $e = explode(';', $e[1]);
        foreach ($e as $string) {
            if ($string) {
                $e2 = explode('=', $string);

                if (isset($e2[0]) && isset($e2[1])) {
                    list($key, $value) = $e2;
                    $parts[$key] = $value;
                }
            }
        }

        return $parts;
    }

    protected function _buildDsnPartsArray($dsn)
    {
        // fix sqlite dsn so that it will parse correctly
        $dsn = str_replace("////", "/", $dsn);
        $dsn = str_replace("\\", "/", $dsn);
        $dsn = preg_replace("/\/\/\/(.*):\//", "//$1:/", $dsn);

        // silence any warnings
        $parts = @parse_url($dsn);

        $names = array('dsn', 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment', 'unix_socket');

        foreach ($names as $name) {
            if ( ! isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        if (count($parts) == 0 || ! isset($parts['scheme'])) {
            throw new IPF_ORM_Exception('Could not parse dsn');
        }

        return $parts;
    }

    public function parseDsn($dsn)
    {
        $parts = $this->_buildDsnPartsArray($dsn);

        switch ($parts['scheme']) {
            case 'sqlite':
            case 'sqlite2':
            case 'sqlite3':
                if (isset($parts['host']) && $parts['host'] == ':memory') {
                    $parts['database'] = ':memory:';
                    $parts['dsn']      = 'sqlite::memory:';
                } else {
                    //fix windows dsn we have to add host: to path and set host to null
                    if (isset($parts['host'])) {
                        $parts['path'] = $parts['host'] . ":" . $parts["path"];
                        $parts['host'] = null;
                    }
                    $parts['database'] = $parts['path'];
                    $parts['dsn'] = $parts['scheme'] . ':' . $parts['path'];
                }

                break;

            case 'mssql':
            case 'dblib':
                if ( ! isset($parts['path']) || $parts['path'] == '/') {
                    throw new IPF_ORM_Exception('No database available in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if ( ! isset($parts['host'])) {
                    throw new IPF_ORM_Exception('No hostname set in data source name');
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                              . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port']:null) . ';dbname='
                              . $parts['database'];

                break;

            case 'mysql':
            case 'informix':
            case 'oci8':
            case 'oci':
            case 'firebird':
            case 'pgsql':
            case 'odbc':
            case 'mock':
            case 'oracle':
                if ( ! isset($parts['path']) || $parts['path'] == '/') {
                    throw new IPF_ORM_Exception('No database available in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if ( ! isset($parts['host'])) {
                    throw new IPF_ORM_Exception('No hostname set in data source name');
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                              . $parts['host'] . (isset($parts['port']) ? ';port=' . $parts['port']:null) . ';dbname='
                              . $parts['database'];

                break;
            default:
                throw new IPF_ORM_Exception('Unknown driver '.$parts['scheme']);
        }

        return $parts;
    }

    public function getConnection($name)
    {
        if ( ! isset($this->_connections[$name])) {
            throw new IPF_ORM_Exception('Unknown connection: ' . $name);
        }

        return $this->_connections[$name];
    }

    public function getConnectionName(IPF_ORM_Connection $conn)
    {
        return array_search($conn, $this->_connections, true);
    }

    public function bindComponent($componentName, $connectionName)
    {
        $this->_bound[$componentName] = $connectionName;
    }

    public function getConnectionForComponent($componentName)
    {
        //IPF_ORM::autoload($componentName);

        if (isset($this->_bound[$componentName])) {
            return $this->getConnection($this->_bound[$componentName]);
        }

        return $this->getCurrentConnection();
    }
    
    public function hasConnectionForComponent($componentName = null)
    {
        return isset($this->_bound[$componentName]);
    }

    public function closeConnection(IPF_ORM_Connection $connection)
    {
        $connection->close();

        $key = array_search($connection, $this->_connections, true);

        if ($key !== false) {
            unset($this->_connections[$key]);
        }
        $this->_currIndex = key($this->_connections);

        unset($connection);
    }

    public function getConnections()
    {
        return $this->_connections;
    }

    public function setCurrentConnection($key)
    {
        $key = (string) $key;
        if ( ! isset($this->_connections[$key])) {
            throw new InvalidKeyException();
        }
        $this->_currIndex = $key;
    }

    public function contains($key)
    {
        return isset($this->_connections[$key]);
    }

    public function count()
    {
        return count($this->_connections);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_connections);
    }

    public function getCurrentConnection()
    {
        $i = $this->_currIndex;
        if ( ! isset($this->_connections[$i])) {
            throw new IPF_ORM_Exception('There is no open connection');
        }
        return $this->_connections[$i];
    }

    public function createDatabases($specifiedConnections = array())
    {
        if ( ! is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        $results = array();

        foreach ($this as $name => $connection) {
            if ( ! empty($specifiedConnections) && ! in_array($name, $specifiedConnections)) {
                continue;
            }

            $results[$name] = $connection->createDatabase();
        }

        return $results;
    }

    public function dropDatabases($specifiedConnections = array())
    {
        if ( ! is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        $results = array();

        foreach ($this as $name => $connection) {
            if ( ! empty($specifiedConnections) && ! in_array($name, $specifiedConnections)) {
                continue;
            }

            $results[$name] = $connection->dropDatabase();
        }

        return $results;
    }

    public function __toString()
    {
        $r[] = "<pre>";
        $r[] = "IPF_ORM_Manager";
        $r[] = "Connections : ".count($this->_connections);
        $r[] = "</pre>";
        return implode("\n",$r);
    }
}
