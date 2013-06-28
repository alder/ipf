<?php

class IPF_Template_SafeString
{
    public $value = '';

    public static function value($mixed, $safe=false)
    {
        if (is_object($mixed) and 'IPF_Template_SafeString' == get_class($mixed))
            return $mixed->value;
        if ($safe)
            return $mixed;
        return htmlspecialchars($mixed, ENT_COMPAT, 'UTF-8');
    }

    function __construct($mixed, $safe=false)
    {
        $this->value = self::value($mixed, $safe);
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

