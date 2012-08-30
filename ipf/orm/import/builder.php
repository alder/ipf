<?php

class IPF_ORM_Import_Builder extends IPF_ORM_Builder
{
    protected $_path = '';
    protected $_packagesPrefix = 'Package';
    protected $_packagesPath = '';
    protected $_packagesFolderName = 'packages';
    protected $_suffix = '.php';
    protected $_generateBaseClasses = true;
    protected $_generateTableClasses = false;
    protected $_baseClassPrefix = 'Base';
    protected $_baseClassesDirectory = IPF_ORM::BASE_CLASSES_DIRECTORY;
    protected $_baseClassName = 'IPF_ORM_Record';
    protected $_generateAccessors = false;
    protected static $_tpl;
    public function __construct()
    {
        $this->loadTemplate();
    }

    public function setTargetPath($path)
    {
        if ($path) {
            if ( ! $this->_packagesPath) {
                $this->setPackagesPath($path . DIRECTORY_SEPARATOR . $this->_packagesFolderName);
            }

            $this->_path = $path;
        }
    }

    public function setPackagesPrefix($packagesPrefix)
    {
        $this->_packagesPrefix = $packagesPrefix;
    }

    public function setPackagesPath($packagesPath)
    {
        if ($packagesPath) {
            $this->_packagesPath = $packagesPath;
        }
    }

    public function generateBaseClasses($bool = null)
    {
        if ($bool !== null) {
            $this->_generateBaseClasses = $bool;
        }

        return $this->_generateBaseClasses;
    }

    public function generateTableClasses($bool = null)
    {
        if ($bool !== null) {
            $this->_generateTableClasses = $bool;
        }

        return $this->_generateTableClasses;
    }

    public function generateAccessors($bool = null)
    {
      if ($bool !== null) {
          $this->_generateAccessors = $bool;
      }

      return $this->_generateAccessors;
    }

    public function setBaseClassPrefix($prefix)
    {
        $this->_baseClassPrefix = $prefix;
    }

    public function getBaseClassPrefix()
    {
        return $this->_baseClassPrefix;
    }

    public function setbaseClassesDirectory($baseClassesDirectory)
    {
        $this->_baseClassesDirectory = $baseClassesDirectory;
    }

    public function setBaseClassName($className)
    {
        $this->_baseClassName = $className;
    }

    public function setSuffix($suffix)
    {
        $this->_suffix = $suffix;
    }

    public function getTargetPath()
    {
        return $this->_path;
    }

    public function setOptions($options)
    {
        if ( ! empty($options)) {
            foreach ($options as $key => $value) {
                $this->setOption($key, $value);
            }
        }
    }

    public function setOption($key, $value)
    {
        $name = 'set' . IPF_ORM_Inflector::classify($key);

        if (method_exists($this, $name)) {
            $this->$name($value);
        } else {
            $key = '_' . $key;
            $this->$key = $value;
        }
    }

    public function loadTemplate()
    {
        if (isset(self::$_tpl)) {
            return;
        }

        self::$_tpl = '/**' . PHP_EOL
                    . ' * This class has been auto-generated by the IPF_ORM Framework' . PHP_EOL
                    . ' */' . PHP_EOL
                    . '%sclass %s extends %s' . PHP_EOL
                    . '{'
                    . '%s' . PHP_EOL
                    . '%s' . PHP_EOL
                    . '%s'
                    . '}';
    }

