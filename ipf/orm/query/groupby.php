<?php

class IPF_ORM_Query_Groupby extends IPF_ORM_Query_Part
{
    public function parse($str, $append = false)
    {
        $r = array();
        foreach (explode(',', $str) as $reference) {
            $reference = trim($reference);

            $r[] = $this->query->parseClause($reference);
        }
        return implode(', ', $r);
    }
}
