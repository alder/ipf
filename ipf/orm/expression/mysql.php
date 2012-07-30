<?php

class IPF_ORM_Expression_Mysql extends IPF_ORM_Expression_Driver
{
    public function regexp()
    {
        return 'RLIKE';
    }

    public function random()
    {
        return 'RAND()';
    }

    public function matchPattern($pattern, $operator = null, $field = null)
    {
        $match = '';
        if ( ! is_null($operator)) {
            $field = is_null($field) ? '' : $field.' ';
            $operator = strtoupper($operator);
            switch ($operator) {
                // case insensitive
                case 'ILIKE':
                    $match = $field.'LIKE ';
                    break;
                // case sensitive
                case 'LIKE':
                    $match = $field.'LIKE BINARY ';
                    break;
                default:
                    throw new IPF_ORM_Exception('not a supported operator type:'. $operator);
            }
        }
        $match.= "'";
        foreach ($pattern as $key => $value) {
            if ($key % 2) {
                $match .= $value;
            } else {
                $match .= $this->conn->escapePattern($this->conn->escape($value));
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        return $match;
    }

    public function guid()
    {
        return 'UUID()';
    }

    public function year($column)
    {
        $column = $this->getIdentifier($column);
        return 'YEAR(' .  $column . ')';
    }

    public function month($column)
    {
        $column = $this->getIdentifier($column);
        return 'MONTH(' .  $column . ')';
    }

    public function monthname($column)
    {
        $column = $this->getIdentifier($column);
        return 'MONTHNAME(' .  $column . ')';
    }

    public function day($column)
    {
        $column = $this->getIdentifier($column);
        return 'DAY(' .  $column . ')';
    }
}