    public function buildTableDefinition(array $definition)
    {
        if (isset($definition['inheritance']['type']) && ($definition['inheritance']['type'] == 'simple' || $definition['inheritance']['type'] == 'column_aggregation')) {
            return;
        }

        $ret = array();

        $i = 0;

        if (isset($definition['inheritance']['type']) && $definition['inheritance']['type'] == 'concrete') {
            $ret[$i] = "    parent::setTableDefinition();";
            $i++;
        }

        if (isset($definition['tableName']) && !empty($definition['tableName'])) {
            $ret[$i] = "    ".'$this->setTableName(\''. $definition['tableName'].'\');';
            $i++;
        }

        if (isset($definition['columns']) && is_array($definition['columns']) && !empty($definition['columns'])) {
            $ret[$i] = $this->buildColumns($definition['columns']);
            $i++;
        }

        if (isset($definition['indexes']) && is_array($definition['indexes']) && !empty($definition['indexes'])) {
            $ret[$i] = $this->buildIndexes($definition['indexes']);
            $i++;
        }

        if (isset($definition['attributes']) && is_array($definition['attributes']) && !empty($definition['attributes'])) {
            $ret[$i] = $this->buildAttributes($definition['attributes']);
            $i++;
        }

        if (isset($definition['options']) && is_array($definition['options']) && !empty($definition['options'])) {
            $ret[$i] = $this->buildOptions($definition['options']);
            $i++;
        }

        if (isset($definition['inheritance']['subclasses']) && ! empty($definition['inheritance']['subclasses'])) {
            $ret[$i] = "    ".'$this->setSubClasses('. $this->varExport($definition['inheritance']['subclasses']).');';
            $i++;
        }

        $code = implode(PHP_EOL, $ret);
        $code = trim($code);

        return PHP_EOL . "  public function setTableDefinition()" . PHP_EOL . '  {' . PHP_EOL . '    ' . $code . PHP_EOL . '  }';
    }

