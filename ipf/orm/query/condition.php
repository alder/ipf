<?php

abstract class IPF_ORM_Query_Condition extends IPF_ORM_Query_Part
{
    public function parse($str)
    {
        $tmp = trim($str);

        $parts = $this->_tokenizer->bracketExplode($str, array(' \&\& ', ' AND '), '(', ')');

        if (count($parts) > 1) {
            $ret = array();
            foreach ($parts as $part) {
                $part = $this->_tokenizer->bracketTrim($part, '(', ')');
                $ret[] = $this->parse($part);
            }
            $r = implode(' AND ', $ret);
        } else {

            $parts = $this->_tokenizer->bracketExplode($str, array(' \|\| ', ' OR '), '(', ')');
            if (count($parts) > 1) {
                $ret = array();
                foreach ($parts as $part) {
                    $part = $this->_tokenizer->bracketTrim($part, '(', ')');
                    $ret[] = $this->parse($part);
                }
                $r = implode(' OR ', $ret);
            } else {
                // Fix for #710
                if (substr($parts[0],0,1) == '(' && substr($parts[0], -1) == ')') {
                    return $this->parse(substr($parts[0], 1, -1));
                } else {
                    // Processing NOT here
                    if (strtoupper(substr($parts[0], 0, 4)) === 'NOT ') {
                        $r = 'NOT ('.$this->parse(substr($parts[0], 4)).')';
                    } else {
                        return $this->load($parts[0]);
                    }
                }
            }
        }

        return '(' . $r . ')';
    }

    public function parseLiteralValue($value)
    {
        // check that value isn't a string
        if (strpos($value, '\'') === false) {
            // parse booleans
            $value = $this->query->getConnection()
                     ->dataDict->parseBoolean($value);

            $a = explode('.', $value);

            if (count($a) > 1) {
            // either a float or a component..

                if ( ! is_numeric($a[0])) {
                    // a component found
                    $field     = array_pop($a);
                	  $reference = implode('.', $a);
                    $value = $this->query->getTableAlias($reference). '.' . $field;
                }
            }
        } else {
            // string literal found
        }
        return $value;
    }
}