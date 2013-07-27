<?php

class IPF_ORM_Connection_Mysql extends IPF_ORM_Connection
{
    protected $driverName = 'Mysql';

    protected static $keywords = array(
        'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC',
        'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB',
        'BOTH', 'BY', 'BIT', 'CALL', 'CASCADE', 'CASE', 'CHANGE',
        'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN',
        'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CONTINUE',
        'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME',
        'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE',
        'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE',
        'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT',
        'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC',
        'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP', 'DUAL',
        'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS',
        'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4',
        'FLOAT8', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT',
        'GRANT', 'GROUP', 'HAVING', 'HIGH_PRIORITY',
        'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF',
        'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT',
        'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3',
        'INT4', 'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IS',
        'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LEADING', 'LEAVE',
        'LEFT', 'LIKE', 'LIMIT', 'LINES', 'LOAD', 'LOCALTIME',
        'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT',
        'LOOP', 'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT',
        'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND',
        'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT',
        'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'OPTIMIZE',
        'OPTION', 'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER',
        'OUTFILE', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE',
        'RAID0', 'READ', 'READS', 'REAL', 'REFERENCES', 'REGEXP',
        'RELEASE', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE',
        'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE', 'SCHEMA',
        'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE',
        'SEPARATOR', 'SET', 'SHOW', 'SMALLINT', 'SONAME', 'SPATIAL',
        'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING',
        'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT',
        'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE', 'TERMINATED',
        'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING',
        'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK',
        'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE',
        'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR',
        'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH',
        'WRITE', 'X509', 'XOR', 'YEAR_MONTH', 'ZEROFILL'
    );

    public function __construct(IPF_ORM_Manager $manager, $adapter)
    {
        $this->attributes[IPF_ORM::ATTR_DEFAULT_TABLE_TYPE] = 'INNODB';

        $this->properties['string_quoting'] = array('start' => "'",
                                                    'end' => "'",
                                                    'escape' => '\\',
                                                    'escape_pattern' => '\\');

        $this->properties['identifier_quoting'] = '`';

        $this->properties['sql_comments'] = array(
                                            array('start' => '-- ', 'end' => "\n", 'escape' => false),
                                            array('start' => '#', 'end' => "\n", 'escape' => false),
                                            array('start' => '/*', 'end' => '*/', 'escape' => false),
                                            );

        $this->properties['varchar_max_length'] = 255;

        parent::__construct($manager, $adapter);
    }

    protected function onConnect()
    {
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->exec('SET NAMES \'utf8\'');
    }

    public function quoteIdentifier($str)
    {
        $quote = $this->identifier_quoting;
        $q = array();
        foreach (explode('.', $str) as $s) {
            if (in_array(strtoupper($s), self::$keywords))
                $q[] = $quote . str_replace($quote, $quote . $quote, $s) . $quote;
            else
                $q[] = $s;
        }
        return implode('.', $q);
    }

    public function getDatabaseName()
    {
        return $this->fetchOne('SELECT DATABASE()');
    }

    public function replace(IPF_ORM_Table $table, array $fields, array $keys)
    {
        if (empty($keys)) {
            throw new IPF_ORM_Exception('Not specified which fields are keys');
        }
        $columns = array();
        $values = array();
        $params = array();
        foreach ($fields as $fieldName => $value) {
            $columns[] = $table->getColumnName($fieldName);
            $values[] = '?';
            $params[] = $value;
        }
        $query = 'REPLACE INTO ' . $table->getTableName() . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
        return $this->exec($query, $params);
    }

    public function modifyLimitQuery($query, $limit = false,$offset = false,$isManip=false)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;
        
        if ($limit && $offset) {
            $query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        } elseif ($limit && ! $offset) {
            $query .= ' LIMIT ' . $limit;
        } elseif ( ! $limit && $offset) {
            $query .= ' LIMIT 999999999999 OFFSET ' . $offset;
        }
        return $query;
    }
}

