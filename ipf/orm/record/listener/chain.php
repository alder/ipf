<?php

class IPF_ORM_Record_Listener_Chain extends IPF_ORM_Access implements IPF_ORM_Record_Listener_Interface
{
    protected $_listeners = array();

    public function add($listener, $name = null)
    {
        if ( ! ($listener instanceof IPF_ORM_Record_Listener_Interface) &&
             ! ($listener instanceof IPF_ORM_Overloadable)) {
            throw new IPF_Exception_ORM("Couldn't add eventlistener. Record listeners should implement either IPF_ORM_Record_Listener_Interface or IPF_ORM_Overloadable");
        }
        if ($name === null) {
            $this->_listeners[] = $listener;
        } else {
            $this->_listeners[$name] = $listener;
        }
    }

    public function get($key)
    {
        if ( ! isset($this->_listeners[$key])) {
            return null;
        }
        return $this->_listeners[$key];
    }

    public function set($key, $listener)
    {
        $this->_listeners[$key] = $listener;
    }

    public function preSerialize(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preSerialize($event);
        }
    }

    public function postSerialize(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preSerialize($event);
        }
    }

    public function preUnserialize(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preUnserialize($event);
        }
    }

    public function postUnserialize(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postUnserialize($event);
        }
    }

    public function preDqlSelect(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preDqlSelect($event);
        }
    }

    public function preSave(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preSave($event);
        }
    }

    public function postSave(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postSave($event);
        }
    }

    public function preDqlDelete(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preDqlDelete($event);
        }
    }

    public function preDelete(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preDelete($event);
        }
    }

    public function postDelete(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postDelete($event);
        }
    }

    public function preDqlUpdate(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preDqlUpdate($event);
        }
    }

    public function preUpdate(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preUpdate($event);
        }
    }

    public function postUpdate(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postUpdate($event);
        }
    }

    public function preInsert(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preInsert($event);
        }
    }

    public function postInsert(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postInsert($event);
        }
    }

    public function preHydrate(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->preHydrate($event);
        }
    }

    public function postHydrate(IPF_ORM_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postHydrate($event);
        }
    }
}
