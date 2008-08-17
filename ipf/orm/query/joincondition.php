<?php

class IPF_ORM_Query_JoinCondition extends IPF_ORM_Query_Condition 
{
    public function load($condition) 
    {
        $condition = trim($condition);

        $e = $this->_tokenizer->sqlExplode($condition);

        if (count($e) > 2) {
            $expr = new IPF_ORM_Expression($e[0], $this->query->getConnection());
            $e[0] = $expr->getSql();

            $operator  = $e[1];

            if (substr(trim($e[2]), 0, 1) != '(') {
                $expr = new IPF_ORM_Expression($e[2], $this->query->getConnection());
                $e[2] = $expr->getSql();
            }

            // We need to check for agg functions here
            $hasLeftAggExpression = preg_match('/(.*)\(([^\)]*)\)([\)]*)/', $e[0], $leftMatches);

            if ($hasLeftAggExpression) {
                $e[0] = $leftMatches[2];
            }

            $hasRightAggExpression = preg_match('/(.*)\(([^\)]*)\)([\)]*)/', $e[2], $rightMatches);

            if ($hasRightAggExpression) {
                $e[2] = $rightMatches[2];
            }

            $a         = explode('.', $e[0]);
            $field     = array_pop($a);
            $reference = implode('.', $a);
            $value     = $e[2];

            $conn      = $this->query->getConnection();
            $alias     = $this->query->getTableAlias($reference);
            $map       = $this->query->getAliasDeclaration($reference);
            $table     = $map['table'];
            // check if value is enumerated value
            $enumIndex = $table->enumIndex($field, trim($value, "'"));

            if (false !== $enumIndex && $conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                $enumIndex = $conn->quote($enumIndex, 'text');
            }

            // FIX: Issues with "(" XXX ")"
            if ($hasRightAggExpression) {
                $value = '(' . $value . ')';
            }

            if (substr($value, 0, 1) == '(') {
                // trim brackets
                $trimmed   = $this->_tokenizer->bracketTrim($value);

                if (substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {
                    // subquery found
                    $q = $this->query->createSubquery();

                    // Change due to bug "(" XXX ")"
                    //$value = '(' . $q->parseQuery($trimmed)->getQuery() . ')';
                    $value = $q->parseQuery($trimmed)->getQuery();
                } elseif (substr($trimmed, 0, 4) == 'SQL:') {
                    // Change due to bug "(" XXX ")"
                    //$value = '(' . substr($trimmed, 4) . ')';
                    $value = substr($trimmed, 4);
                } else {
                    // simple in expression found
                    $e     = $this->_tokenizer->sqlExplode($trimmed, ',');

                    $value = array();
                    foreach ($e as $part) {
                        $index = $table->enumIndex($field, trim($part, "'"));

                        if (false !== $index && $conn->getAttribute(IPF_ORM::ATTR_USE_NATIVE_ENUM)) {
                            $index = $conn->quote($index, 'text');
                        }

                        if ($index !== false) {
                            $value[] = $index;
                        } else {
                            $value[] = $this->parseLiteralValue($part);
                        }
                    }

                    // Change due to bug "(" XXX ")"
                    //$value = '(' . implode(', ', $value) . ')';
                    $value = implode(', ', $value);
                }
            } else {
                if ($enumIndex !== false) {
                    $value = $enumIndex;
                } else {
                    $value = $this->parseLiteralValue($value);
                }
            }

            switch ($operator) {
                case '<':
                case '>':
                case '=':
                case '!=':
                    if ($enumIndex !== false) {
                        $value  = $enumIndex;
                    }
                default:
                    $leftExpr = (($hasLeftAggExpression) ? $leftMatches[1] . '(' : '') 
                              . $alias . '.' . $field
                              . (($hasLeftAggExpression) ? $leftMatches[3] . ')' : '') ;

                    $rightExpr = (($hasRightAggExpression) ? $rightMatches[1] . '(' : '') 
                              . $value
                              . (($hasRightAggExpression) ? $rightMatches[3] . ')' : '') ;

                    $condition  = $leftExpr . ' ' . $operator . ' ' . $rightExpr;
            }

        }

        return $condition;
    }
}