    public function buildSetUp(array $definition)
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
                    $a[] = '\'refClass\' => ' . $this->varExport($relation['refClass']);
                }

                if (isset($relation['deferred']) && $relation['deferred']) {
                    $a[] = '\'default\' => ' . $this->varExport($relation['deferred']);
                }

                if (isset($relation['local']) && $relation['local']) {
                    $a[] = '\'local\' => ' . $this->varExport($relation['local']);
                }

                if (isset($relation['foreign']) && $relation['foreign']) {
                    $a[] = '\'foreign\' => ' . $this->varExport($relation['foreign']);
                }

                if (isset($relation['onDelete']) && $relation['onDelete']) {
                    $a[] = '\'onDelete\' => ' . $this->varExport($relation['onDelete']);
                }

                if (isset($relation['onUpdate']) && $relation['onUpdate']) {
                    $a[] = '\'onUpdate\' => ' . $this->varExport($relation['onUpdate']);
                }

                if (isset($relation['equal']) && $relation['equal']) {
                    $a[] = '\'equal\' => ' . $this->varExport($relation['equal']);
                }

                if (isset($relation['owningSide']) && $relation['owningSide']) {
                    $a[] = '\'owningSide\' => ' . $this->varExport($relation['owningSide']);
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
            foreach($definition['listeners'] as $listener)
            {
                $ret[$i] = $this->buildListener($listener);
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

    public function buildColumns(array $columns)
    {
        $build = null;
        foreach ($columns as $name => $column) {
            $columnName = isset($column['name']) ? $column['name']:$name;
            $build .= "    ".'$this->hasColumn(\'' . $columnName . '\', \'' . $column['type'] . '\'';

            if ($column['length']) {
                $build .= ', ' . $column['length'];
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
                $build .= ', ' . $this->varExport($options);
            }

            $build .= ');' . PHP_EOL;
        }

        return $build;
    }

    public function buildAccessors(array $definition)
    {
        $accessors = array();
        foreach (array_keys($definition['columns']) as $name) {
            $accessors[] = $name;
        }

        foreach ($definition['relations'] as $relation) {
            $accessors[] = $relation['alias'];
        }

        $ret = '';
        foreach ($accessors as $name) {
            // getters
            $ret .= PHP_EOL . '  public function get' . IPF_ORM_Inflector::classify(IPF_ORM_Inflector::tableize($name)) . "(\$load = true)" . PHP_EOL;
            $ret .= "  {" . PHP_EOL;
            $ret .= "    return \$this->get('{$name}', \$load);" . PHP_EOL;
            $ret .= "  }" . PHP_EOL;

            // setters
            $ret .= PHP_EOL . '  public function set' . IPF_ORM_Inflector::classify(IPF_ORM_Inflector::tableize($name)) . "(\${$name}, \$load = true)" . PHP_EOL;
            $ret .= "  {" . PHP_EOL;
            $ret .= "    return \$this->set('{$name}', \${$name}, \$load);" . PHP_EOL;
            $ret .= "  }" . PHP_EOL;
        }

        return $ret;
    }

    public function buildTemplates(array $templates)
    {
        $build = '';
        foreach ($templates as $name => $options) {

            if (is_array($options) && !empty($options)) {
                $optionsPhp = $this->varExport($options);

                $build .= "    \$this->loadTemplate('" . $name . "', " . $optionsPhp . ");" . PHP_EOL;
            } else {
                if (isset($templates[0])) {
                    $build .= "    \$this->loadTemplate('" . $options . "');" . PHP_EOL;
                } else {
                    $build .= "    \$this->loadTemplate('" . $name . "');" . PHP_EOL;
                }
            }
        }

        return $build;
    }

    private function emitAssign($level, $name, $option)
    {
        // find class matching $name
        $classname = $name;
        if (class_exists("IPF_ORM_Template_$name", true)) {
            $classname = "IPF_ORM_Template_$name";
        }
        return "    \$" . strtolower($name) . "$level = new $classname($option);". PHP_EOL;
    }

    private function emitAddChild($level, $parent, $name)
    {
        return "    \$" . strtolower($parent) . ($level - 1) . "->addChild(\$" . strtolower($name) . "$level);" . PHP_EOL;
    }

    private function emitActAs($level, $name)
    {
        return "    \$this->actAs(\$" . strtolower($name) . "$level);" . PHP_EOL;
    }

    public function buildActAs($actAs)
    {
        $emittedActAs = array();
        $build = $this->innerBuildActAs($actAs, 0, null, $emittedActAs);
        foreach($emittedActAs as $str) {
            $build .= $str;
        }
        return $build;
    }

    private function innerBuildActAs($actAs, $level, $parent, array &$emittedActAs)
    {
        // rewrite special case of actAs: [Behavior] which gave [0] => Behavior
        if(is_array($actAs) && isset($actAs[0]) && !is_array($actAs[0])) {
            $actAs = array_flip($actAs);
        }

        $build = '';
        $currentParent = $parent;
        if(is_array($actAs)) {
            foreach($actAs as $template => $options) {
                if ($template == 'actAs') {
                    // found another actAs
                    $build .= $this->innerBuildActAs($options, $level + 1, $parent, $emittedActAs);
                } else if (is_array($options)) {
                    // remove actAs from options
                    $realOptions = array();
                    $leftActAs = array();
                    foreach($options as $name => $value) {
                        if ($name != 'actAs') {
                            $realOptions[$name] = $options[$name];
                        } else {
                            $leftActAs[$name] = $options[$name];
                        }
                    } 

                    $optionPHP = $this->varExport($realOptions);
                    $build .= $this->emitAssign($level, $template, $optionPHP); 
                    if ($level == 0) {
                        $emittedActAs[] = $this->emitActAs($level, $template);
                    } else {
                        $build .= $this->emitAddChild($level, $currentParent, $template);
                    }
                    // descend for the remainings actAs
                    $parent = $template;            
                    $build .= $this->innerBuildActAs($leftActAs, $level, $template, $emittedActAs);
                } else {
                    $build .= $this->emitAssign($level, $template, null);
                    if ($level == 0) {
                        $emittedActAs[] = $this->emitActAs($level, $template);
                    } else {
                        $build .= $this->emitAddChild($level, $currentParent, $template);
                    }
                    $parent = $template;            
                }
            }
        } else {
            $build .= $this->emitAssign($level, $actAs, null);
            if ($level == 0) {
                $emittedActAs[] = $this->emitActAs($level, $actAs);
            } else {
                $build .= $this->emitAddChild($level, $currentParent, $actAs);
            }
        }

        return $build;
    }

    public function buildListener($listener)
    {
        return PHP_EOL."    ".'$this->addListener(new '.$listener.'());';
    }


    public function buildAttributes(array $attributes)
    {
        $build = PHP_EOL;
        foreach ($attributes as $key => $value) {

            if (is_bool($value))
            {
              $values = $value ? 'true':'false';
            } else {
                if ( ! is_array($value)) {
                    $value = array($value);
                }

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

    public function buildOptions(array $options)
    {
        $build = '';
        foreach ($options as $name => $value) {
            $build .= "    \$this->option('$name', " . $this->varExport($value) . ");" . PHP_EOL;
        }

        return $build;
    }

    public function buildIndexes(array $indexes)
    {
      $build = '';

      foreach ($indexes as $indexName => $definitions) {
          $build .= PHP_EOL . "    \$this->index('" . $indexName . "'";
          $build .= ', ' . $this->varExport($definitions);
          $build .= ');';
      }

      return $build;
    }

    public function buildDefinition(array $definition)
    {
        if ( ! isset($definition['className'])) {
            throw new IPF_ORM_Exception('Missing class name.');
        }

        $abstract = isset($definition['abstract']) && $definition['abstract'] === true ? 'abstract ':null;
        $className = $definition['className'];
        $extends = isset($definition['inheritance']['extends']) ? $definition['inheritance']['extends']:$this->_baseClassName;

        if ( ! (isset($definition['no_definition']) && $definition['no_definition'] === true)) {
            $tableDefinitionCode = $this->buildTableDefinition($definition);
            $setUpCode = $this->buildSetUp($definition);
        } else {
            $tableDefinitionCode = null;
            $setUpCode = null;
        }

        if ($tableDefinitionCode && $setUpCode) {
            $setUpCode = PHP_EOL . $setUpCode;
        }

        if ( ! isset($definition['generate_accessors']) || !$definition['generate_accessors']) {
          $definition['generate_accessors'] = $this->generateAccessors();
        }

        $accessorsCode = (isset($definition['generate_accessors']) && $definition['generate_accessors'] === true) ? $this->buildAccessors($definition):null;

        $content = sprintf(self::$_tpl, $abstract,
                                       $className,
                                       $extends,
                                       $tableDefinitionCode,
                                       $setUpCode,
                                       $accessorsCode);

        return $content;
    }

    public function buildRecord(array $definition)
    {
        if ( !isset($definition['className'])) {
            throw new IPF_ORM_Exception('Missing class name.');
        }
        $definition['topLevelClassName'] = $definition['className'];
        if ($this->generateBaseClasses()) {
            $definition['is_package'] = (isset($definition['package']) && $definition['package']) ? true:false;

            if ($definition['is_package']) {
                $e = explode('.', trim($definition['package']));
                $definition['package_name'] = $e[0];

                $definition['package_path'] = ! empty($e) ? implode(DIRECTORY_SEPARATOR, $e):$definition['package_name'];
            }
            // Top level definition that extends from all the others
            $topLevel = $definition;
            unset($topLevel['tableName']);

            // If we have a package then we need to make this extend the package definition and not the base definition
            // The package definition will then extends the base definition
            $topLevel['inheritance']['extends'] = (isset($topLevel['package']) && $topLevel['package']) ? $this->_packagesPrefix . $topLevel['className']:$this->_baseClassPrefix . $topLevel['className'];
            $topLevel['no_definition'] = true;
            $topLevel['generate_once'] = true;
            $topLevel['is_main_class'] = true;
            unset($topLevel['connection']);

            // Package level definition that extends from the base definition
            if (isset($definition['package'])) {

                $packageLevel = $definition;
                $packageLevel['className'] = $topLevel['inheritance']['extends'];
                $packageLevel['inheritance']['extends'] = $this->_baseClassPrefix . $topLevel['className'];
                $packageLevel['no_definition'] = true;
                $packageLevel['abstract'] = true;
                $packageLevel['override_parent'] = true;
                $packageLevel['generate_once'] = true;
                $packageLevel['is_package_class'] = true;
                unset($packageLevel['connection']);

                $packageLevel['tableClassName'] = $packageLevel['className'] . 'Table';
                $packageLevel['inheritance']['tableExtends'] = isset($definition['inheritance']['extends']) ? $definition['inheritance']['extends'] . 'Table':'IPF_ORM_Table';

                $topLevel['tableClassName'] = $topLevel['topLevelClassName'] . 'Table';
                $topLevel['inheritance']['tableExtends'] = $packageLevel['className'] . 'Table';
            } else {
                $topLevel['tableClassName'] = $topLevel['className'] . 'Table';
                $topLevel['inheritance']['tableExtends'] = isset($definition['inheritance']['extends']) ? $definition['inheritance']['extends'] . 'Table':'IPF_ORM_Table';
            }

            $baseClass = $definition;
            $baseClass['className'] = $this->_baseClassPrefix . $baseClass['className'];
            $baseClass['abstract'] = true;
            $baseClass['override_parent'] = false;
            $baseClass['is_base_class'] = true;

            $this->writeDefinition($baseClass);

            if ( ! empty($packageLevel)) {
                $this->writeDefinition($packageLevel);
            }

            $this->writeDefinition($topLevel);
        } else {
            $this->writeDefinition($definition);
        }
    }

    public function writeTableDefinition($className, $path, $options = array())
    {
        $content  = '<?php' . PHP_EOL;
        $content .= sprintf(self::$_tpl, false,
                                       $className,
                                       isset($options['extends']) ? $options['extends']:'IPF_ORM_Table',
                                       null,
                                       null,
                                       null
                                       );

        IPF_Utils::makeDirectories($path);

        $writePath = $path . DIRECTORY_SEPARATOR . $className . $this->_suffix;

        IPF_ORM::loadModel($className, $writePath);

        if ( ! file_exists($writePath)) {
            file_put_contents($writePath, $content);
        }
    }

    public function writeDefinition(array $definition)
    {
        $definitionCode = $this->buildDefinition($definition);

        $fileName = $definition['className'] . $this->_suffix;

        $packagesPath = $this->_packagesPath ? $this->_packagesPath:$this->_path;

        // If this is a main class that either extends from Base or Package class
        if (isset($definition['is_main_class']) && $definition['is_main_class']) {
            // If is package then we need to put it in a package subfolder
            if (isset($definition['is_package']) && $definition['is_package']) {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $definition['package_name'];
            // Otherwise lets just put it in the root of the path
            } else {
                $writePath = $this->_path;
            }

            if ($this->generateTableClasses()) {
                $this->writeTableDefinition($definition['tableClassName'], $writePath, array('extends' => $definition['inheritance']['tableExtends']));
            }
        }
        // If is the package class then we need to make the path to the complete package
        else if (isset($definition['is_package_class']) && $definition['is_package_class']) {
            $writePath = $packagesPath . DIRECTORY_SEPARATOR . $definition['package_path'];

            if ($this->generateTableClasses()) {
                $this->writeTableDefinition($definition['tableClassName'], $writePath, array('extends' => $definition['inheritance']['tableExtends']));
            }
        }
        // If it is the base class of the IPF_ORM record definition
        else if (isset($definition['is_base_class']) && $definition['is_base_class']) {
            // If it is a part of a package then we need to put it in a package subfolder
            if (isset($definition['is_package']) && $definition['is_package']) {
                $basePath = $this->_path . DIRECTORY_SEPARATOR . $definition['package_name'];
                $writePath = $basePath . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            // Otherwise lets just put it in the root generated folder
            } else {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            }
        }

        // If we have a writePath from the if else conditionals above then use it
        if (isset($writePath)) {
            IPF_Utils::makeDirectories($writePath);

            $writePath .= DIRECTORY_SEPARATOR . $fileName;
        // Otherwise none of the conditions were met and we aren't generating base classes
        } else {
            IPF_Utils::makeDirectories($this->_path);

            $writePath = $this->_path . DIRECTORY_SEPARATOR . $fileName;
        }

        $code = "<?php" . PHP_EOL;

        if (isset($definition['connection']) && $definition['connection']) {
            $code .= "// Connection Component Binding" . PHP_EOL;
            $code .= "IPF_ORM_Manager::getInstance()->bindComponent('" . $definition['connectionClassName'] . "', '" . $definition['connection'] . "');" . PHP_EOL;
        }

        $code .= PHP_EOL . $definitionCode;

        if (isset($definition['generate_once']) && $definition['generate_once'] === true) {
            if ( ! file_exists($writePath)) {
                $bytes = file_put_contents($writePath, $code);
            }
        } else {
            $bytes = file_put_contents($writePath, $code);
        }

        if (isset($bytes) && $bytes === false) {
            throw new IPF_ORM_Exception("Couldn't write file " . $writePath);
        }

        IPF_ORM::loadModel($definition['className'], $writePath);
    }
}
