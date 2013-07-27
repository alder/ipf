<?php

class IPF_ORM_Import_Builder
{
    private $_baseClassPrefix = 'Base';
    private $_baseClassesDirectory = IPF_ORM::BASE_CLASSES_DIRECTORY;

    private static function varExport($var)
    {
        $export = var_export($var, true);
        $export = preg_replace('#\s*\(\s*#', '(', $export);
        $export = preg_replace('#[\s,]+\)#', ')', $export);
        $export = preg_replace('#\s+#', ' ', $export);
        return $export;
    }

    private function buildTableDefinition(array $definition)
    {
        if (isset($definition['inheritance']['type']) && ($definition['inheritance']['type'] == 'simple' || $definition['inheritance']['type'] == 'column_aggregation')) {
            return;
        }

        $ret = array(
            '  public function setTableDefinition()',
            '  {',
        );

        if (isset($definition['inheritance']['type']) && $definition['inheritance']['type'] == 'concrete')
            $ret[] = "    parent::setTableDefinition();";

        if (isset($definition['tableName']) && !empty($definition['tableName']))
            $ret[] = "    ".'$this->setTableName(\''. $definition['tableName'].'\');';

        if (isset($definition['columns']) && is_array($definition['columns']) && !empty($definition['columns']))
            $ret[] = $this->buildColumns($definition['columns']);

        if (isset($definition['indexes']) && is_array($definition['indexes']) && !empty($definition['indexes']))
            foreach ($definition['indexes'] as $indexName => $definitions)
                $ret[] = "    \$this->index('" . $indexName . "', " . self::varExport($definitions) . ');';

        if (isset($definition['attributes']) && is_array($definition['attributes']) && !empty($definition['attributes']))
            $ret[] = $this->buildAttributes($definition['attributes']);

        if (isset($definition['options']) && is_array($definition['options']) && !empty($definition['options']))
            $ret[] = $this->buildOptions($definition['options']);

        if (isset($definition['inheritance']['subclasses']) && ! empty($definition['inheritance']['subclasses']))
            $ret[] = "    ".'$this->setSubClasses('. self::varExport($definition['inheritance']['subclasses']).');';

        $ret[] = '  }';

        return implode(PHP_EOL, $ret);
    }

    private function buildSetUp(array $definition)
    {
        $ret = array();
        $i = 0;

        if (isset($definition['relations']) && is_array($definition['relations']) && ! empty($definition['relations'])) {
            foreach ($definition['relations'] as $name => $relation) {
                $class = isset($relation['class']) ? $relation['class']:$name;
                $alias = (isset($relation['alias']) && $relation['alias'] !== $relation['class']) ? ' as ' . $relation['alias'] : '';

                if ( ! isset($relation['type'])) {
                    $relation['type'] = IPF_ORM_Relation::ONE;
                }

                if ($relation['type'] === IPF_ORM_Relation::ONE ||
                    $relation['type'] === IPF_ORM_Relation::ONE_COMPOSITE) {
                    $ret[$i] = "    ".'$this->hasOne(\'' . $class . $alias . '\'';
                } else {
                    $ret[$i] = "    ".'$this->hasMany(\'' . $class . $alias . '\'';
                }

                $a = array();

                if (isset($relation['refClass'])) {
                    $a[] = '\'refClass\' => ' . self::varExport($relation['refClass']);
                }

                if (isset($relation['deferred']) && $relation['deferred']) {
                    $a[] = '\'default\' => ' . self::varExport($relation['deferred']);
                }

                if (isset($relation['local']) && $relation['local']) {
                    $a[] = '\'local\' => ' . self::varExport($relation['local']);
                }

                if (isset($relation['foreign']) && $relation['foreign']) {
                    $a[] = '\'foreign\' => ' . self::varExport($relation['foreign']);
                }

                if (isset($relation['onDelete']) && $relation['onDelete']) {
                    $a[] = '\'onDelete\' => ' . self::varExport($relation['onDelete']);
                }

                if (isset($relation['onUpdate']) && $relation['onUpdate']) {
                    $a[] = '\'onUpdate\' => ' . self::varExport($relation['onUpdate']);
                }

                if (isset($relation['equal']) && $relation['equal']) {
                    $a[] = '\'equal\' => ' . self::varExport($relation['equal']);
                }

                if (isset($relation['owningSide']) && $relation['owningSide']) {
                    $a[] = '\'owningSide\' => ' . self::varExport($relation['owningSide']);
                }

                if (isset($relation['exclude']) && $relation['exclude']) {
                    $a[] = '\'exclude\' => ' . self::varExport($relation['exclude']);
                }

                if ( ! empty($a)) {
                    $ret[$i] .= ', ' . 'array(';
                    $length = strlen($ret[$i]);
                    $ret[$i] .= implode(',' . PHP_EOL . str_repeat(' ', $length), $a) . ')';
                }

                $ret[$i] .= ');'.PHP_EOL;
                $i++;
            }
        }

        if (isset($definition['templates']) && is_array($definition['templates']) && !empty($definition['templates'])) {
            $ret[$i] = $this->buildTemplates($definition['templates']);
            $i++;
        }

        if (isset($definition['actAs']) && is_array($definition['actAs']) && !empty($definition['actAs'])) {
            $ret[$i] = $this->buildActAs($definition['actAs']);
            $i++;
        }

        if (isset($definition['listeners']) && is_array($definition['listeners']) && !empty($definition['listeners'])) {
            foreach($definition['listeners'] as $listener) {
                $ret[$i] = PHP_EOL.'    $this->getTable()->listeners[\''.$listener.'\'] = new '.$listener.'();';
                $i++;
            }
        }

        $code = implode(PHP_EOL, $ret);
        $code = trim($code);

        // If the body of the function has contents then we need to 
        if ($code) {
            // If the body of the function has contents and we are using inheritance
            // then we need call the parent::setUp() before the body of the function
            // Class table inheritance is the only one we shouldn't call parent::setUp() for
            if ($code && isset($definition['inheritance']['type']) && $definition['inheritance']['type'] != 'class_table') {
                $code = "parent::setUp();" . PHP_EOL . '    ' . $code;
            }
        }

        // If we have some code for the function then lets define it and return it
        if ($code) {
            return '  public function setUp()' . PHP_EOL . '  {' . PHP_EOL . '    ' . $code . PHP_EOL . '  }';
        }
    }

