<?php

class IPF_ORM_Relation_Parser
{
    protected $_table;
    protected $_relations = array();
    protected $_pending   = array();

    public function __construct(IPF_ORM_Table $table)
    {
        $this->_table = $table;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function getPendingRelation($name)
    {
        if ( ! isset($this->_pending[$name])) {
            throw new IPF_ORM_Exception('Unknown pending relation ' . $name);
        }

        return $this->_pending[$name];
    }

    public function getPendingRelations()
    {
        return $this->_pending;
    }

    public function unsetPendingRelations($name)
    {
       unset($this->_pending[$name]);
    }

    public function hasRelation($name)
    {
        if ( ! isset($this->_pending[$name]) && ! isset($this->_relations[$name])) {
            return false;
        }

        return true;
    }

    public function bind($name, $options = array())
    {
        $e    = explode(' as ', $name);
        $name = $e[0];
        $alias = isset($e[1]) ? $e[1] : $name;

        if ( ! isset($options['type'])) {
            throw new IPF_ORM_Exception('Relation type not set.');
        }

        if ($this->hasRelation($alias)) {
            unset($this->relations[$alias]);
            unset($this->_pending[$alias]);
        }

        $this->_pending[$alias] = array_merge($options, array('class' => $name, 'alias' => $alias));

        return $this->_pending[$alias];
    }

    public function getRelation($alias, $recursive = true)
    {
        if (isset($this->_relations[$alias])) {
            return $this->_relations[$alias];
        }

        if (isset($this->_pending[$alias])) {
            $def = $this->_pending[$alias];
            $identifierColumnNames = $this->_table->getIdentifierColumnNames();
            $idColumnName = array_pop($identifierColumnNames);

            // check if reference class name exists
            // if it does we are dealing with association relation
            if (isset($def['refClass'])) {
                $def = $this->completeAssocDefinition($def);
                $localClasses = array_merge($this->_table->getOption('parents'), array($this->_table->getComponentName()));

                if ( ! isset($this->_pending[$def['refClass']]) &&
                     ! isset($this->_relations[$def['refClass']])) {

                    $parser = $def['refTable']->getRelationParser();
                    if ( ! $parser->hasRelation($this->_table->getComponentName())) {
                        $parser->bind($this->_table->getComponentName(),
                                      array('type'    => IPF_ORM_Relation::ONE,
                                            'local'   => $def['local'],
                                            'foreign' => $idColumnName,
                                            'localKey' => true,
                                            ));
                    }

                    if ( ! $this->hasRelation($def['refClass'])) {
                        $this->bind($def['refClass'], array('type' => IPF_ORM_Relation::MANY,
                                                            'foreign' => $def['local'],
                                                            'local'   => $idColumnName));
                    }
                }
                if (in_array($def['class'], $localClasses)) {
                    $rel = new IPF_ORM_Relation_Nest($def);
                } else {
                    $rel = new IPF_ORM_Relation_Association($def);
                }
            } else {
                // simple foreign key relation
                $def = $this->completeDefinition($def);

                if (isset($def['localKey'])) {
                    $rel = new IPF_ORM_Relation_LocalKey($def);

                    // Automatically index foreign keys which are not primary
                    $foreign = (array) $def['foreign'];
                    foreach ($foreign as $fk) {
                        if ( ! $rel->getTable()->isIdentifier($fk)) {
                            $rel->getTable()->addIndex($fk, array('fields' => array($fk)));
                        }
                    }
                } else {
                    $rel = new IPF_ORM_Relation_ForeignKey($def);
                }
            }
            if (isset($rel)) {
                // unset pending relation
                unset($this->_pending[$alias]);

                $this->_relations[$alias] = $rel;
                return $rel;
            }
        }
        if ($recursive) {
            $this->getRelations();
            return $this->getRelation($alias, false);
        } else {
            throw new IPF_ORM_Exception('Unknown relation alias "' . $alias . '".');
        }
    }

    public function getRelations()
    {
        foreach ($this->_pending as $k => $v) {
            $this->getRelation($k);
        }

        return $this->_relations;
    }

    public function getImpl($template)
    {
        $conn = $this->_table->getConnection();

        if (in_array('IPF_ORM_Template', class_parents($template))) {
            $impl = $this->_table->getImpl($template);

            if ($impl === null) {
                throw new IPF_ORM_Exception("Couldn't find concrete implementation for template " . $template);
            }
        } else {
            $impl = $template;
        }

        return $conn->getTable($impl);
    }

    public function completeAssocDefinition($def)
    {
        $conn = $this->_table->getConnection();
        $def['table'] = $this->getImpl($def['class']);
        $def['localTable'] = $this->_table;
        $def['class'] = $def['table']->getComponentName();
        $def['refTable'] = $this->getImpl($def['refClass']);

        $id = $def['refTable']->getIdentifierColumnNames();

        if (count($id) > 1) {
            if ( ! isset($def['foreign'])) {
                // foreign key not set
                // try to guess the foreign key

                $def['foreign'] = ($def['local'] === $id[0]) ? $id[1] : $id[0];
            }
            if ( ! isset($def['local'])) {
                // foreign key not set
                // try to guess the foreign key

                $def['local'] = ($def['foreign'] === $id[0]) ? $id[1] : $id[0];
            }
        } else {

            if ( ! isset($def['foreign'])) {
                // foreign key not set
                // try to guess the foreign key

                $columns = $this->getIdentifiers($def['table']);

                $def['foreign'] = $columns;
            }
            if ( ! isset($def['local'])) {
                // local key not set
                // try to guess the local key
                $columns = $this->getIdentifiers($this->_table);

                $def['local'] = $columns;
            }
        }
        return $def;
    }

    public function getIdentifiers(IPF_ORM_Table $table)
    {
        $componentNameToLower = strtolower($table->getComponentName());
        if (is_array($table->getIdentifier())) {
            $columns = array();
            foreach ((array) $table->getIdentifierColumnNames() as $identColName) {
                $columns[] = $componentNameToLower . '_' . $identColName;
            }
        } else {
            $columns = $componentNameToLower . '_' . $table->getColumnName(
                    $table->getIdentifier());
        }

        return $columns;
    }

    public function guessColumns(array $classes, IPF_ORM_Table $foreignTable)
    {
        $conn = $this->_table->getConnection();

        foreach ($classes as $class) {
            try {
                $table   = $conn->getTable($class);
            } catch (IPF_ORM_Exception $e) {
                continue;
            }
            $columns = $this->getIdentifiers($table);
            $found   = true;

            foreach ((array) $columns as $column) {
                if ( ! $foreignTable->hasColumn($column)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                break;
            }
        }

        if ( ! $found) {
            throw new IPF_ORM_Exception("Couldn't find columns.");
        }

        return $columns;
    }

    public function completeDefinition($def)
    {
        $conn = $this->_table->getConnection();
        $def['table'] = $this->getImpl($def['class']);
        $def['localTable'] = $this->_table;
        $def['class'] = $def['table']->getComponentName();

        $foreignClasses = array_merge($def['table']->getOption('parents'), array($def['class']));
        $localClasses   = array_merge($this->_table->getOption('parents'), array($this->_table->getComponentName()));

        $localIdentifierColumnNames = $this->_table->getIdentifierColumnNames();
        $localIdentifierCount = count($localIdentifierColumnNames);
        $localIdColumnName = array_pop($localIdentifierColumnNames);
        $foreignIdentifierColumnNames = $def['table']->getIdentifierColumnNames();
        $foreignIdColumnName = array_pop($foreignIdentifierColumnNames);

        if (isset($def['local'])) {
            if ( ! isset($def['foreign'])) {
                // local key is set, but foreign key is not
                // try to guess the foreign key

                if ($def['local'] === $localIdColumnName) {
                    $def['foreign'] = $this->guessColumns($localClasses, $def['table']);
                } else {
                    // the foreign field is likely to be the
                    // identifier of the foreign class
                    $def['foreign'] = $foreignIdColumnName;
                    $def['localKey'] = true;
                }
            } else {
                if ($localIdentifierCount == 1) {
                    if ($def['local'] == $localIdColumnName && isset($def['owningSide'])
                            && $def['owningSide'] === true) {
                        $def['localKey'] = true;
                    } else if (($def['local'] !== $localIdColumnName && $def['type'] == IPF_ORM_Relation::ONE)) {
                        $def['localKey'] = true;
                    }
                } else if ($localIdentifierCount > 1) {
                    // It's a composite key and since 'foreign' can not point to a composite
                    // key currently, we know that 'local' must be the foreign key.
                    $def['localKey'] = true;
                }
            }
        } else {
            if (isset($def['foreign'])) {
                // local key not set, but foreign key is set
                // try to guess the local key
                if ($def['foreign'] === $foreignIdColumnName) {
                    $def['localKey'] = true;
                    try {
                        $def['local'] = $this->guessColumns($foreignClasses, $this->_table);
                    } catch (IPF_ORM_Exception $e) {
                        $def['local'] = $localIdColumnName;
                    }
                } else {
                    $def['local'] = $localIdColumnName;
                }
            } else {
                // neither local or foreign key is being set
                // try to guess both keys

                $conn = $this->_table->getConnection();

                // the following loops are needed for covering inheritance
                foreach ($localClasses as $class) {
                    $table = $conn->getTable($class);
                    $identifierColumnNames = $table->getIdentifierColumnNames();
                    $idColumnName = array_pop($identifierColumnNames);
                    $column = strtolower($table->getComponentName())
                            . '_' . $idColumnName;

                    foreach ($foreignClasses as $class2) {
                        $table2 = $conn->getTable($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign'] = $column;
                            $def['local'] = $idColumnName;
                            return $def;
                        }
                    }
                }

                foreach ($foreignClasses as $class) {
                    $table  = $conn->getTable($class);
                    $identifierColumnNames = $table->getIdentifierColumnNames();
                    $idColumnName = array_pop($identifierColumnNames);
                    $column = strtolower($table->getComponentName())
                            . '_' . $idColumnName;

                    foreach ($localClasses as $class2) {
                        $table2 = $conn->getTable($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign']  = $idColumnName;
                            $def['local']    = $column;
                            $def['localKey'] = true;
                            return $def;
                        }
                    }
                }

                // auto-add columns and auto-build relation
                $columns = array();
                foreach ((array) $this->_table->getIdentifierColumnNames() as $id) {
                    // ?? should this not be $this->_table->getComponentName() ??
                    $column = strtolower($table->getComponentName())
                            . '_' . $id;

                    $col = $this->_table->getColumnDefinition($id);
                    $type = $col['type'];
                    $length = $col['length'];

                    unset($col['type']);
                    unset($col['length']);
                    unset($col['autoincrement']);
                    unset($col['primary']);

                    $def['table']->setColumn($column, $type, $length, $col);

                    $columns[] = $column;
                }
                if (count($columns) > 1) {
                    $def['foreign'] = $columns;
                } else {
                    $def['foreign'] = $columns[0];
                }
                $def['local'] = $localIdColumnName;
            }
        }
        return $def;
    }
}
