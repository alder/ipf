<?php

class IPF_ORM_Validator_Range
{
    public function validate($value)
    {
        if (isset($this->args[0]) && $value < $this->args[0]) {
            return false;
        }
        if (isset($this->args[1]) && $value > $this->args[1]) {
            return false;
        }
        return true;
    }
}