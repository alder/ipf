<?php

abstract class IPF_Server_Abstract implements IPF_Server_Interface
{
    protected static $magic_methods = array(
        '__construct',
        '__destruct',
        '__get',
        '__set',
        '__call',
        '__sleep',
        '__wakeup',
        '__isset',
        '__unset',
        '__tostring',
        '__clone',
        '__set_state',
    );

    public static function lowerCase(&$value, &$key)
    {
        return $value = strtolower($value);
    }
}
