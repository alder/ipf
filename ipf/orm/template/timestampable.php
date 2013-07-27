<?php

class IPF_ORM_Template_Timestampable extends IPF_ORM_Template
{
    protected $_options = array(
        'created' => array(
            'name'        => 'created_at',
            'type'        => 'timestamp',
            'format'      => 'Y-m-d H:i:s',
            'disabled'    => false,
            'expression'  => false,
            'options'     => array('exclude' => true),
        ),
        'updated' => array(
            'name'        => 'updated_at',
            'type'        => 'timestamp',
            'format'      => 'Y-m-d H:i:s',
            'disabled'    => false,
            'expression'  => false,
            'onInsert'    => true,
            'exclude'     => true,
            'options'     => array('exclude' => true),
        ),
    );

    public function __construct(array $options = array())
    {
        $this->_options = IPF_ORM_Utils::arrayDeepMerge($this->_options, $options);
    }

    public function setTableDefinition()
    {
        if (!$this->_options['created']['disabled']) {
            $this->getTable()->setColumn($this->_options['created']['name'], $this->_options['created']['type'], null, $this->_options['created']['options']);
        }
        if (!$this->_options['updated']['disabled']) {
            $this->getTable()->setColumn($this->_options['updated']['name'], $this->_options['updated']['type'], null, $this->_options['updated']['options']);
        }
        $this->getTable()->listeners['Timestampable_'.print_r($this->_options, true)] = new IPF_ORM_Template_Listener_Timestampable($this->_options);
    }
}

