<?php

class IPF_ORM_Expression_Driver extends IPF_ORM_Connection_Module
{
    public function getIdentifier($column)
    {
        return $column;
    }
    public function getIdentifiers($columns)
    {
        return $columns;
    }

    public function regexp()
    {
        throw new IPF_ORM_Exception('Regular expression operator is not supported by this database driver.');
    }

    public function avg($column)
    {
        $column = $this->getIdentifier($column);
        return 'AVG(' .  $column . ')';
    }

    public function count($column)
    {
        $column = $this->getIdentifier($column);
        return 'COUNT(' . $column . ')';
    }

    public function max($column)
    {
        $column = $this->getIdentifier($column);
        return 'MAX(' . $column . ')';
    }

    public function min($column)
    {
        $column = $this->getIdentifier($column);
        return 'MIN(' . $column . ')';
    }

    public function sum($column)
    {
        $column = $this->getIdentifier($column);
        return 'SUM(' . $column . ')';
    }

    public function md5($column)
    {
        $column = $this->getIdentifier($column);
        return 'MD5(' . $column . ')';
    }

    public function length($column)
    {
        $column = $this->getIdentifier($column);
        return 'LENGTH(' . $column . ')';
    }

    public function round($column, $decimals = 0)
    {
        $column = $this->getIdentifier($column);

        return 'ROUND(' . $column . ', ' . $decimals . ')';
    }

    public function mod($expression1, $expression2)
    {
        $expression1 = $this->getIdentifier($expression1);
        $expression2 = $this->getIdentifier($expression2);
        return 'MOD(' . $expression1 . ', ' . $expression2 . ')';
    }

    public function trim($str)
    {
        return 'TRIM(' . $str . ')';
    }

    public function rtrim($str)
    {
        return 'RTRIM(' . $str . ')';
    }

    public function ltrim($str)
    {
        return 'LTRIM(' . $str . ')';
    }

    public function upper($str)
    {
        return 'UPPER(' . $str . ')';
    }

    public function lower($str)
    {
        return 'LOWER(' . $str . ')';
    }

    public function locate($str, $substr)
    {
        return 'LOCATE(' . $str . ', ' . $substr . ')';
    }

    public function now()
    {
        return 'NOW()';
    }

    public function coalesce($expression1, $expression2)
    {
        $expression1 = $this->getIdentifier($expression1);
        $expression2 = $this->getIdentifier($expression2);
        return 'COALESCE(' . $expression1 . ', ' . $expression2 . ')';
    }

    public function soundex($value)
    {
        throw new IPF_ORM_Exception('SQL soundex function not supported by this driver.');
    }

    public function substring($value, $from, $len = null)
    {
        $value = $this->getIdentifier($value);
        if ($len === null)
            return 'SUBSTRING(' . $value . ' FROM ' . $from . ')';
        else {
            $len = $this->getIdentifier($len);
            return 'SUBSTRING(' . $value . ' FROM ' . $from . ' FOR ' . $len . ')';
        }
    }

    public function concat()
    {
        $args = func_get_args();

        return 'CONCAT(' . join(', ', (array) $args) . ')';
    }

    public function not($expression)
    {
        $expression = $this->getIdentifier($expression);
        return 'NOT(' . $expression . ')';
    }

    private function basicMath($type, array $args)
    {
        $elements = $this->getIdentifiers($args);
        if (count($elements) < 1) {
            return '';
        }
        if (count($elements) == 1) {
            return $elements[0];
        } else {
            return '(' . implode(' ' . $type . ' ', $elements) . ')';
        }
    }

    public function add(array $args)
    {
        return $this->basicMath('+', $args);
    }

    public function sub(array $args)
    {
        return $this->basicMath('-', $args );
    }

    public function mul(array $args)
    {
        return $this->basicMath('*', $args);
    }

    public function div(array $args)
    {
        return $this->basicMath('/', $args);
    }

    public function eq($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' = ' . $value2;
    }

    public function neq($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' <> ' . $value2;
    }

    public function gt($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' > ' . $value2;
    }

    public function gte($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' >= ' . $value2;
    }

    public function lt($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' < ' . $value2;
    }

    public function lte($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' <= ' . $value2;
    }

    public function in($column, $values)
    {
        if ( ! is_array($values)) {
            $values = array($values);
        }
        $values = $this->getIdentifiers($values);
        $column = $this->getIdentifier($column);

        if (count($values) == 0) {
            throw new IPF_ORM_Exception('Values array for IN operator should not be empty.');
        }
        return $column . ' IN (' . implode(', ', $values) . ')';
    }

    public function isNull($expression)
    {
        $expression = $this->getIdentifier($expression);
        return $expression . ' IS NULL';
    }

    public function isNotNull($expression)
    {
        $expression = $this->getIdentifier($expression);
        return $expression . ' IS NOT NULL';
    }

    public function between($expression, $value1, $value2)
    {
        $expression = $this->getIdentifier($expression);
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $expression . ' BETWEEN ' .$value1 . ' AND ' . $value2;
    }

    public function guid()
    {
        throw new IPF_ORM_Exception('method not implemented');
    }

    public function acos($value)
    {
        return 'ACOS(' . $value . ')';
    }

    public function sin($value)
    {
        return 'SIN(' . $value . ')';
    }

    public function pi()
    {
        return 'PI()';
    }

    public function cos($value)
    {
        return 'COS(' . $value . ')';
    }

    public function __call($m, $a) 
    {
        if ($this->conn->getAttribute(IPF_ORM::ATTR_PORTABILITY) & IPF_ORM::PORTABILITY_EXPR) {
            throw new IPF_ORM_Exception('Unknown expression ' . $m);
        }
        return $m . '(' . implode(', ', $a) . ')';
    }
}

