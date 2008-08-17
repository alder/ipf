<?php

class IPF_ORM_Validator_Htmlcolor
{
    public function validate($value)
    {
        if ( ! preg_match("/^#{0,1}[0-9a-fA-F]{6}$/", $value)) {
            return false;
        }
        return true;
    }
}