<?php

class IPF_ORM_Validator_Nospace extends IPF_ORM_Validator_Driver
{
    public function validate($value)
    {
        return ($value === null || ! preg_match('/\s/', $value));
    }
}
