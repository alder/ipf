<?php

class IPF_ORM_Template_Owned extends IPF_ORM_Template
{
    private $name = 'owner';
    private $columnName = 'owner_id';
    private $exclude = true;
    private $verbose = 'owner';

    public function __construct(array $options=array())
    {
        if ($options) {
            if (array_key_exists('column', $options))
                $this->columnName = $options['column'];
            if (array_key_exists('name', $options))
                $this->name = $options['name'];
            if (array_key_exists('exclude', $options))
                $this->exclude = $options['exclude'];
            if (array_key_exists('verbose', $options))
                $this->verbose = $options['verbose'];
        }
    }

    public function getColumnName()
    {
        return $this->columnName;
    }

    public function setTableDefinition(IPF_ORM_Table $table)
    {
        $table->setColumn($this->columnName, 'integer', null, array(
            'exclude'   => $this->exclude,
            'verbose'   => $this->verbose,
        ));
        $table->hasOne('User', $this->name, array(
            'local'     => $this->columnName,
            'exclude'   => $this->exclude,
            'foreign'   => 'id',
            'onDelete'  => 'CASCADE',
        ));
        $table->listeners['Owned_'.$this->columnName] = new IPF_ORM_Template_Listener_Owned($this->columnName);
    }
}

