<?php

class IPF_XmlRpc_Server_Fault extends IPF_XmlRpc_Fault
{
    protected $_exception;
    protected static $_faultExceptionClasses = array('IPF_Exception' => true);
    protected static $_observers = array();

    public function __construct(Exception $e)
    {
        $this->_exception = $e;
        $code             = 404;
        $message          = 'Unknown error';
        $exceptionClass   = get_class($e);

        foreach (array_keys(self::$_faultExceptionClasses) as $class) {
            if ($e instanceof $class) {
                $code    = $e->getCode();
                $message = $e->getMessage();
                break;
            }
        }

        parent::__construct($code, $message);

        // Notify exception observers, if present
        if (!empty(self::$_observers)) {
            foreach (array_keys(self::$_observers) as $observer) {
                call_user_func(array($observer, 'observe'), $this);
            }
        }
    }

    public static function getInstance(Exception $e)
    {
        return new self($e);
    }

    public static function attachFaultException($classes)
    {
        if (!is_array($classes)) {
            $classes = (array) $classes;
        }

        foreach ($classes as $class) {
            if (is_string($class) && class_exists($class)) {
                self::$_faultExceptionClasses[$class] = true;
            }
        }
    }

    public static function detachFaultException($classes)
    {
        if (!is_array($classes)) {
            $classes = (array) $classes;
        }

        foreach ($classes as $class) {
            if (is_string($class) && isset(self::$_faultExceptionClasses[$class])) {
                unset(self::$_faultExceptionClasses[$class]);
            }
        }
    }

    public static function attachObserver($class)
    {
        if (!is_string($class)
            || !class_exists($class)
            || !is_callable(array($class, 'observe')))
        {
            return false;
        }

        if (!isset(self::$_observers[$class])) {
            self::$_observers[$class] = true;
        }

        return true;
    }

    public static function detachObserver($class)
    {
        if (!isset(self::$_observers[$class])) {
            return false;
        }

        unset(self::$_observers[$class]);
        return true;
    }

    public function getException()
    {
        return $this->_exception;
    }
}
