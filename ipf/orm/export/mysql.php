<?php

class IPF_ORM_Export_Mysql extends IPF_ORM_Export
{
    public function createDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name, true);
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

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields . ')';

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

        $default   = $this->getDefaultFieldDeclaration($field);

        $charset   = (isset($field['charset']) && $field['charset']) ?
                    ' ' . $this->getCharsetFieldDeclaration($field['charset']) : '';

        $collation = (isset($field['collation']) && $field['collation']) ?
                    ' ' . $this->getCollationFieldDeclaration($field['collation']) : '';

        $notnull   = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

        $unique    = (isset($field['unique']) && $field['unique']) ?
                    ' ' . $this->getUniqueFieldDeclaration() : '';

        $check     = (isset($field['check']) && $field['check']) ?
                    ' ' . $field['check'] : '';

        $comment   = (isset($field['comment']) && $field['comment']) ?
                    " COMMENT '" . $field['comment'] . "'" : '';

        $method = 'get' . $field['type'] . 'Declaration';

        if (method_exists($this->conn->dataDict, $method)) {
            return $this->conn->dataDict->$method($name, $field);
        } else {
            $dec = $this->conn->dataDict->getNativeDeclaration($field);
        }
        return $this->conn->quoteIdentifier($name, true) . ' ' . $dec . $charset . $default . $notnull . $comment . $unique . $check . $collation;
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
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName, true);
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
                $renamedField = $this->conn->quoteIdentifier($renamedField, true);
                $query .= 'CHANGE ' . $renamedField . ' '
                        . $this->getDeclaration($field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);
        
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

    public function getDefaultFieldDeclaration($field)
    {
        $default = '';
        if (isset($field['default']) && ( ! isset($field['length']) || $field['length'] <= 255)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];

                if ($field['default'] === ''
                    && ($this->conn->getAttribute(IPF_ORM::ATTR_PORTABILITY) & IPF_ORM::PORTABILITY_EMPTY_TO_NULL)
                ) {
                    $field['default'] = ' ';
                }
            }
    
            // Proposed patch:
            if ($field['type'] == 'enum' && $this->conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                $fieldType = 'varchar';
            } else {
                $fieldType = $field['type'];
            }
            
            $default = ' DEFAULT ' . $this->conn->quote($field['default'], $fieldType);
            //$default = ' DEFAULT ' . $this->conn->quote($field['default'], $field['type']);
        }
        return $default;
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
        $table  = $this->conn->quoteIdentifier($table, true);
        $name   = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name), true);
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }

    public function dropTableSql($table)
    {
        $table  = $this->conn->quoteIdentifier($table, true);
        return 'DROP TABLE ' . $table;
    }

    public function dropForeignKey($table, $name)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($name);

        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $name);
    }
}
