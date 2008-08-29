<?php

class IPF_ORM_Import_Schema
{
    protected $_relations = array();

    protected $_options = array('packagesPrefix'        =>  'Package',
                                'packagesPath'          =>  '',
                                'packagesFolderName'    =>  'packages',
                                'suffix'                =>  '.php',
                                'generateBaseClasses'   =>  true,
                                'generateTableClasses'  =>  false,
                                'generateAccessors'     =>  false,
                                'baseClassesPrefix'     =>  'Base',
                                'baseClassesDirectory'  =>  '_generated',
                                'baseClassName'         =>  'IPF_ORM_Record');

    protected $_validation = array('root'       =>  array('abstract',
                                                          'connection',
                                                          'className',
                                                          'tableName',
                                                          'connection',
                                                          'relations',
                                                          'columns',
                                                          'indexes',
                                                          'attributes',
                                                          'templates',
                                                          'actAs',
                                                          'options',
                                                          'package',
                                                          'inheritance',
                                                          'detect_relations',
                                                          'generate_accessors',
                                                          'listeners'),

                                   'column'     =>  array('name',
                                                          'format',
                                                          'fixed',
                                                          'primary',
                                                          'autoincrement',
                                                          'type',
                                                          'length',
                                                          'size',
                                                          'default',
                                                          'scale',
                                                          'values',
                                                          'comment',
                                                          'sequence',
                                                          'protected',
                                                          'zerofill',
                                                          'owner'),

                                   'relation'   =>  array('key',
                                                          'class',
                                                          'alias',
                                                          'type',
                                                          'refClass',
                                                          'local',
                                                          'foreign',
                                                          'foreignClass',
                                                          'foreignAlias',
                                                          'foreignType',
                                                          'autoComplete',
                                                          'onDelete',
                                                          'onUpdate',
                                                          'equal',
                                                          'owningSide'),

                                   'inheritance'=>  array('type',
                                                          'extends',
                                                          'keyField',
                                                          'keyValue'));

    protected $_validators = array();

    public function getValidators()
    {
        if (empty($this->_validators)) {
            $this->_validators = IPF_ORM_Utils::getValidators();
        }
        return $this->_validators;
    }

