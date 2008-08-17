<?php

class IPF_ORM_Query_Having extends IPF_ORM_Query_Condition
{
    private function parseAggregateFunction($func)
    {
        $pos = strpos($func, '(');

        if ($pos !== false) {
            $funcs  = array();

            $name   = substr($func, 0, $pos);
            $func   = substr($func, ($pos + 1), -1);
            $params = $this->_tokenizer->bracketExplode($func, ',', '(', ')');

            foreach ($params as $k => $param) {
                $params[$k] = $this->parseAggregateFunction($param);
            }

            $funcs = $name . '(' . implode(', ', $params) . ')';

            return $funcs;

        } else {
            if ( ! is_numeric($func)) {
                $a = explode('.', $func);

                if (count($a) > 1) {
                    $field     = array_pop($a);
                    $reference = implode('.', $a);
                    $map       = $this->query->load($reference, false);
                    $field     = $map['table']->getColumnName($field);
                    $func      = $this->query->getTableAlias($reference) . '.' . $field;
                } else {
                    $field = end($a);
                    $func  = $this->query->getAggregateAlias($field);
                }
                return $func;
            } else {
                return $func;
            }
        }
    }

    final public function load($having)
    {
        $tokens = $this->_tokenizer->bracketExplode($having, ' ', '(', ')');
        $part = $this->parseAggregateFunction(array_shift($tokens));
        $operator  = array_shift($tokens);
        $value     = implode(' ', $tokens);
        $part .= ' ' . $operator . ' ' . $value;
        // check the RHS for aggregate functions
        if (strpos($value, '(') !== false) {
          $value = $this->parseAggregateFunction($value);
        }
        return $part;
    }
}
