<?php

class IPF_ORM_Validator_Past
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
        
        if (is_array($this->args) && isset($this->args['timezone'])) {
            switch (strtolower($this->args['timezone'])) {
                case 'gmt':
                    $now = gmdate("U") - date("Z");
                    break;
                default:
                    $now = getdate();
                    break;
            }
        } else {
            $now = getdate();
        }
        
        if ($now['year'] < $e[0]) {
            return false;
        } else if ($now['year'] == $e[0]) {
            if ($now['mon'] < $e[1]) {
                return false;
            } else if ($now['mon'] == $e[1]) {
                return $now['mday'] > $e[2];
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}