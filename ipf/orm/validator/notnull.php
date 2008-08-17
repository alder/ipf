<?php

class IPF_ORM_Validator_Notnull extends IPF_ORM_Validator_Driver
{
    public function validate($value)
    {
        return ($value !== null);
    }
}
