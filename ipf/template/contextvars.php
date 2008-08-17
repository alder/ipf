<?php

class IPF_Template_ContextVars extends ArrayObject
{
    function offsetGet($prop)
    {
        if (!$this->offsetExists($prop)) {
            return '';
        }
        return parent::offsetGet($prop);
    }

    function __get($prop)
    {
        if (isset($this->$prop)) {
            return $this->$prop;
        } else {
            return $this->offsetGet($prop);
        }
    }

    function __toString()
    {
        return var_export($this, true);
    }
}
