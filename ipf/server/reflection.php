<?php

class IPF_Server_Reflection
{
    public static function reflectClass($class, $argv = false, $namespace = '')
    {
        if (is_object($class)) {
            $reflection = new ReflectionObject($class);
        } elseif (class_exists($class)) {
            $reflection = new ReflectionClass($class);
        } else {
            throw new IPF_Exception('Invalid class or object passed to attachClass()');
        }

        if ($argv && !is_array($argv)) {
            throw new IPF_Exception('Invalid argv argument passed to reflectClass');
        }
        return new IPF_Server_Reflection_Class($reflection, $namespace, $argv);
    }

    public static function reflectFunction($function, $argv = false, $namespace = '')
    {
        if (!is_string($function) || !function_exists($function)) {
            throw new IPF_Exception('Invalid function "' . $function . '" passed to reflectFunction');
        }
        if ($argv && !is_array($argv)) {
            throw new IPF_Exception('Invalid argv argument passed to reflectClass');
        }
        return new IPF_Server_Reflection_Function(new ReflectionFunction($function), $namespace, $argv);
    }
}
