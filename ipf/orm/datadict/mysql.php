<?php

class IPF_ORM_DataDict_Mysql extends IPF_ORM_DataDict
{
    protected $keywords = array(
                          'ADD', 'ALL', 'ALTER',
                          'ANALYZE', 'AND', 'AS',
                          'ASC', 'ASENSITIVE', 'BEFORE',
                          'BETWEEN', 'BIGINT', 'BINARY',
                          'BLOB', 'BOTH', 'BY', 'BIT',
                          'CALL', 'CASCADE', 'CASE',
                          'CHANGE', 'CHAR', 'CHARACTER',
                          'CHECK', 'COLLATE', 'COLUMN',
                          'CONDITION', 'CONNECTION', 'CONSTRAINT',
                          'CONTINUE', 'CONVERT', 'CREATE',
                          'CROSS', 'CURRENT_DATE', 'CURRENT_TIME',
                          'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR',
                          'DATABASE', 'DATABASES', 'DAY_HOUR',
                          'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND',
                          'DEC', 'DECIMAL', 'DECLARE',
                          'DEFAULT', 'DELAYED', 'DELETE',
                          'DESC', 'DESCRIBE', 'DETERMINISTIC',
                          'DISTINCT', 'DISTINCTROW', 'DIV',
                          'DOUBLE', 'DROP', 'DUAL',
                          'EACH', 'ELSE', 'ELSEIF',
                          'ENCLOSED', 'ESCAPED', 'EXISTS',
                          'EXIT', 'EXPLAIN', 'FALSE',
                          'FETCH', 'FLOAT', 'FLOAT4',
                          'FLOAT8', 'FOR', 'FORCE',
                          'FOREIGN', 'FROM', 'FULLTEXT',
                          'GRANT', 'GROUP', 'HAVING',
                          'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE',
                          'HOUR_SECOND', 'IF', 'IGNORE',
                          'IN', 'INDEX', 'INFILE',
                          'INNER', 'INOUT', 'INSENSITIVE',
                          'INSERT', 'INT', 'INT1',
                          'INT2', 'INT3', 'INT4',
                          'INT8', 'INTEGER', 'INTERVAL',
                          'INTO', 'IS', 'ITERATE',
                          'JOIN', 'KEY', 'KEYS',
                          'KILL', 'LEADING', 'LEAVE',
                          'LEFT', 'LIKE', 'LIMIT',
                          'LINES', 'LOAD', 'LOCALTIME',
                          'LOCALTIMESTAMP', 'LOCK', 'LONG',
                          'LONGBLOB', 'LONGTEXT', 'LOOP',
                          'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB',
                          'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT',
                          'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD',
                          'MODIFIES', 'NATURAL', 'NOT',
                          'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC',
                          'ON', 'OPTIMIZE', 'OPTION',
                          'OPTIONALLY', 'OR', 'ORDER',
                          'OUT', 'OUTER', 'OUTFILE',
                          'PRECISION', 'PRIMARY', 'PROCEDURE',
                          'PURGE', 'RAID0', 'READ',
                          'READS', 'REAL', 'REFERENCES',
                          'REGEXP', 'RELEASE', 'RENAME',
                          'REPEAT', 'REPLACE', 'REQUIRE',
                          'RESTRICT', 'RETURN', 'REVOKE',
                          'RIGHT', 'RLIKE', 'SCHEMA',
                          'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT',
                          'SENSITIVE', 'SEPARATOR', 'SET',
                          'SHOW', 'SMALLINT', 'SONAME',
                          'SPATIAL', 'SPECIFIC', 'SQL',
                          'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING',
                          'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT',
                          'SSL', 'STARTING', 'STRAIGHT_JOIN',
                          'TABLE', 'TERMINATED', 'THEN',
                          'TINYBLOB', 'TINYINT', 'TINYTEXT',
                          'TO', 'TRAILING', 'TRIGGER',
                          'TRUE', 'UNDO', 'UNION',
                          'UNIQUE', 'UNLOCK', 'UNSIGNED',
                          'UPDATE', 'USAGE', 'USE',
                          'USING', 'UTC_DATE', 'UTC_TIME',
                          'UTC_TIMESTAMP', 'VALUES', 'VARBINARY',
                          'VARCHAR', 'VARCHARACTER', 'VARYING',
                          'WHEN', 'WHERE', 'WHILE',
                          'WITH', 'WRITE', 'X509',
                          'XOR', 'YEAR_MONTH', 'ZEROFILL'
                          );

