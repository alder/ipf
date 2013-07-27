<?php

class IPF_ORM_Export_Mysql extends IPF_ORM_Export
{
    public function createDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name);
    }

    public function dropDatabaseSql($name)
    {
        return 'DROP DATABASE ' . $this->conn->quoteIdentifier($name);
    }

    public function createTableSql($name, array $fields, array $options = array()) 
    {
        if ( ! $name)
            throw new IPF_ORM_Exception('no valid table name specified');

        if (empty($fields)) {
            throw new IPF_ORM_Exception('no fields specified for table "'.$name.'"');
        }
        $queryFields = $this->getFieldDeclarationList($fields);

        // build indexes for all foreign key fields (needed in MySQL!!)
        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $fk) {
                $local = $fk['local'];
                $found = false;
                if (isset($options['indexes'])) {
                    foreach ($options['indexes'] as $definition) {
                        if (is_string($definition['fields'])) {
                            // Check if index already exists on the column                            
                            $found = ($local == $definition['fields']);                        
                        } else if (in_array($local, $definition['fields']) && count($definition['fields']) === 1) {
                            // Index already exists on the column
                            $found = true;
                        }
                    }
                }
                if (isset($options['primary']) && !empty($options['primary']) &&
                        in_array($local, $options['primary'])) {
                    // field is part of the PK and therefore already indexed
                    $found = true;
                }
                
                if ( ! $found) {
                    if (is_array($local)) {
                      foreach($local as $localidx) {
                        $options['indexes'][$localidx] = array('fields' => array($localidx => array()));
                      }
                    } else {
                      $options['indexes'][$local] = array('fields' => array($local => array()));                      
                    }
                }
            }
        }

        // add all indexes
        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclaration($index, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_values($options['primary']);
            $keyColumns = array_map(array($this->conn, 'quoteIdentifier'), $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name) . ' (' . $queryFields . ')';

        $optionStrings = array();

        if (isset($options['comment'])) {
            $optionStrings['comment'] = 'COMMENT = ' . $this->conn->quote($options['comment'], 'text');
        }
        if (isset($options['charset'])) {
            $optionStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];
        }
        if (isset($options['collate'])) {
            $optionStrings['collate'] = 'COLLATE ' . $options['collate'];
        }

        $type = false;

        // get the type of the table
        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->conn->getAttribute(IPF_ORM::ATTR_DEFAULT_TABLE_TYPE);
        }

        if ($type) {
            $optionStrings[] = 'ENGINE = ' . $type;
        }

        if ( ! empty($optionStrings)) {
            $query.= ' '.implode(' ', $optionStrings);
        }
        $sql[] = $query;

        if (isset($options['foreignKeys'])) {

            foreach ((array) $options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->createForeignKeySql($name, $definition);
                }
            }
        }
        return $sql;
    }

    public function getDeclaration($name, array $field)
    {
        $declaration = $this->conn->quoteIdentifier($name) . ' ';

        if (!isset($field['type']))
            throw new IPF_ORM_Exception('Missing column type.');

        switch ($field['type']) {
            case 'char':
                $length = ( ! empty($field['length'])) ? $field['length'] : false;

                $declaration .= $length ? 'CHAR('.$length.')' : 'CHAR(255)';
                break;
            case 'varchar':
            case 'array':
            case 'object':
            case 'string':
            case 'gzip':
                if (!isset($field['length'])) {
                    if (array_key_exists('default', $field)) {
                        $field['length'] = $this->conn->varchar_max_length;
                    } else {
                        $field['length'] = false;
                    }
                }

                $length = ($field['length'] <= $this->conn->varchar_max_length) ? $field['length'] : false;
                $fixed  = (isset($field['fixed'])) ? $field['fixed'] : false;

                $declaration .= $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                    : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
                break;
            case 'clob':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65532) {
                        return 'TEXT';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMTEXT';
                    }
                }
                $declaration .= 'LONGTEXT';
                break;
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
                $declaration .= 'LONGBLOB';
                break;
            case 'enum':
                if ($this->conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                    $values = array();
                    foreach ($field['values'] as $value) {
                      $values[] = $this->conn->quote($value, 'varchar');
                    }
                    $declaration .= 'ENUM('.implode(', ', $values).')';
                    break;
                }
                // fall back to integer
            case 'integer':
            case 'int':
                $type = 'INT';
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 1) {
                        $type = 'TINYINT';
                    } elseif ($length == 2) {
                        $type = 'SMALLINT';
                    } elseif ($length == 3) {
                        $type = 'MEDIUMINT';
                    } elseif ($length == 4) {
                        $type = 'INT';
                    } elseif ($length > 4) {
                        $type = 'BIGINT';
                    }
                }
                $declaration .= $type;
                if (isset($field['unsigned']) && $field['unsigned'])
                    $declaration .= ' UNSIGNED';
                break;
            case 'boolean':
                $declaration .= 'TINYINT(1)';
                break;
            case 'date':
                $declaration .= 'DATE';
                break;
            case 'time':
                $declaration .= 'TIME';
                break;
            case 'datetime':
                $declaration .= 'DATETIME';
                break;
            case 'timestamp':
                $declaration .= 'TIMESTAMP';
                break;
            case 'float':
            case 'double':
                $declaration .= 'DOUBLE';
                break;
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
                $declaration .= 'DECIMAL('.$length.','.$scale.')';
                break;
            case 'bit':
                $declaration .= 'BIT';
                break;
            default:
                throw new IPF_ORM_Exception('Unknown field type \'' . $field['type'] .  '\'.');
        }

        if (isset($field['charset']) && $field['charset'])
            $declaration .= ' CHARACTER SET ' . $field['charset'];

        if (isset($field['collate']) && $field['collate'])
            $declaration .= ' COLLATE ' . $field['collate'];

        if (isset($field['notnull']) && $field['notnull'])
            $declaration .= ' NOT NULL';

        if (!empty($field['autoincrement'])) {
            $declaration .= ' AUTO_INCREMENT';
        } else {
            $declaration .= $this->getDefaultFieldDeclaration($field);

            if (isset($field['unique']) && $field['unique'])
                $declaration .= ' UNIQUE';
        }

        if (isset($field['comment']) && $field['comment'])
            $declaration .= ' COMMENT ' . $this->conn->quote($field['comment'], 'varchar');

        return $declaration;
    }

    private function getDefaultFieldDeclaration($field)
    {
        $default = '';
        if (isset($field['default']) && (!isset($field['length']) || $field['length'] <= 255)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : $this->valid_default_values[$field['type']];
            }

            if ($field['default'] === '' && ($this->conn->getAttribute(IPF_ORM::ATTR_PORTABILITY) & IPF_ORM::PORTABILITY_EMPTY_TO_NULL))
                $field['default'] = null;

            if (is_null($field['default'])) {
                $default = ' DEFAULT NULL';
            } else {
                if ($field['type'] === 'boolean') {
                    $fieldType = 'boolean';
                    $field['default'] = $this->conn->convertBooleans($field['default']);
                } elseif ($field['type'] == 'enum' && $this->conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                    $fieldType = 'varchar';
                } else {
                    $fieldType = $field['type'];
                }
                $default = ' DEFAULT ' . $this->conn->quote($field['default'], $fieldType);
            }
        }
        return $default;
    }

    public function alterTableSql($name, array $changes, $check = false)
    {
        if ( ! $name) {
            throw new IPF_ORM_Exception('no valid table name specified');
        }
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'rename':
                case 'name':
                    break;
                default:
                    throw new IPF_ORM_Exception('change type "' . $changeName . '" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $query = '';
        if ( ! empty($changes['name'])) {
            $change_name = $this->conn->quoteIdentifier($changes['name']);
            $query .= 'RENAME TO ' . $change_name;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $this->getDeclaration($fieldName, $field);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $fieldName = $this->conn->quoteIdentifier($fieldName);
                $query .= 'DROP ' . $fieldName;
            }
        }

        $rename = array();
        if ( ! empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $rename[$field['name']] = $fieldName;
            }
        }

        if ( ! empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                if (isset($rename[$fieldName])) {
                    $oldFieldName = $rename[$fieldName];
                    unset($rename[$fieldName]);
                } else {
                    $oldFieldName = $fieldName;
                }
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName);
                $query .= 'CHANGE ' . $oldFieldName . ' ' 
                        . $this->getDeclaration($fieldName, $field['definition']);
            }
        }

        if ( ! empty($rename) && is_array($rename)) {
            foreach ($rename as $renameName => $renamedField) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamedField];
                $renamedField = $this->conn->quoteIdentifier($renamedField);
                $query .= 'CHANGE ' . $renamedField . ' '
                        . $this->getDeclaration($field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name);
        
        return 'ALTER TABLE ' . $name . ' ' . $query;
    }

    public function createIndexSql($table, $name, array $definition)
    {
        $table  = $table;
        $name   = $this->conn->formatter->getIndexName($name);
        $name   = $this->conn->quoteIdentifier($name);
        $type   = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw new IPF_ORM_Exception('Unknown index type ' . $definition['type']);
            }
        }
        $query  = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    public function getIndexDeclaration($name, array $definition)
    {
        $name   = $this->conn->formatter->getIndexName($name);
        $type   = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw new IPF_ORM_Exception('Unknown index type ' . $definition['type']);
            }
        }
        
        if ( ! isset($definition['fields'])) {
            throw new IPF_ORM_Exception('No index columns given.');
        }
        if ( ! is_array($definition['fields'])) {
            $definition['fields'] = array($definition['fields']);
        }

        $query = $type . 'INDEX ' . $this->conn->quoteIdentifier($name);

        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';
        
        return $query;
    }

    public function getIndexFieldDeclarationList(array $fields)
    {
        $declFields = array();

        foreach ($fields as $fieldName => $field) {
            $fieldString = $this->conn->quoteIdentifier($fieldName);

            if (is_array($field)) {
                if (isset($field['length'])) {
                    $fieldString .= '(' . $field['length'] . ')';
                }

                if (isset($field['sorting'])) {
                    $sort = strtoupper($field['sorting']);
                    switch ($sort) {
                        case 'ASC':
                        case 'DESC':
                            $fieldString .= ' ' . $sort;
                            break;
                        default:
                            throw new IPF_ORM_Exception('Unknown index sorting option given.');
                    }
                }
            } else {
                $fieldString = $this->conn->quoteIdentifier($field);
            }
            $declFields[] = $fieldString;
        }
        return implode(', ', $declFields);
    }

    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if ( ! empty($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if ( ! empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialAction($definition['onUpdate']);
        }
        if ( ! empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialAction($definition['onDelete']);
        }
        return $query;
    }

    public function dropIndexSql($table, $name)
    {
        $table  = $this->conn->quoteIdentifier($table);
        $name   = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name));
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }

    public function dropTableSql($table)
    {
        $table  = $this->conn->quoteIdentifier($table);
        return 'DROP TABLE ' . $table;
    }

    public function dropForeignKey($table, $name)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($name);

        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $name);
    }
}

