<?php

class IPF_ORM_Template_Orderable extends IPF_ORM_Template
{
    private $columnName = 'ord';
    private $exclude = true;
    private $prepend = false;

    public function __construct(array $options=array())
    {
        if ($options) {
            if (array_key_exists('name', $options))
                $this->columnName = $options['name'];
            if (array_key_exists('exclude', $options))
                $this->exclude = $options['exclude'];
            if (array_key_exists('prepend', $options))
                $this->prepend = $options['prepend'];
        }
    }

    public function getColumnName()
    {
        return $this->columnName;
    }

    public function setTableDefinition()
    {
        $this->getTable()->setColumn($this->columnName, 'integer', null, array('exclude' => $this->exclude));
        $this->index($this->getTable()->getOption('tableName') . '_orderable_' . $this->columnName, array('fields' => array($this->columnName)));
        $this->getTable()->listeners['Orderable_'.$this->columnName] = new IPF_ORM_Template_Listener_Orderable($this->columnName, $this->prepend);
    }
}

