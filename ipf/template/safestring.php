<?php

class IPF_Template_SafeString
{
    public $value = '';

    function __construct($mixed, $safe=false)
    {
        if (is_object($mixed) and 'IPF_Template_SafeString' == get_class($mixed)) {
            $this->value = $mixed->value;
        } else {
            $this->value = ($safe) ? $mixed : htmlspecialchars($mixed, ENT_COMPAT, 'UTF-8');
        }
    }

    function __toString()
    {
        return $this->value;
    }

    public static function markSafe($string)
    {
        return new IPF_Template_SafeString($string, true);
    }
}