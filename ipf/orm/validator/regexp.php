<?php

class IPF_ORM_Validator_Regexp
{
    public function validate($value)
    {
        if ( ! isset($this->args)) {
           return true;
        }
        if (is_array($this->args)) {
            foreach ($this->args as $regexp) {
                if ( ! preg_match($regexp, $value)) {
                    return false;
                }
            }
            return true;
        } else {
            if (preg_match($this->args, $value)) {
                return true;
            }
        }

        return false;
    }
}