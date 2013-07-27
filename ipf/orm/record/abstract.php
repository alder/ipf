<?php

abstract class IPF_ORM_Record_Abstract extends IPF_ORM_Access
{
    protected $_table;

    public function setTableDefinition()
    {
    }

    public function setUp()
    {
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function index($name, array $definition = array())
    {
        if ( ! $definition) {
            return $this->_table->getIndex($name);
        } else {
            return $this->_table->addIndex($name, $definition);
        }
    }
    public function setAttribute($attr, $value)
    {
        $this->_table->setAttribute($attr, $value);
    }
    public function setTableName($tableName)
    {
        $this->_table->setTableName($tableName);
    }
    public function setInheritanceMap($map)
    {
        $this->_table->setOption('inheritanceMap', $map);
    }

    public function setSubclasses($map)
    {
        if (isset($map[get_class($this)])) {
            $this->_table->setOption('inheritanceMap', $map[get_class($this)]);
            return;
        }
        $this->_table->setOption('subclasses', array_keys($map));
    }

    public function attribute($attr, $value)
    {
        if ($value == null) {
            if (is_array($attr)) {
                foreach ($attr as $k => $v) {
                    $this->_table->setAttribute($k, $v);
                }
            } else {
                return $this->_table->getAttribute($attr);
            }
        } else {
            $this->_table->setAttribute($attr, $value);
        }    
    }

    public function option($name, $value = null)
    {
        if ($value === null) {
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    $this->_table->setOption($k, $v);
                }
            } else {
                return $this->_table->getOption($name);
            }
        } else {
            $this->_table->setOption($name, $value);
        }
    }

    public function ownsOne()
    {
        $this->_table->bind(func_get_args(), IPF_ORM_Relation::ONE_COMPOSITE);
        
        return $this;
    }

    public function ownsMany()
    {
        $this->_table->bind(func_get_args(), IPF_ORM_Relation::MANY_COMPOSITE);
        return $this;
    }

    public function hasOne()
    {
        $this->_table->bind(func_get_args(), IPF_ORM_Relation::ONE_AGGREGATE);
        return $this;
    }

    public function hasMany()
    {
        $this->_table->bind(func_get_args(), IPF_ORM_Relation::MANY_AGGREGATE);
        return $this;
    }

    public function hasColumn($name, $type, $length = 2147483647, $options = "")
    {
        $this->_table->setColumn($name, $type, $length, $options);
    }

    public function hasColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $this->hasColumn($name, $options['type'], $options['length'], $options);
        }
    } 

    public function bindQueryParts(array $queryParts)
    {
        $this->_table->bindQueryParts($queryParts);
        return $this;
    }

    public function loadGenerator(IPF_ORM_Record_Generator $generator)
    {
        $generator->initialize($this->_table);
        $this->_table->addGenerator($generator, get_class($generator));
    }

    public function check($constraint, $name = null)
    {
        if (is_array($constraint)) {
            foreach ($constraint as $name => $def) {
                $this->_table->addCheckConstraint($def, $name);
            }
        } else {
            $this->_table->addCheckConstraint($constraint, $name);
        }
        return $this;
    }
}

