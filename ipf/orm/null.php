<?php

final class IPF_ORM_Null
{
    private static $instance = null;
    public static function getInstance()
    {
        if (!self::$instance)
            self::$instance = new IPF_ORM_Null;
        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function isNull($obj)
    {
        return $obj === self::$instance;
    }

    public function exists()
    {
        return false;
    }

    public function __toString()
    {
        return '';
    }
}

