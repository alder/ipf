<?php

class IPF_ORM_Validator_Email
{
    public function validate($value)
    {
        if ($value === null)
            return true;
        return IPF_Utils::isEmail($value);
    }
}