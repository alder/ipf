<?php

class IPF_ORM_Validator extends IPF_ORM_Locator_Injectable
{
    private static $validators = array();
    public static function getValidator($name)
    {
        if ( ! isset(self::$validators[$name])) {
            $class = 'IPF_ORM_Validator_' . ucwords(strtolower($name));
            if (class_exists($class)) {
                self::$validators[$name] = new $class;
            } else if (class_exists($name)) {
                self::$validators[$name] = new $name;
            } else {
                throw new IPF_ORM_Exception("Validator named '$name' not available.");
            }

        }
        return self::$validators[$name];
    }

    public function validateRecord(IPF_ORM_Record $record)
    {
        $table = $record->getTable();

        // if record is transient all fields will be validated
        // if record is persistent only the modified fields will be validated
        $fields = $record->exists() ? $record->getModified():$record->getData();
        foreach ($fields as $fieldName => $value) {
            $table->validateField($fieldName, $value, $record);
        }
    }

    public static function validateLength($value, $type, $maximumLength)
    {
        if ($type == 'timestamp' || $type == 'integer' || $type == 'enum') {
            return true;
        } else if ($type == 'array' || $type == 'object') {
            $length = strlen(serialize($value));
        } else {
            $length = strlen($value);
        }
        if ($length > $maximumLength) {
            return false;
        }
        return true;
    }

    public function hasErrors()
    {
        return (count($this->stack) > 0);
    }

     public static function isValidType($var, $type)
     {
         if ($var instanceof IPF_ORM_Expression) {
             return true;
         } else if ($var === null) {
             return true;
         } else if (is_object($var)) {
             return $type == 'object';
         }

         switch ($type) {
             case 'float':
             case 'double':
             case 'decimal':
                 return (string)$var == strval(floatval($var));
             case 'integer':
                 return (string)$var == strval(intval($var));
             case 'string':
                 return is_string($var) || is_numeric($var);
             case 'blob':
             case 'clob':
             case 'gzip':
                 return is_string($var);
             case 'array':
                 return is_array($var);
             case 'object':
                 return is_object($var);
             case 'boolean':
                 return is_bool($var) || (is_numeric($var) && ($var == 0 || $var == 1));
             case 'timestamp':
                 $validator = self::getValidator('timestamp');
                 return $validator->validate($var);
             case 'time':
                 $validator = self::getValidator('time');
                 return $validator->validate($var);
             case 'date':
                 $validator = self::getValidator('date');
                 return $validator->validate($var);
             case 'enum':
                 return is_string($var) || is_int($var);
             default:
                 return false;
         }
     }
}