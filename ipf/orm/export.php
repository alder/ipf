<?php

class IPF_ORM_Export extends IPF_ORM_Connection_Module
{
    protected $valid_default_values = array(
        'text'      => '',
        'boolean'   => true,
        'integer'   => 0,
        'decimal'   => 0.0,
        'float'     => 0.0,
        'double'    => 0.0,
        'timestamp' => '1970-01-01 00:00:00',
        'time'      => '00:00:00',
        'date'      => '1970-01-01',
        'clob'      => '',
        'blob'      => '',
        'string'    => ''
    );

    public function dropDatabase($database)
    {
        $this->conn->execute($this->dropDatabaseSql($database));
    }

    public function dropDatabaseSql($database)
    {
        throw new IPF_ORM_Exception('Drop database not supported by this driver.');
    }

    public function dropTableSql($table)
    {
        return 'DROP TABLE ' . $this->conn->quoteIdentifier($table);
    }

    public function dropTable($table)
    {
        $this->conn->execute($this->dropTableSql($table));
    }

    public function dropIndex($table, $name)
    {
        return $this->conn->exec($this->dropIndexSql($table, $name));
    }

    public function dropIndexSql($table, $name)
    {
        $name = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name));
        
        return 'DROP INDEX ' . $name;
    }

    public function dropConstraint($table, $name, $primary = false)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($name);
        
        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name);
    }

    public function dropForeignKey($table, $name)
    {
        return $this->dropConstraint($table, $name);
    }

    public function createDatabase($database)
    {
        $this->conn->execute($this->createDatabaseSql($database));
    }

    public function createDatabaseSql($database)
    {
        throw new IPF_ORM_Exception('Create database not supported by this driver.');
    }

    public function createTableSql($name, array $fields, array $options = array())
    {
        if ( ! $name) {
            throw new IPF_ORM_Exception('no valid table name specified');
        }

        if (empty($fields)) {
            throw new IPF_ORM_Exception('no fields specified for table ' . $name);
        }

        $queryFields = $this->getFieldDeclarationList($fields);


        if (isset($options['primary']) && ! empty($options['primary'])) {
            $queryFields .= ', PRIMARY KEY(' . implode(', ', array_values($options['primary'])) . ')';
        }

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclaration($index, $definition);
            }
        }

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields;
        
        $check = $this->getCheckDeclaration($fields);

        if ( ! empty($check)) {
            $query .= ', ' . $check;
        }

        $query .= ')';

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

    public function createConstraint($table, $name, $definition)
    {
        $sql = $this->createConstraintSql($table, $name, $definition);
        
        return $this->conn->exec($sql);
    }

    public function createConstraintSql($table, $name, $definition)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name));
        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $name;

        if (isset($definition['primary']) && $definition['primary']) {
            $query .= ' PRIMARY KEY';
        } elseif (isset($definition['unique']) && $definition['unique']) {
            $query .= ' UNIQUE';
        }

        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';

        return $query;
    }

    public function createIndex($table, $name, array $definition)
    {
        return $this->conn->execute($this->createIndexSql($table, $name, $definition));
    }

    public function createIndexSql($table, $name, array $definition)
    {
        $table  = $this->conn->quoteIdentifier($table);
        $name   = $this->conn->quoteIdentifier($name);
        $type   = '';

        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw new IPF_ORM_Exception('Unknown index type ' . $definition['type']);
            }
        }

        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;

        $fields = array();
        foreach ($definition['fields'] as $field) {
            $fields[] = $this->conn->quoteIdentifier($field);
        }
        $query .= ' (' . implode(', ', $fields) . ')';

        return $query;
    }    

    public function createForeignKeySql($table, array $definition)
    {
        $table = $this->conn->quoteIdentifier($table);

        $query = 'ALTER TABLE ' . $table . ' ADD ' . $this->getForeignKeyDeclaration($definition);

        return $query;
    }

    public function createForeignKey($table, array $definition)
    {
        $sql = $this->createForeignKeySql($table, $definition);
        
        return $this->conn->execute($sql);
    }

    public function alterTable($name, array $changes, $check = false)
    {
        $sql = $this->alterTableSql($name, $changes, $check);
        
        if (is_string($sql) && $sql) {
            $this->conn->execute($sql);
        }
    }

    public function alterTableSql($name, array $changes, $check = false)
    {
        throw new IPF_ORM_Exception('Alter table not supported by this driver.');
    }

    public function getFieldDeclarationList(array $fields)
    {
        foreach ($fields as $fieldName => $field) {
            $query = $this->getDeclaration($fieldName, $field);

            $queryFields[] = $query;
        }
        return implode(', ', $queryFields);
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

        $method = 'get' . $field['type'] . 'Declaration';

        if (method_exists($this->conn->dataDict, $method)) {
            return $this->conn->dataDict->$method($name, $field);
        } else {
            $dec = $this->conn->dataDict->getNativeDeclaration($field);
        }
        return $this->conn->quoteIdentifier($name, true) . ' ' . $dec . $charset . $default . $notnull . $unique . $check . $collation;
    }

    public function getDefaultFieldDeclaration($field)
    {
        $default = '';
        if (isset($field['default'])) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];

                if ($field['default'] === '' &&
                   ($this->conn->getAttribute(IPF_ORM::ATTR_PORTABILITY) & IPF_ORM::PORTABILITY_EMPTY_TO_NULL)) {
                    $field['default'] = null;
                }
            }

            if ($field['type'] === 'boolean') {
                $field['default'] = $this->conn->convertBooleans($field['default']);
            }
            $default = ' DEFAULT ' . $this->conn->quote($field['default'], $field['type']);
        }
        return $default;
    }

    public function getCheckDeclaration(array $definition)
    {
        $constraints = array();
        foreach ($definition as $field => $def) {
            if (is_string($def)) {
                $constraints[] = 'CHECK (' . $def . ')';
            } else {
                if (isset($def['min'])) {
                    $constraints[] = 'CHECK (' . $field . ' >= ' . $def['min'] . ')';
                }

                if (isset($def['max'])) {
                    $constraints[] = 'CHECK (' . $field . ' <= ' . $def['max'] . ')';
                }
            }
        }

        return implode(', ', $constraints);
    }

    public function getIndexDeclaration($name, array $definition)
    {
        $name   = $this->conn->quoteIdentifier($name);
        $type   = '';

        if (isset($definition['type'])) {
            if (strtolower($definition['type']) == 'unique') {
                $type = strtoupper($definition['type']) . ' ';
            } else {
                throw new IPF_ORM_Exception('Unknown index type ' . $definition['type']);
            }
        }

        if ( ! isset($definition['fields']) || ! is_array($definition['fields'])) {
            throw new IPF_ORM_Exception('No index columns given.');
        }

        $query = $type . 'INDEX ' . $name;

        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    public function getIndexFieldDeclarationList(array $fields)
    {
        $ret = array();
        foreach ($fields as $field => $definition) {
            if (is_array($definition)) {
                $ret[] = $this->conn->quoteIdentifier($field);
            } else {
                $ret[] = $this->conn->quoteIdentifier($definition);
            }
        }
        return implode(', ', $ret);
    }

    public function getTemporaryTableQuery()
    {
        return 'TEMPORARY';
    }

    public function getForeignKeyDeclaration(array $definition)
    {
        $sql  = $this->getForeignKeyBaseDeclaration($definition);
        $sql .= $this->getAdvancedForeignKeyOptions($definition);
        return $sql;
    }

    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if ( ! empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialAction($definition['onUpdate']);
        }
        if ( ! empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialAction($definition['onDelete']);
        }
        return $query;
    }

    public function getForeignKeyReferentialAction($action)
    {
        $upper = strtoupper($action);
        switch ($upper) {
            case 'CASCADE':
            case 'SET NULL':
            case 'NO ACTION':
            case 'RESTRICT':
            case 'SET DEFAULT':
                return $upper;
            break;
            default:
                throw new IPF_ORM_Exception('Unknown foreign key referential action \'' . $upper . '\' given.');
        }
    }

    public function getForeignKeyBaseDeclaration(array $definition)
    {
        $sql = '';
        if (isset($definition['name'])) {
            $sql .= ' CONSTRAINT ' . $this->conn->quoteIdentifier($definition['name']) . ' ';
        }
        $sql .= 'FOREIGN KEY (';

        if ( ! isset($definition['local'])) {
            throw new IPF_ORM_Exception('Local reference field missing from definition.');
        }
        if ( ! isset($definition['foreign'])) {
            throw new IPF_ORM_Exception('Foreign reference field missing from definition.');
        }
        if ( ! isset($definition['foreignTable'])) {
            throw new IPF_ORM_Exception('Foreign reference table missing from definition.');
        }

        if ( ! is_array($definition['local'])) {
            $definition['local'] = array($definition['local']);
        }
        if ( ! is_array($definition['foreign'])) {
            $definition['foreign'] = array($definition['foreign']);
        }

        $sql .= implode(', ', array_map(array($this->conn, 'quoteIdentifier'), $definition['local']))
              . ') REFERENCES '
              . $this->conn->quoteIdentifier($definition['foreignTable']) . '('
              . implode(', ', array_map(array($this->conn, 'quoteIdentifier'), $definition['foreign'])) . ')';

        return $sql;
    }

    public function getUniqueFieldDeclaration()
    {
        return 'UNIQUE';
    }

    public function getCharsetFieldDeclaration($charset)
    {
        return '';
    }

    public function getCollationFieldDeclaration($collation)
    {
        return '';
    }

    public function exportSortedClassesSql($classes, $groupByConnection = true)
    {
         $connections = array();
         foreach ($classes as $class) {
             $connection = IPF_ORM_Manager::getInstance()->getConnectionForComponent($class);
             $connectionName = $connection->getName();

             if ( ! isset($connections[$connectionName])) {
                 $connections[$connectionName] = array(
                     'create_tables'    => array(),
                     'create_indexes'   => array(),
                     'alters'           => array()
                 );
             }

             $sql = $connection->export->exportClassesSql(array($class));

             // Build array of all the creates
             // We need these to happen first
             foreach ($sql as $key => $query) {
                 // If create table statement
                 if (substr($query, 0, strlen('CREATE TABLE')) == 'CREATE TABLE') {
                     $connections[$connectionName]['create_tables'][] = $query;

                     unset($sql[$key]);
                     continue;
                 }

                 // If create index statement
                 if (preg_grep("/CREATE .* INDEX/", array($query))) {
                     $connections[$connectionName]['create_indexes'][] =  $query;

                     unset($sql[$key]);
                     continue;
                 }

                 // If alter table statement
                 if (substr($query, 0, strlen('ALTER TABLE')) == 'ALTER TABLE') {
                     $connections[$connectionName]['alters'][] = $query;

                     unset($sql[$key]);
                     continue;
                 }
             }
         }

         // Loop over all the sql again to merge everything together so it is in the correct order
         $build = array();
         foreach ($connections as $connectionName => $sql) {
             $build[$connectionName] = array_merge($sql['create_tables'], $sql['create_indexes'], $sql['alters']);
         }

         if ( ! $groupByConnection) {
             $new = array();
             foreach($build as $connectionname => $sql) {
                 $new = array_merge($new, $sql);
             }
             $build = $new;
         }
         return $build;
    }

     public function exportClasses(array $classes)
     {
         $queries = $this->exportSortedClassesSql($classes);
         
         foreach ($queries as $connectionName => $sql) {
             $connection = IPF_ORM_Manager::getInstance()->getConnection($connectionName);

             $connection->beginTransaction();

             foreach ($sql as $query) {
                 try {
                     $connection->exec($query);
                 } catch (IPF_ORM_Exception $e) {
                     // we only want to silence table already exists errors
                     if ($e->getPortableCode() !== IPF_ORM::ERR_ALREADY_EXISTS) {
                         $connection->rollback();
                         throw new IPF_ORM_Exception($e->getMessage() . '. Failing Query: ' . $query);
                     }
                 }
             }
             $connection->commit();
         }
     }

    public function exportClassesSql(array $models)
    {
        $sql = array();
        
        foreach ($models as $name) {
            $record = new $name();
            $table  = $record->getTable();

            $data = $table->getExportableFormat();

            $query = $this->conn->export->createTableSql($data['tableName'], $data['columns'], $data['options']);

            if (is_array($query)) {
                $sql = array_merge($sql, $query);
            } else {
                $sql[] = $query;
            }
            
            if ($table->getAttribute(IPF_ORM::ATTR_EXPORT) & IPF_ORM::EXPORT_PLUGINS) {
                $sql = array_merge($sql, $this->exportGeneratorsSql($table));
            }
        }
        
        $sql = array_unique($sql);
        
        rsort($sql);

        return $sql;
    }

    public function getAllGenerators(IPF_ORM_Table $table)
    {
        $generators = array();

        foreach ($table->getGenerators() as $name => $generator) {
            if ($generator === null) {
                continue;                       
            }

            $generators[] = $generator;

            $generatorTable = $generator->getTable();
            
            if ($generatorTable instanceof IPF_ORM_Table) {
                $generators = array_merge($generators, $this->getAllGenerators($generatorTable));
            }
        }

        return $generators;
    }

    public function exportGeneratorsSql(IPF_ORM_Table $table)
    {
        $sql = array();

        foreach ($this->getAllGenerators($table) as $name => $generator) {
            $table = $generator->getTable();
            
            // Make sure plugin has a valid table
            if ($table instanceof IPF_ORM_Table) {
                $data = $table->getExportableFormat();

                $query = $this->conn->export->createTableSql($data['tableName'], $data['columns'], $data['options']);

                $sql = array_merge($sql, (array) $query);
            }
        }

        return $sql;
    }
}