    public function getNativeDeclaration($field)
    {
        if ( ! isset($field['type'])) {
            throw new IPF_ORM_DataDict_Exception('Missing column type.');
        }

        switch ($field['type']) {
            case 'char':
                $length = ( ! empty($field['length'])) ? $field['length'] : false;

                return $length ? 'CHAR('.$length.')' : 'CHAR(255)';
            case 'varchar':
            case 'array':
            case 'object':
            case 'string':
            case 'gzip':
                if ( ! isset($field['length'])) {
                    if (array_key_exists('default', $field)) {
                        $field['length'] = $this->conn->varchar_max_length;
                    } else {
                        $field['length'] = false;
                    }
                }

                $length = ($field['length'] <= $this->conn->varchar_max_length) ? $field['length'] : false;
                $fixed  = (isset($field['fixed'])) ? $field['fixed'] : false;

                return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                    : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
            case 'clob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65532) {
                        return 'TEXT';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMTEXT';
                    }
                }
                return 'LONGTEXT';
            case 'blob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65532) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'enum':
                if ($this->conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                    $values = array();
                    foreach ($field['values'] as $value) {
                      $values[] = $this->conn->quote($value, 'varchar');
                    }
                    return 'ENUM('.implode(', ', $values).')';
                }
                // fall back to integer
            case 'integer':
            case 'int':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 1) {
                        return 'TINYINT';
                    } elseif ($length == 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3) {
                        return 'MEDIUMINT';
                    } elseif ($length == 4) {
                        return 'INT';
                    } elseif ($length > 4) {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'datetime':
                return 'DATETIME';
            case 'timestamp':
                return 'TIMESTAMP';
            case 'float':
            case 'double':
                return 'DOUBLE';
            case 'decimal':
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(IPF_ORM::ATTR_DECIMAL_PLACES);
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if (is_array($length)) {
                        list($length, $scale) = $length;
                    }
                } else {
                    $length = 18;
                }
                return 'DECIMAL('.$length.','.$scale.')';
            case 'bit':
                return 'BIT';
        }
        throw new IPF_ORM_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }

    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $dbType = strtok($dbType, '(), ');
        if ($dbType == 'national') {
            $dbType = strtok('(), ');
        }
        if (isset($field['length'])) {
            $length = $field['length'];
            $decimal = '';
        } else {
            $length = strtok('(), ');
            $decimal = strtok('(), ');
        }
        $type = array();
        $unsigned = $fixed = null;

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        $values = null;

        switch ($dbType) {
            case 'tinyint':
                $type[] = 'integer';
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 1;
            break;
            case 'smallint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 2;
            break;
            case 'mediumint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 3;
            break;
            case 'int':
            case 'integer':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 4;
            break;
            case 'bigint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 8;
            break;
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'varchar':
                $fixed = false;
            case 'string':
            case 'char':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                    if ($decimal == 'binary') {
                        $type[] = 'blob';
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'enum':
                $type[] = 'enum';
                preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $field['type'], $matches);
                $length = 0;
                $fixed = false;
                if (is_array($matches)) {
                    foreach ($matches[1] as &$value) {
                        $value = str_replace('\'\'', '\'', $value);
                        $length = max($length, strlen($value));
                    }
                    if ($length == '1' && count($matches[1]) == 2) {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['name'])) {
                            $type = array_reverse($type);
                        }
                    }

                    $values = $matches[1];
                }
                $type[] = 'integer';
                break;
            case 'set':
                $fixed = false;
                $type[] = 'text';
                $type[] = 'integer';
            break;
            case 'date':
                $type[] = 'date';
                $length = null;
            break;
            case 'datetime':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
            break;
            case 'time':
                $type[] = 'time';
                $length = null;
            break;
            case 'float':
            case 'double':
            case 'real':
                $type[] = 'float';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
            break;
            case 'unknown':
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
            break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
                $type[] = 'blob';
                $length = null;
            break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
            break;
            case 'bit':
                $type[] = 'bit';
            break;
            default:
                throw new IPF_ORM_Exception('unknown database attribute type: ' . $dbType);
        }

        $length = ((int) $length == 0) ? null : (int) $length;

        if ($values === null) {
            return array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed);
        } else {
            return array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed, 'values' => $values);
        }
    }

    public function getIntegerDeclaration($name, $field)
    {
        $default = $autoinc = '';
        if ( ! empty($field['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            if (is_null($field['default'])) {
                $default = ' DEFAULT NULL';
            } else {
                $default = ' DEFAULT '.$this->conn->quote($field['default']);
            }
        }

        $notnull  = (isset($field['notnull'])  && $field['notnull'])  ? ' NOT NULL' : '';
        $unsigned = (isset($field['unsigned']) && $field['unsigned']) ? ' UNSIGNED' : '';

        $name = $this->conn->quoteIdentifier($name, true);

        return $name . ' ' . $this->getNativeDeclaration($field) . $unsigned . $default . $notnull . $autoinc;
    }
}

