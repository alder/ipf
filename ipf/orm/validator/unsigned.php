<?php

class IPF_ORM_Validator_Unsigned
{
    public function validate($value)
    {
        $int = (int) $value;

        if ($int != $value || $int < 0) {
            return false;
        }
        return true;
    }
}