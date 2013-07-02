<?php

class IPF_ORM_Template_Orderable extends IPF_ORM_Template
{
    private $columnName = 'ord';
    private $exclude = true;

    public function __construct(array $options=array())
    {
        if ($options) {
            if (array_key_exists('name', $options))
                $this->columnName = $options['name'];
            if (array_key_exists('exclude', $options))
                $this->exclude = $options['exclude'];
        }
    }

    public function getColumnName()
    {
        return $this->columnName;
    }

    public function setTableDefinition()
    {
        $this->hasColumn($this->columnName, 'integer', null, array('exclude' => $this->exclude));
        $this->getTable()->listeners['Orderable_'.$this->columnName] = new IPF_ORM_Template_Listener_Orderable($this->columnName);
    }
}

