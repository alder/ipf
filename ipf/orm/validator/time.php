<?php

class IPF_ORM_Validator_Time
{
    public function validate($value)
    {
        if ($value === null) {
            return true;
        }

        $e = explode(':', $value);

        if (count($e) !== 3) {
            return false;
        }

        if ( ! preg_match('/^ *[0-9]{2}:[0-9]{2}:[0-9]{2} *$/', $value)) {
            return false;
        }

        $hr = intval($e[0], 10);
        $min = intval($e[1], 10);
        $sec = intval($e[2], 10);

        return $hr >= 0 && $hr <= 23 && $min >= 0 && $min <= 59 && $sec >= 0 && $sec <= 59;      
    }
}