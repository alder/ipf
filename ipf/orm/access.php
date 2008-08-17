<?php

abstract class IPF_ORM_Access extends IPF_ORM_Locator_Injectable implements ArrayAccess
{
    public function setArray(array $array)
    {
        foreach ($array as $k => $v) {
            $this->set($k, $v);
        }

        return $this;
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->contains($name);
    }

    public function __unset($name)
    {
        return $this->remove($name);
    }

    public function offsetExists($offset)
    {
        return $this->contains($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    public function remove($offset)
    {
        throw new IPF_ORM_Exception('Remove is not supported for ' . get_class($this));
    }

    public function get($offset)
    {
        throw new IPF_ORM_Exception('Get is not supported for ' . get_class($this));
    }

    public function set($offset, $value)
    {
        throw new IPF_ORM_Exception('Set is not supported for ' . get_class($this));
    }

    public function contains($offset)
    {
        throw new IPF_ORM_Exception('Contains is not supported for ' . get_class($this));
    }

    public function add($value)
    {
        throw new IPF_ORM_Exception('Add is not supported for ' . get_class($this));
    }
}