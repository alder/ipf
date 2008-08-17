<?php

interface IPF_ORM_Record_Listener_Interface
{
    public function preSerialize(IPF_ORM_Event $event);
    public function postSerialize(IPF_ORM_Event $event);
    public function preUnserialize(IPF_ORM_Event $event);
    public function postUnserialize(IPF_ORM_Event $event);
    public function preSave(IPF_ORM_Event $event);
    public function postSave(IPF_ORM_Event $event);
    public function preDelete(IPF_ORM_Event $event);
    public function postDelete(IPF_ORM_Event $event);
    public function preUpdate(IPF_ORM_Event $event);
    public function postUpdate(IPF_ORM_Event $event);
    public function preInsert(IPF_ORM_Event $event);
    public function postInsert(IPF_ORM_Event $event);
    public function preHydrate(IPF_ORM_Event $event);
    public function postHydrate(IPF_ORM_Event $event);
}
