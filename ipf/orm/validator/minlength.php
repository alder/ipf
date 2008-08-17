<?php

class IPF_ORM_Validator_Minlength 
{
    public function validate($value)
    {
        if (isset($this->args) && strlen($value) < $this->args) {
            return false;
        }
        return true;
    }
}