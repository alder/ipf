<?php

class IPF_ORM_Template_Orderable extends IPF_ORM_Template
{
    private $columnName = 'ord';

    public function __construct(array $options=array())
    {
        if ($options && array_key_exists('name', $options))
            $this->columnName = $options['name'];
    }

    public function setTableDefinition()
    {
        $this->hasColumn($this->columnName, 'integer', null, '');
        $this->getTable()->listeners['Orderable_'.$this->columnName] = new IPF_ORM_Template_Listener_Orderable($this->columnName);
    }
}

