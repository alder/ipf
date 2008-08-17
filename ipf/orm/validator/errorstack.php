<?php

class IPF_ORM_Validator_ErrorStack extends IPF_ORM_Access implements Countable, IteratorAggregate
{
    protected $_errors = array();
    protected $_validators = array();

    protected $_className;

    public function __construct($className)
    {
        $this->_className = $className;
    }

    public function add($invalidFieldName, $errorCode = 'general')
    {
        // FIXME: In the future the error stack should contain nothing but validator objects
        if (is_object($errorCode) && strpos(get_class($errorCode), 'IPF_ORM_Validator_') !== false) {
            $validator = $errorCode;
            $this->_validators[$invalidFieldName][] = $validator;
            $className = get_class($errorCode);
            $errorCode = strtolower(substr($className, strlen('IPF_ORM_Validator_'), strlen($className)));
        }

        $this->_errors[$invalidFieldName][] = $errorCode;
    }

    public function remove($fieldName)
    {
        if (isset($this->_errors[$fieldName])) {
            unset($this->_errors[$fieldName]);
        }
    }

    public function get($fieldName)
    {
        return isset($this->_errors[$fieldName]) ? $this->_errors[$fieldName] : null;
    }

    public function set($fieldName, $errorCode)
    {
        $this->add($fieldName, $errorCode);
    }

    public function contains($fieldName)
    {
        return array_key_exists($fieldName, $this->_errors);
    }

    public function clear()
    {
        $this->_errors = array();
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_errors);
    }

    public function toArray()
    {
        return $this->_errors;
    }

    public function count()
    {
        return count($this->_errors);
    }

    public function getClassname()
    {
        return $this->_className;
    }

    public function getValidators()
    {
        return $this->_validators;
    }
}