<?php

class IPF_ORM_Validator_Date
{
    public function validate($value)
    {
        if ($value === null) {
            return true;
        }
        $e = explode('-', $value);

        if (count($e) !== 3) {
            return false;
        }
        return checkdate($e[1], $e[2], $e[0]);
    }
}