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
        $this->attributes = array(
            IPF_ORM::ATTR_LOAD_REFERENCES         => true,
            IPF_ORM::ATTR_IDXNAME_FORMAT          => "%s_idx",
            IPF_ORM::ATTR_SEQNAME_FORMAT          => "%s_seq",
            IPF_ORM::ATTR_TBLNAME_FORMAT          => "%s",
            IPF_ORM::ATTR_QUOTE_IDENTIFIER        => false,
            IPF_ORM::ATTR_SEQCOL_NAME             => 'id',
            IPF_ORM::ATTR_PORTABILITY             => IPF_ORM::PORTABILITY_ALL,
            IPF_ORM::ATTR_EXPORT                  => IPF_ORM::EXPORT_ALL,
            IPF_ORM::ATTR_DECIMAL_PLACES          => 2,
            IPF_ORM::ATTR_DEFAULT_PARAM_NAMESPACE => 'ipf',
        );
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

    public static function connection()
    {
        return IPF_ORM_Manager::getInstance()->getCurrentConnection();
    }

    public function openConnection($adapter, $name = null, $setCurrent = true)
    {
        if (is_object($adapter)) {
            if (!($adapter instanceof PDO))
                throw new IPF_ORM_Exception("First argument should be an instance of PDO");
            $driverName = $adapter->getAttribute(PDO::ATTR_DRIVER_NAME);
        } else {
            $adapter = $this->connectionParameters($adapter);
            $driverName = $adapter['scheme'];
        }

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
        $conn->setName($name);

        $this->_connections[$name] = $conn;

        if ($setCurrent) {
            $this->_currIndex = $name;
        }
        return $this->_connections[$name];
    }
    
    public function connectionParameters($adapter)
    {
        if (is_array($adapter)) {
            if (!count($adapter))
                throw new IPF_ORM_Exception('Empty data source name given.');

            if (array_key_exists('database', $adapter)) {
                $adapter['dsn'] = $this->makeDsnForPDO($adapter['driver'], $adapter['host'], @$adapter['port'], $adapter['database']);
                $adapter['scheme'] = $adapter['driver'];
                return $adapter;
            } else {
                $dsn = urldecode($adapter[0]);
                $result = $this->parseDsn($dsn);
                $result['username'] = (isset($adapter[1])) ? urldecode($adapter[1]) : null;
                $result['password'] = (isset($adapter[2])) ? urldecode($adapter[2]) : null;
                return $result;
            }
        } else {
            $result = $this->parseDsn($adapter);
            $result['username'] = $result['user'];
            $result['password'] = $result['pass'];
            return $result;
        }
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
                } else {
                    //fix windows dsn we have to add host: to path and set host to null
                    if (isset($parts['host'])) {
                        $parts['path'] = $parts['host'] . ":" . $parts["path"];
                        $parts['host'] = null;
                    }
                    $parts['database'] = $parts['path'];
                }

                $parts['dsn'] = $this->makeDsnForPDO($parts['scheme'], $parts['host'], @$parts['port'], $parts['database']);

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

                $parts['dsn'] = $this->makeDsnForPDO($parts['scheme'], $parts['host'], @$parts['port'], $parts['database']);

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

                $parts['dsn'] = $this->makeDsnForPDO($parts['scheme'], $parts['host'], @$parts['port'], $parts['database']);

                break;
            default:
                throw new IPF_ORM_Exception('Unknown driver '.$parts['scheme']);
        }

        return $parts;
    }

    public function makeDsnForPDO($driver, $host, $port, $database)
    {
        switch ($driver) {
            case 'sqlite':
            case 'sqlite2':
            case 'sqlite3':
                if ($host == ':memory') {
                    return 'sqlite::memory:';
                } else {
                    return $driver . ':' . $database;
                }

            case 'mssql':
            case 'dblib':
                return $driver . ':host=' . $host . ($port ? ':' . $port : '') . ';dbname=' . $database;

            case 'mysql':
            case 'informix':
            case 'oci8':
            case 'oci':
            case 'firebird':
            case 'pgsql':
            case 'odbc':
            case 'mock':
            case 'oracle':
                return $driver . ':host=' . $host . ($port ? ';port=' . $port : '') . ';dbname=' . $database;

            default:
                throw new IPF_ORM_Exception('Unknown driver '.$driver);
        }
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

