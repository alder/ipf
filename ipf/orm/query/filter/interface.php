<?php

interface IPF_ORM_Query_Filter_Interface
{
    public function preQuery(IPF_ORM_Query $query);
    public function postQuery(IPF_ORM_Query $query);
}