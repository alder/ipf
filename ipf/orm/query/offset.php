<?php

class IPF_ORM_Query_Offset extends IPF_ORM_Query_Part
{
    public function parse($offset)
    {
        return (int) $offset;
    }
}