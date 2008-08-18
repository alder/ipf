<?php

class IPF_ORM_Template_Sluggable extends IPF_ORM_Template{
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
        $this->hasColumn($this->_options['name'], $this->_options['type'], $this->_options['length'], $this->_options['options']);
        
        if ($this->_options['unique'] == true && $this->_options['uniqueIndex'] == true && ! empty($this->_options['fields'])) {
            $indexFields = array($this->_options['name']);
            $indexFields = array_merge($indexFields, $this->_options['uniqueBy']);
            $this->index($this->_options['indexName'], array('fields' => $indexFields,
                                                             'type' => 'unique'));
        }
        $this->addListener(new IPF_ORM_Template_Listener_Sluggable($this->_options));
    }
}