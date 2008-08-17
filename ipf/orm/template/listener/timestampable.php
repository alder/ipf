<?php

class IPF_ORM_Template_Listener_Timestampable extends IPF_ORM_Record_Listener
{
    protected $_options = array();
    public function __construct(array $options)
    {
        $this->_options = $options;
    }

    public function preInsert(IPF_ORM_Event $event)
    {
        if( ! $this->_options['created']['disabled']) {
            $createdName = $this->_options['created']['name'];
            $event->getInvoker()->$createdName = $this->getTimestamp('created');
        }

        if( ! $this->_options['updated']['disabled'] && $this->_options['updated']['onInsert']) {
            $updatedName = $this->_options['updated']['name'];
            $event->getInvoker()->$updatedName = $this->getTimestamp('updated');
        }
    }

    public function preUpdate(IPF_ORM_Event $event)
    {
        if( ! $this->_options['updated']['disabled']) {
            $updatedName = $this->_options['updated']['name'];
            $event->getInvoker()->$updatedName = $this->getTimestamp('updated');
        }
    }

    public function getTimestamp($type)
    {
        $options = $this->_options[$type];

        if ($options['expression'] !== false && is_string($options['expression'])) {
            return new IPF_ORM_Expression($options['expression']);
        } else {
            if ($options['type'] == 'date') {
                return date($options['format'], time());
            } else if ($options['type'] == 'timestamp') {
                return date($options['format'], time());
            } else {
                return time();
            }
        }
    }
}