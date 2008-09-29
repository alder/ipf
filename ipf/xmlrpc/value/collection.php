<?php

abstract class IPF_XmlRpc_Value_Collection extends IPF_XmlRpc_Value
{
    public function __construct($value)
    {
        $values = (array)$value;   // Make sure that the value is an array
        foreach ($values as $key => $value) {
            // If the elements of the given array are not IPF_XmlRpc_Value objects,
            // we need to convert them as such (using auto-detection from PHP value)
            if (!$value instanceof parent) {
                $value = self::getXmlRpcValue($value, self::AUTO_DETECT_TYPE);
            }
            $this->_value[$key] = $value;
        }
    }

    public function getValue()
    {
        $values = (array)$this->_value;
        foreach ($values as $key => $value) {
            /* @var $value IPF_XmlRpc_Value */
            if (!$value instanceof parent) {
                throw new IPF_Exception('Values of '. get_class($this) .' type must be IPF_XmlRpc_Value objects');
            }
            $values[$key] = $value->getValue();
        }
        return $values;
    }

}

