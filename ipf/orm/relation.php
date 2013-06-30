<?php

abstract class IPF_ORM_Relation implements ArrayAccess
{
    const ONE_AGGREGATE         = 0;
    const ONE_COMPOSITE         = 1;
    const MANY_AGGREGATE        = 2;
    const MANY_COMPOSITE        = 3;

    const ONE   = 0;
    const MANY  = 2;

    protected $definition = array('alias'       => true,
                                  'foreign'     => true,
                                  'local'       => true,
                                  'class'       => true,
                                  'type'        => true,
                                  'table'       => true,
                                  'localTable'  => true,
                                  'name'        => null,
                                  'refTable'    => null,
                                  'onDelete'    => null,
                                  'onUpdate'    => null,
                                  'deferred'    => null,
                                  'deferrable'  => null,
                                  'constraint'  => null,
                                  'equal'       => false,
                                  'cascade'     => array(), // application-level cascades
                                  'owningSide'  => false, // whether this is the owning side
                                  'exclude'     => false,
                                  );

    public function __construct(array $definition)
    {
        $def = array();
        foreach ($this->definition as $key => $val) {
            if ( ! isset($definition[$key]) && $val) {
                throw new IPF_ORM_Exception($key . ' is required!');
            }
            if (isset($definition[$key])) {
                $def[$key] = $definition[$key];
            } else {
                $def[$key] = $this->definition[$key];
            }
        }
        $this->definition = $def;
    }

    public function hasConstraint()
    {
        return ($this->definition['constraint'] ||
                ($this->definition['onUpdate']) ||
                ($this->definition['onDelete']));
    }
    public function isDeferred()
    {
        return $this->definition['deferred'];
    }

    public function isDeferrable()
    {
        return $this->definition['deferrable'];
    }
    public function isEqual()
    {
        return $this->definition['equal'];
    }

    public function offsetExists($offset)
    {
        return isset($this->definition[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->definition[$offset])) {
            return $this->definition[$offset];
        }

        return null;
    }

    public function offsetSet($offset, $value)
    {
        if (isset($this->definition[$offset])) {
            $this->definition[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        $this->definition[$offset] = false;
    }

    public function toArray()
    {
        return $this->definition;
    }

    final public function getAlias()
    {
        return $this->definition['alias'];
    }

    final public function getType()
    {
        return $this->definition['type'];
    }

    public function isCascadeDelete()
    {
        return in_array('delete', $this->definition['cascade']);
    }

    final public function getTable()
    {
        return IPF_ORM_Manager::getInstance()
               ->getConnectionForComponent($this->definition['class'])
               ->getTable($this->definition['class']);
    }

    final public function getClass()
    {
        return $this->definition['class'];
    }

    final public function getLocal()
    {
        return $this->definition['local'];
    }

    final public function getLocalFieldName()
    {
        return $this->definition['localTable']->getFieldName($this->definition['local']);
    }

    final public function getForeign()
    {
        return $this->definition['foreign'];
    }

    final public function getForeignFieldName()
    {
        return $this->definition['table']->getFieldName($this->definition['foreign']);
    }

    final public function isComposite()
    {
        return ($this->definition['type'] == IPF_ORM_Relation::ONE_COMPOSITE ||
                $this->definition['type'] == IPF_ORM_Relation::MANY_COMPOSITE);
    }

    final public function isOneToOne()
    {
        return ($this->definition['type'] == IPF_ORM_Relation::ONE_AGGREGATE ||
                $this->definition['type'] == IPF_ORM_Relation::ONE_COMPOSITE);
    }

    public function getRelationDql($count)
    {
        $component = $this->getTable()->getComponentName();

        $dql  = 'FROM ' . $component
              . ' WHERE ' . $component . '.' . $this->definition['foreign']
              . ' IN (' . substr(str_repeat('?, ', $count), 0, -2) . ')';

        return $dql;
    }

    abstract public function fetchRelatedFor(IPF_ORM_Record $record);

    public function __toString()
    {
        $r[] = "<pre>";
        foreach ($this->definition as $k => $v) {
            if (is_object($v)) {
                $v = 'Object(' . get_class($v) . ')';
            }
            $r[] = $k . ' : ' . $v;
        }
        $r[] = "</pre>";
        return implode("\n", $r);
    }
}