<?php

class IPF_ORM_Query_Limit extends IPF_ORM_Query_Part
{
    public function parse($limit) 
    {
        return (int) $limit;
    }
}