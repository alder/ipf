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
        $table = $this->getTable();
        $table->setColumn($this->columnName, 'integer', null, array('exclude' => $this->exclude));
        $table->addIndex($table->getOption('tableName') . '_orderable_' . $this->columnName, array('fields' => array($this->columnName)));
        $table->listeners['Orderable_'.$this->columnName] = new IPF_ORM_Template_Listener_Orderable($this->columnName, $this->prepend);
    }
}

