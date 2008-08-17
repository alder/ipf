<?php

final class IPF_ORM_Null
{ 
    public function exists()
    {
        return false;    
    }
    public function __toString()
    {
        return '';
    }
}