    public function getOption($name)
    {
        if (isset($this->_options[$name]))   {
            return $this->_options[$name];
        }
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function setOption($name, $value)
    {
        if (isset($this->_options[$name])) {
            $this->_options[$name] = $value;
        }
    }
    
    public function setOptions($options)
    {
        if ( ! empty($options)) {
          $this->_options = $options;
        }
    }

    public function buildSchema($schema, $format)
    {
        $array = array();

        foreach ((array) $schema AS $s) {
            if (is_file($s)) {
                $array = array_merge($array, $this->parseSchema($s, $format));
            } else if (is_dir($s)) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($s),
                                                      RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());
                    if (end($e) === $format) {
                        $array = array_merge($array, $this->parseSchema($file->getPathName(), $format));
                    }
                }
            } else {
              $array = array_merge($array, $this->parseSchema($s, $format));
            }
        }

        $array = $this->_buildRelationships($array);
        $array = $this->_processInheritance($array);

        return $array;
    }

    public function importSchema($schema, $format = 'yml', $directory = null, $models = array())
    {
        $builder = new IPF_ORM_Import_Builder();
        $builder->setTargetPath($directory);
        $builder->setOptions($this->getOptions());
        
        $array = $this->buildSchema($schema, $format);

        foreach ($array as $name => $definition) {
            
            if ( ! empty($models) && !in_array($definition['className'], $models)) {
                continue;
            }
            print "    $name\n";
            $builder->buildRecord($definition);
        }
    }

    public function parseSchema($schema, $type)
    {
        $defaults = array('abstract'            =>  false,
                          'className'           =>  null,
                          'tableName'           =>  null,
                          'connection'          =>  null,
                          'relations'           =>  array(),
                          'indexes'             =>  array(),
                          'attributes'          =>  array(),
                          'templates'           =>  array(),
                          'actAs'               =>  array(),
                          'options'             =>  array(),
                          'package'             =>  null,
                          'inheritance'         =>  array(),
                          'detect_relations'    =>  false,
                          'generate_accessors'  =>  false);
        
        $array = IPF_ORM_Parser::load($schema, $type);

        // Go through the schema and look for global values so we can assign them to each table/class
        $globals = array();
        $globalKeys = array('connection',
                            'attributes',
                            'templates',
                            'actAs',
                            'options',
                            'package',
                            'inheritance',
                            'detect_relations',
                            'generate_accessors');

        // Loop over and build up all the global values and remove them from the array
        foreach ($array as $key => $value) {
            if (in_array($key, $globalKeys)) {
                unset($array[$key]);
                $globals[$key] = $value;
            }
        }

        // Apply the globals to each table if it does not have a custom value set already
        foreach ($array as $className => $table) {
            foreach ($globals as $key => $value) {
                if (!isset($array[$className][$key])) {
                    $array[$className][$key] = $value;
                }
            }
        }

        $build = array();

        foreach ($array as $className => $table) {
            $this->_validateSchemaElement('root', array_keys($table), $className);

            $columns = array();

            $className = isset($table['className']) ? (string) $table['className']:(string) $className;

            if (isset($table['inheritance']['keyField']) || isset($table['inheritance']['keyValue'])) {
                $table['inheritance']['type'] = 'column_aggregation';
            }

            if (isset($table['tableName']) && $table['tableName']) {
                $tableName = $table['tableName'];
            } else {
                if (isset($table['inheritance']['type']) && ($table['inheritance']['type'] == 'column_aggregation')) {
                    $tableName = null;
                } else {
                    $tableName = IPF_ORM_Inflector::tableize($className);
                }
            }

            $connection = isset($table['connection']) ? $table['connection']:'current';

            $columns = isset($table['columns']) ? $table['columns']:array();

            if ( ! empty($columns)) {
                foreach ($columns as $columnName => $field) {

                    // Support short syntax: my_column: integer(4)
                    if ( ! is_array($field)) {
                        $original = $field;
                        $field = array();
                        $field['type'] = $original;
                    }

                    $colDesc = array();
                    if (isset($field['name'])) {
                        $colDesc['name'] = $field['name'];
                    } else {
                        $colDesc['name'] = $columnName;
                    }

                    $this->_validateSchemaElement('column', array_keys($field), $className . '->columns->' . $colDesc['name']);

                    // Support short type(length) syntax: my_column: { type: integer(4) }
                    $e = explode('(', $field['type']);
                    if (isset($e[0]) && isset($e[1])) {
                        $colDesc['type'] = $e[0];
                        $colDesc['length'] = substr($e[1], 0, strlen($e[1]) - 1);
                    } else {
                        $colDesc['type'] = isset($field['type']) ? (string) $field['type']:null;
                        $colDesc['length'] = isset($field['length']) ? (int) $field['length']:null;
                        $colDesc['length'] = isset($field['size']) ? (int) $field['size']:$colDesc['length'];
                    }

                    $colDesc['fixed'] = isset($field['fixed']) ? (int) $field['fixed']:null;
                    $colDesc['primary'] = isset($field['primary']) ? (bool) (isset($field['primary']) && $field['primary']):null;
                    $colDesc['default'] = isset($field['default']) ? $field['default']:null;
                    $colDesc['autoincrement'] = isset($field['autoincrement']) ? (bool) (isset($field['autoincrement']) && $field['autoincrement']):null;
                    $colDesc['sequence'] = isset($field['sequence']) ? (string) $field['sequence']:null;
                    $colDesc['values'] = isset($field['values']) ? (array) $field['values']:null;

                    // Include all the specified and valid validators in the colDesc
                    $validators = $this->getValidators();

                    foreach ($validators as $validator) {
                        if (isset($field[$validator])) {
                            $colDesc[$validator] = $field[$validator];
                        }
                    }

                    $columns[(string) $columnName] = $colDesc;
                }
            }

            // Apply the default values
            foreach ($defaults as $key => $defaultValue) {
                if (isset($table[$key]) && ! isset($build[$className][$key])) {
                    $build[$className][$key] = $table[$key];
                } else {
                    $build[$className][$key] = isset($build[$className][$key]) ? $build[$className][$key]:$defaultValue;
                }
            }
            
            $build[$className]['className'] = $className;
            $build[$className]['tableName'] = $tableName;
            $build[$className]['columns'] = $columns;
            
            // Make sure that anything else that is specified in the schema makes it to the final array
            $build[$className] = IPF_ORM_Utils::arrayDeepMerge($table, $build[$className]);
            
            // We need to keep track of the className for the connection
            $build[$className]['connectionClassName'] = $build[$className]['className'];
        }

        return $build;
    }

    protected function _processInheritance($array)
    {
        // Apply default inheritance configuration
        foreach ($array as $className => $definition) {
            if ( ! empty($array[$className]['inheritance'])) {
                $this->_validateSchemaElement('inheritance', array_keys($definition['inheritance']), $className . '->inheritance');

                // Default inheritance to concrete inheritance
                if ( ! isset($array[$className]['inheritance']['type'])) {
                    $array[$className]['inheritance']['type'] = 'concrete';
                }

                // Some magic for setting up the keyField and keyValue column aggregation options
                // Adds keyField to the parent class automatically
                if ($array[$className]['inheritance']['type'] == 'column_aggregation') {
                    // Set the keyField to 'type' by default
                    if ( ! isset($array[$className]['inheritance']['keyField'])) {
                        $array[$className]['inheritance']['keyField'] = 'type';                        
                    }
                    
                    // Set the keyValue to the name of the child class if it does not exist
                    if ( ! isset($array[$className]['inheritance']['keyValue'])) {
                        $array[$className]['inheritance']['keyValue'] = $className;
                    }
                    
                    // Add the keyType column to the parent if a definition does not already exist
                    if ( ! isset($array[$array[$className]['inheritance']['extends']]['columns'][$array[$className]['inheritance']['keyField']])) {
                        $array[$definition['inheritance']['extends']]['columns'][$array[$className]['inheritance']['keyField']] = array('name' => $array[$className]['inheritance']['keyField'], 'type' => 'string', 'length' => 255);
                    }
                }
            }
        }

        // Array of the array keys to move to the parent, and the value to default the child definition to
        // after moving it. Will also populate the subclasses array for the inheritance parent
        $moves = array('columns' => array());
        
        foreach ($array as $className => $definition) {
            if (!isset($definition['className']))
                continue;
            $parent = $this->_findBaseSuperClass($array, $definition['className']);
            // Move any definitions on the schema to the parent
            if (isset($definition['inheritance']['extends']) && isset($definition['inheritance']['type']) && ($definition['inheritance']['type'] == 'simple' || $definition['inheritance']['type'] == 'column_aggregation')) {
                foreach ($moves as $move => $resetValue) {
                    $array[$parent][$move] = IPF_ORM_Utils::arrayDeepMerge($array[$parent][$move], $definition[$move]);
                    $array[$definition['className']][$move] = $resetValue;
                }

                // Populate the parents subclasses
                if ($definition['inheritance']['type'] == 'column_aggregation') {
                    $array[$parent]['inheritance']['subclasses'][$definition['className']] = array($definition['inheritance']['keyField'] => $definition['inheritance']['keyValue']);
                }
            }
        }

        return $array;
    }

    protected function _findBaseSuperClass($array, $class)
    {
        if (isset($array[$class]['inheritance']['extends'])) {
            return $this->_findBaseSuperClass($array, $array[$class]['inheritance']['extends']);
        } else {
            return $class;
        }
    }

    protected function _buildRelationships($array)
    {
        // Handle auto detecting relations by the names of columns
        // User.contact_id will automatically create User hasOne Contact local => contact_id, foreign => id
        foreach ($array as $className => $properties) {
            if (isset($properties['columns']) && ! empty($properties['columns']) && isset($properties['detect_relations']) && $properties['detect_relations']) {
                foreach ($properties['columns'] as $column) {
                    // Check if the column we are inflecting has a _id on the end of it before trying to inflect it and find
                    // the class name for the column
                    if (strpos($column['name'], '_id')) {
                        $columnClassName = IPF_ORM_Inflector::classify(str_replace('_id', '', $column['name']));
                        if (isset($array[$columnClassName]) && !isset($array[$className]['relations'][$columnClassName])) {
                            $array[$className]['relations'][$columnClassName] = array();

                            // Set the detected foreign key type and length to the same as the primary key
                            // of the related table
                            $type = isset($array[$columnClassName]['columns']['id']['type']) ? $array[$columnClassName]['columns']['id']['type']:'integer';
                            $length = isset($array[$columnClassName]['columns']['id']['length']) ? $array[$columnClassName]['columns']['id']['length']:8;
                            $array[$className]['columns'][$column['name']]['type'] = $type;
                            $array[$className]['columns'][$column['name']]['length'] = $length;
                        }
                    }
                }
            }
        }

        foreach ($array as $name => $properties) {
            if ( ! isset($properties['relations'])) {
                continue;
            }
            
            $className = $properties['className'];
            $relations = $properties['relations'];
            
            foreach ($relations as $alias => $relation) {
                $class = isset($relation['class']) ? $relation['class']:$alias;
                if (!isset($array[$class])) {
                    continue;
                }
                $relation['class'] = $class;
                $relation['alias'] = isset($relation['alias']) ? $relation['alias'] : $alias;
                
                // Attempt to guess the local and foreign
                if (isset($relation['refClass'])) {
                    $relation['local'] = isset($relation['local']) ? $relation['local']:IPF_ORM_Inflector::tableize($name) . '_id';
                    $relation['foreign'] = isset($relation['foreign']) ? $relation['foreign']:IPF_ORM_Inflector::tableize($class) . '_id';
                } else {
                    $relation['local'] = isset($relation['local']) ? $relation['local']:IPF_ORM_Inflector::tableize($relation['class']) . '_id';
                    $relation['foreign'] = isset($relation['foreign']) ? $relation['foreign']:'id';
                }
                
                if (isset($relation['refClass'])) {
                    $relation['type'] = 'many';
                }
                
                if (isset($relation['type']) && $relation['type']) {
                    $relation['type'] = $relation['type'] === 'one' ? IPF_ORM_Relation::ONE:IPF_ORM_Relation::MANY;
                } else {
                    $relation['type'] = IPF_ORM_Relation::ONE;
                }

                if (isset($relation['foreignType']) && $relation['foreignType']) {
                    $relation['foreignType'] = $relation['foreignType'] === 'one' ? IPF_ORM_Relation::ONE:IPF_ORM_Relation::MANY;
                }
                
                $relation['key'] = $this->_buildUniqueRelationKey($relation);
                
                $this->_validateSchemaElement('relation', array_keys($relation), $className . '->relation->' . $relation['alias']);
                
                $this->_relations[$className][$alias] = $relation;
            }
        }
        
        // Now we auto-complete opposite ends of relationships
        $this->_autoCompleteOppositeRelations();
        
        // Make sure we do not have any duplicate relations
        $this->_fixDuplicateRelations();

        //$array['relations'];
        // Set the full array of relationships for each class to the final array
        foreach ($this->_relations as $className => $relations) {
            $array[$className]['relations'] = $relations;
        }
        
        return $array;
    }

    protected function _autoCompleteOppositeRelations()
    {
        foreach($this->_relations as $className => $relations) {
            foreach ($relations AS $alias => $relation) {
                if ((isset($relation['equal']) && $relation['equal']) || (isset($relation['autoComplete']) && $relation['autoComplete'] === false)) {
                    continue;
                }
                
                $newRelation = array();
                $newRelation['foreign'] = $relation['local'];
                $newRelation['local'] = $relation['foreign'];
                $newRelation['class'] = isset($relation['foreignClass']) ? $relation['foreignClass']:$className;
                $newRelation['alias'] = isset($relation['foreignAlias']) ? $relation['foreignAlias']:$className;
                
                // this is so that we know that this relation was autogenerated and
                // that we do not need to include it if it is explicitly declared in the schema by the users.
                $newRelation['autogenerated'] = true; 
                
                if (isset($relation['refClass'])) {
                    $newRelation['refClass'] = $relation['refClass'];
                    $newRelation['type'] = isset($relation['foreignType']) ? $relation['foreignType']:$relation['type'];
                } else {                
                    if(isset($relation['foreignType'])) {
                        $newRelation['type'] = $relation['foreignType'];
                    } else {
                        $newRelation['type'] = $relation['type'] === IPF_ORM_Relation::ONE ? IPF_ORM_Relation::MANY:IPF_ORM_Relation::ONE;
                    }
                }

                // Make sure it doesn't already exist
                if ( ! isset($this->_relations[$relation['class']][$newRelation['alias']])) {
                    $newRelation['key'] = $this->_buildUniqueRelationKey($newRelation);
                    $this->_relations[$relation['class']][$newRelation['alias']] = $newRelation;
                }
            }
        }
    }

    protected function _fixDuplicateRelations()
    {
        foreach($this->_relations as $className => $relations) {
            // This is for checking for duplicates between alias-relations and a auto-generated relations to ensure the result set of unique relations
            $existingRelations = array();
            $uniqueRelations = array();
            foreach ($relations as $relation) {
                if ( ! in_array($relation['key'], $existingRelations)) {
                    $existingRelations[] = $relation['key'];
                    $uniqueRelations = array_merge($uniqueRelations, array($relation['alias'] => $relation));
                } else {
                    // check to see if this relationship is not autogenerated, if it's not, then the user must have explicitly declared it
                    if ( ! isset($relation['autogenerated']) || $relation['autogenerated'] != true) {
                        $uniqueRelations = array_merge($uniqueRelations, array($relation['alias'] => $relation));
                    }
                }
            }
            
            $this->_relations[$className] = $uniqueRelations;
        }
    }

    protected function _buildUniqueRelationKey($relation)
    {
        return md5($relation['local'].$relation['foreign'].$relation['class'].(isset($relation['refClass']) ? $relation['refClass']:null));
    }

    protected function _validateSchemaElement($name, $element, $path)
    {
        $element = (array) $element;

        $validation = $this->_validation[$name];

        // Validators are a part of the column validation
        // This should be fixed, made cleaner
        if ($name == 'column') {
            $validators = $this->getValidators();
            $validation = array_merge($validation, $validators);
        }

        $validation = array_flip($validation);
        foreach ($element as $key => $value) {
            if ( ! isset($validation[$value])) {
                throw new IPF_ORM_Exception(sprintf('Invalid schema element named "' . $value . '" 
                                at path "' . $path . '"'));
            }
        }
    }
}