<?php

class IPF_ORM_Query_Orderby extends IPF_ORM_Query_Part
{
    public function parse($str, $append = false)
    {
        $ret = array();

        foreach (explode(',', trim($str)) as $r) {
            $r = $this->query->parseClause($r);

            $ret[] = $r;
        }
        return $ret;
    }
}
