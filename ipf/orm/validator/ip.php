<?php

class IPF_ORM_Validator_Ip
{
    public function validate($value)
    {
        return (bool) ip2long(str_replace("\0", '', $value));
    }
}