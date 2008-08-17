<?php

class IPF_ORM_Hydrator_ArrayDriver
{
    public function getElementCollection($component)
    {
        return array();
    }
    public function getElement(array $data, $component)
    {
        return $data;
    }

    public function registerCollection($coll)
    {
    }

    public function initRelated(array &$data, $name)
    {
        if ( ! isset($data[$name])) {
            $data[$name] = array();
        }
        return true;
    }

    public function getNullPointer() 
    {
        return null;    
    }

    public function getLastKey(&$data)
    {
        end($data);
        return key($data);
    }

    public function flush()
    {
    }
}
