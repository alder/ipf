<?php

class IPF_ORM_Query_Set extends IPF_ORM_Query_Part
{
    public function parse($dql)
    {
    	$terms = $this->_tokenizer->sqlExplode($dql, ' ');
    	foreach ($terms as $term) {
            preg_match_all("/[a-z0-9_]+\.[a-z0-9_]+[\.[a-z0-9]+]*/i", $term, $m);
            
            if (isset($m[0])) {
                foreach ($m[0] as $part) {
                    $e = explode('.', trim($part));
                    $field = array_pop($e);
        
                    $reference = implode('.', $e);
        
                    $alias = $this->query->getTableAlias($reference);
                    $map   = $this->query->getAliasDeclaration($reference);
        
                    $dql = str_replace($part, $map['table']->getColumnName($field), $dql);
                }
            }
        }
        return $dql;
    }
}