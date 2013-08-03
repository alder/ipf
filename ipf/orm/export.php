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

    protected function getIndexName($name)
    {
        return $name . '_idx';
    }

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
        $name = $this->conn->quoteIdentifier($this->getIndexName($name));
        
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

    public function createConstraint($table, $name, $definition)
    {
        $sql = $this->createConstraintSql($table, $name, $definition);
        
        return $this->conn->exec($sql);
    }

    public function createConstraintSql($table, $name, $definition)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($this->getIndexName($name));
        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $name;

        if (isset($definition['primary']) && $definition['primary']) {
            $query .= ' PRIMARY KEY';
        } elseif (isset($definition['unique']) && $definition['unique']) {
            $query .= ' UNIQUE';
        }

        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field);
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

             $sql = $this->exportClassesSql($class);

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

    public function exportClassesSql($name)
    {
        $sql = array();

        $table  = IPF_ORM::getTable($name);

        $data = $this->getExportableFormat($table);

        $query = $this->createTableSql($data['tableName'], $data['columns'], $data['options']);

        if (is_array($query)) {
            $sql = array_merge($sql, $query);
        } else {
            $sql[] = $query;
        }

        $sql = array_unique($sql);
        
        rsort($sql);

        return $sql;
    }

    private function getExportableFormat($table)
    {
        $columns = array();
        $primary = array();

        foreach ($table->getColumns() as $name => $definition) {

            if (isset($definition['owner'])) {
                continue;
            }

            switch ($definition['type']) {
                case 'enum':
                    if (isset($definition['default'])) {
                        $definition['default'] = $table->enumIndex($name, $definition['default']);
                    }
                    break;
                case 'boolean':
                    if (isset($definition['default'])) {
                        $definition['default'] = $table->getConnection()->convertBooleans($definition['default']);
                    }
                    break;
            }
            $columns[$name] = $definition;

            if (isset($definition['primary']) && $definition['primary']) {
                $primary[] = $name;
            }
        }

        $options['foreignKeys'] = isset($table->_options['foreignKeys']) ?
                $table->_options['foreignKeys'] : array();

        if ($table->getAttribute(IPF_ORM::ATTR_EXPORT) & IPF_ORM::EXPORT_CONSTRAINTS) {
            $constraints = array();

            $emptyIntegrity = array('onUpdate' => null,
                                    'onDelete' => null);

            foreach ($table->getRelations() as $name => $relation) {
                $fk = $relation->toArray();
                $fk['foreignTable'] = $relation->getTable()->getTableName();

                if ($relation->getTable() === $table && in_array($relation->getLocal(), $primary)) {
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
        
        return array('tableName' => $table->getOption('tableName'),
                     'columns'   => $columns,
                     'options'   => array_merge($table->getOptions(), $options));
    }
}

