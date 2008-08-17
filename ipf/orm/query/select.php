<?php

class IPF_ORM_Query_Select extends IPF_ORM_Query_Part
{
    public function parse($dql) 
    {
        $this->query->parseSelect($dql);
    }
}