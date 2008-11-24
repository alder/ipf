<?php

class IPF_ORM_Validator_Timestamp
{
    public function validate($value)
    {
        if ($value === null) {
            return true;
        }

        if ( ! preg_match('/^ *[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} *$/', $value)) {
            return false;
        }

        list($date, $time) = explode(' ', trim($value));

        $dateValidator = IPF_ORM_Validator::getValidator('date');
        $timeValidator = IPF_ORM_Validator::getValidator('time');

        if ( ! $dateValidator->validate($date)) {
            return false;
        }

        if ( ! $timeValidator->validate($time)) {
            return false;
        }

        return true;
    }
}