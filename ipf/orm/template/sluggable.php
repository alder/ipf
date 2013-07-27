<?php

class IPF_ORM_Template_Sluggable extends IPF_ORM_Template
{
    protected $_options = array('name'          =>  'slug',
                                'type'          =>  'string',
                                'length'        =>  255,
                                'unique'        =>  true,
                                'options'       =>  array(),
                                'fields'        =>  array(),
                                'uniqueBy'      =>  array(),
                                'uniqueIndex'   =>  true,
                                'canUpdate'     =>  false,
                                'builder'       =>  array('IPF_ORM_Inflector', 'urlize'),
                                'indexName'     =>  'sluggable'
    );

    public function __construct(array $options = array())
    {
        $this->_options = IPF_ORM_Utils::arrayDeepMerge($this->_options, $options);
    }

    public function setTableDefinition()
    {
        $table = $this->getTable();

        $table->setColumn($this->_options['name'], $this->_options['type'], $this->_options['length'], $this->_options['options']);

        if ($this->_options['unique'] == true && $this->_options['uniqueIndex'] == true && !empty($this->_options['fields'])) {
            $indexFields = array($this->_options['name']);
            $indexFields = array_merge($indexFields, $this->_options['uniqueBy']);
            $table->addIndex($this->_options['indexName'], array('fields' => $indexFields, 'type' => 'unique'));
        }
        $table->listeners['Sluggable_'.print_r($this->_options, true)] = new IPF_ORM_Template_Listener_Sluggable($this->_options);
    }
}

