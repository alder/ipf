<?php

class IPF_ORM_Expression_Mysql extends IPF_ORM_Expression_Driver
{
    public function regexp()
    {
        return 'RLIKE';
    }

    public function random()
    {
        return 'RAND()';
    }

    public function guid()
    {
        return 'UUID()';
    }

    public function year($column)
    {
        $column = $this->getIdentifier($column);
        return 'YEAR(' .  $column . ')';
    }

    public function month($column)
    {
        $column = $this->getIdentifier($column);
        return 'MONTH(' .  $column . ')';
    }

    public function monthname($column)
    {
        $column = $this->getIdentifier($column);
        return 'MONTHNAME(' .  $column . ')';
    }

    public function day($column)
    {
        $column = $this->getIdentifier($column);
        return 'DAY(' .  $column . ')';
    }
}
