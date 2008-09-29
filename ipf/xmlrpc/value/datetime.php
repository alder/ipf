<?php

class IPF_XmlRpc_Value_DateTime extends IPF_XmlRpc_Value_Scalar
{
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_DATETIME;

        // If the value is not numeric, we try to convert it to a timestamp (using the strtotime function)
        if (is_numeric($value)) {   // The value is numeric, we make sure it is an integer
            $value = (int)$value;
        } else {
            $value = strtotime($value);
            if ($value === false || $value == -1) { // cannot convert the value to a timestamp
                throw new IPF_Exception('Cannot convert given value \''. $value .'\' to a timestamp');
            }
        }
        $value = date('c', $value); // Convert the timestamp to iso8601 format

        // Strip out TZ information and dashes
        $value = preg_replace('/(\+|-)\d{2}:\d{2}$/', '', $value);
        $value = str_replace('-', '', $value);

        $this->_value = $value;
    }

    public function getValue()
    {
        return $this->_value;
    }

}

