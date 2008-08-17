<?php

class IPF_ORM_Query_Where extends IPF_ORM_Query_Condition
{
    public function load($where) 
    {
        $where = $this->_tokenizer->bracketTrim(trim($where));
        $conn  = $this->query->getConnection();
        $terms = $this->_tokenizer->sqlExplode($where);  

        if (count($terms) > 1) {
            if (substr($where, 0, 6) == 'EXISTS') {
                return $this->parseExists($where, true);
            } elseif (substr($where, 0, 10) == 'NOT EXISTS') {
                return $this->parseExists($where, false);
            }
        }

        if (count($terms) < 3) {
            $terms = $this->_tokenizer->sqlExplode($where, array('=', '<', '<>', '>', '!='));
        }

        if (count($terms) > 1) {
            $first = array_shift($terms);
            $value = array_pop($terms);
            $operator = trim(substr($where, strlen($first), -strlen($value)));
            $table = null;
            $field = null;

            if (strpos($first, "'") === false && strpos($first, '(') === false) {
                // normal field reference found
                $a = explode('.', $first);
        
                $field = array_pop($a);
                $reference = implode('.', $a);
                
                if (empty($reference)) {
                    $map = $this->query->getRootDeclaration();  
                    
                    $alias = $this->query->getTableAlias($this->query->getRootAlias());
                    $table = $map['table'];
                } else {
                    $map = $this->query->load($reference, false);
    
                    $alias = $this->query->getTableAlias($reference);
                    $table = $map['table'];
                }
            }
            $first = $this->query->parseClause($first);
            
            $sql = $first . ' ' . $operator . ' ' . $this->parseValue($value, $table, $field);
        
            return $sql;  
        } else {
            return $where;
        }
    }

    public function parseValue($value, IPF_ORM_Table $table = null, $field = null)
    {
        $conn = $this->query->getConnection();

        if (substr($value, 0, 1) == '(') {
            // trim brackets
            $trimmed = $this->_tokenizer->bracketTrim($value);

            if (substr($trimmed, 0, 4) == 'FROM' ||
                substr($trimmed, 0, 6) == 'SELECT') {

                // subquery found
                $q     = new IPF_ORM_Query();
                $value = '(' . $this->query->createSubquery()->parseQuery($trimmed, false)->getQuery() . ')';

            } elseif (substr($trimmed, 0, 4) == 'SQL:') {
                $value = '(' . substr($trimmed, 4) . ')';
            } else {
                // simple in expression found
                $e = $this->_tokenizer->sqlExplode($trimmed, ',');

                $value = array();

                $index = false;

                foreach ($e as $part) {
                    if (isset($table) && isset($field)) {
                        $index = $table->enumIndex($field, trim($part, "'"));

                        if (false !== $index && $conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                            $index = $conn->quote($index, 'text');
                        }
                    }

                    if ($index !== false) {
                        $value[] = $index;
                    } else {
                        $value[] = $this->parseLiteralValue($part);
                    }
                }

                $value = '(' . implode(', ', $value) . ')';
            }
        } else if (substr($value, 0, 1) == ':' || $value === '?') {
            // placeholder found
            if (isset($table) && isset($field) && $table->getTypeOf($field) == 'enum') {
                $this->query->addEnumParam($value, $table, $field);
            } else {
                $this->query->addEnumParam($value, null, null);
            }
        } else {
            $enumIndex = false;
            if (isset($table) && isset($field)) {
                // check if value is enumerated value
                $enumIndex = $table->enumIndex($field, trim($value, "'"));

                if (false !== $enumIndex && $conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                    $enumIndex = $conn->quote($enumIndex, 'text');
                }
            }

            if ($enumIndex !== false) {
                $value = $enumIndex;
            } else {
                $value = $this->parseLiteralValue($value);
            }
        }
        return $value;
    }

    public function parseExists($where, $negation)
    {
        $operator = ($negation) ? 'EXISTS' : 'NOT EXISTS';

        $pos = strpos($where, '(');

        if ($pos == false) {
            throw new IPF_ORM_Exception('Unknown expression, expected a subquery with () -marks');
        }

        $sub = $this->_tokenizer->bracketTrim(substr($where, $pos));

        return $operator . ' (' . $this->query->createSubquery()->parseQuery($sub, false)->getQuery() . ')';
    }
}
