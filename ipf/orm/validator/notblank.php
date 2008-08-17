<?php

class IPF_ORM_Validator_Notblank extends IPF_ORM_Validator_Driver
{
    public function validate($value)
    {
        return (trim($value) !== '' && $value !== null);
    }
}