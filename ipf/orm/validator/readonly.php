<?php

class IPF_ORM_Validator_Readonly
{
    public function validate($value)
    {
        $modified = $this->invoker->getModified();
        return array_key_exists($this->field, $modified) ? false : true;
    }
}