    private function buildColumns(array $columns)
    {
        $result = array();
        foreach ($columns as $name => $column) {
            $columnName = isset($column['name']) ? $column['name']:$name;
            $build = "    ".'$this->getTable()->setColumn(\'' . $columnName . '\', \'' . $column['type'] . '\'';

            if ($column['length']) {
                if (is_numeric($column['length']))
                    $build .= ', ' . $column['length'];
                else
                    $build .= ', array(' . $column['length'] . ')';
            } else {
                $build .= ', null';
            }

            $options = $column;

            // Remove name, alltypes, ntype. They are not needed in options array
            unset($options['name']);
            unset($options['alltypes']);
            unset($options['ntype']);

            // Remove notnull => true if the column is primary
            // Primary columns are implied to be notnull in IPF_ORM
            if (isset($options['primary']) && $options['primary'] == true && (isset($options['notnull']) && $options['notnull'] == true)) {
                unset($options['notnull']);
            }

            // Remove default if the value is 0 and the column is a primary key
            // IPF_ORM defaults to 0 if it is a primary key
            if (isset($options['primary']) && $options['primary'] == true && (isset($options['default']) && $options['default'] == 0)) {
                unset($options['default']);
            }

            // These can be removed if they are empty. They all default to a false/0/null value anyways
            $remove = array('fixed', 'primary', 'notnull', 'autoincrement', 'unsigned');
            foreach ($remove as $key) {
                if (isset($options[$key]) && empty($options[$key])) {
                    unset($options[$key]);
                }
            }

            // Remove null and empty array values
            foreach ($options as $key => $value) {
                if (is_null($value) || (is_array($value) && empty($value))) {
                    unset($options[$key]);
                }
            }

            if (is_array($options) && !empty($options)) {
                $build .= ', ' . self::varExport($options);
            }

            $build .= ');';

            $result[] = $build;
        }

        return implode(PHP_EOL, $result);
    }

    private function buildTemplates(array $templates)
    {
        $build = '';
        foreach ($templates as $name => $options) {

            if (is_array($options) && !empty($options)) {
                $optionsPhp = self::varExport($options);

                $build .= "    \$this->getTable()->addTemplate('" . $name . "', " . $optionsPhp . ");" . PHP_EOL;
            } else {
                if (isset($templates[0])) {
                    $build .= "    \$this->getTable()->addTemplate('" . $options . "');" . PHP_EOL;
                } else {
                    $build .= "    \$this->getTable()->addTemplate('" . $name . "');" . PHP_EOL;
                }
            }
        }

        return $build;
    }

    private function buildActAs($actAs)
    {
        // rewrite special case of actAs: [Behavior] which gave [0] => Behavior
        if (is_array($actAs) && isset($actAs[0]) && !is_array($actAs[0])) {
            $actAs = array_flip($actAs);
        }

        // rewrite special case of actAs: Behavior
        if (!is_array($actAs))
            $actAs = array($actAs => '');

        $build = '';
        foreach($actAs as $template => $options) {
            // find class matching $name
            if (class_exists("IPF_ORM_Template_$template", true)) {
                $classname = "IPF_ORM_Template_$template";
            } else {
                $classname = $template;
            }

            if (is_array($options))
                $options = self::varExport($options);
            else
                $options = '';

            $build .= "    \$this->getTable()->addTemplate(new $classname($options));" . PHP_EOL;
        }

        return $build;
    }

    private function buildAttributes(array $attributes)
    {
        $build = PHP_EOL;
        foreach ($attributes as $key => $value) {

            if (is_bool($value)) {
                $values = $value ? 'true':'false';
            } else {
                if (!is_array($value))
                    $value = array($value);

                $values = '';
                foreach ($value as $attr) {
                    $values .= "IPF_ORM::" . strtoupper($key) . "_" . strtoupper($attr) . ' ^ ';
                }

                // Trim last ^
                $values = substr($values, 0, strlen($values) - 3);
            }

            $build .= "    \$this->setAttribute(IPF_ORM::ATTR_" . strtoupper($key) . ", " . $values . ");" . PHP_EOL;
        }

        return $build;
    }

    private function buildOptions(array $options)
    {
        $build = '';
        foreach ($options as $name => $value) {
            $build .= "    \$this->option('$name', " . self::varExport($value) . ");" . PHP_EOL;
        }

        return $build;
    }

    public function buildRecord(array $definition, $targetPath)
    {
        if (!isset($definition['className']))
            throw new IPF_ORM_Exception('Missing class name.');

        $this->writeBaseDefinition($definition, $targetPath);
        $this->writeModelDefinition($definition, $targetPath);
    }

    private function writeBaseDefinition(array $definition, $targetPath)
    {
        $code = array(
            '<?php',
            '',
            '/**',
            ' * This class has been auto-generated by the IPF_ORM Framework.',
            ' * Changes to this file may cause incorrect behavior',
            ' * and will be lost if the code is regenerated.',
            ' */',
            '',
        );

        if (isset($definition['connection']) && $definition['connection']) {
            $code[] = '// Connection Component Binding';
            $code[] = "IPF_ORM_Manager::getInstance()->bindComponent('" . $definition['connectionClassName'] . "', '" . $definition['connection'] . "');";
            $code[] = '';
        }

        $code[] = 'abstract class '.$this->_baseClassPrefix.$definition['className'].' extends IPF_ORM_Record';
        $code[] = '{';
        $code[] = $this->buildTableDefinition($definition);
        $code[] = '';
        $code[] = $this->buildSetUp($definition);
        $code[] = '';
        $code   = array_merge($code, $this->buildShortcuts($definition));
        $code[] = '}';

        $fileName = $this->_baseClassPrefix . $definition['className'] . '.php';
        $writePath = $targetPath . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
        IPF_Utils::makeDirectories($writePath);
        $writePath .= DIRECTORY_SEPARATOR . $fileName;

        if (file_put_contents($writePath, implode(PHP_EOL, $code)) === false)
            throw new IPF_ORM_Exception("Couldn't write file " . $writePath);
    }

    private function buildShortcuts(array $definition)
    {
        return array(
            '  public static function table()',
            '  {',
            '    return IPF_ORM::getTable(\''.$definition['className'].'\');',
            '  }',
            '',
            '  public static function query($alias=\'\')',
            '  {',
            '    return IPF_ORM::getTable(\''.$definition['className'].'\')->createQuery($alias);',
            '  }',
        );
    }

    private function writeModelDefinition(array $definition, $targetPath)
    {
        $className = $definition['className'];
        $adminClassName = $className.'Admin';

        $writePath = $targetPath . DIRECTORY_SEPARATOR . $className . '.php';
        if (file_exists($writePath))
            return;

        $code = array(
            '<?php',
            '',
            sprintf('class %s extends %s%s', $className, $this->_baseClassPrefix, $className),
            '{',
            '}',
            '',
            '/*',
            'class '.$adminClassName.' extends IPF_Admin_Model',
            '{',
            '}',
            '',
            'IPF_Admin_Model::register(\''.$className.'\', \''.$adminClassName.'\');',
            '*/',
            '',
        );

        IPF_Utils::makeDirectories($targetPath);
        if (file_put_contents($writePath, implode(PHP_EOL, $code)) === false)
            throw new IPF_ORM_Exception("Couldn't write file " . $writePath);
    }
}

