<?php

class IPF_ORM_Template_Listener_Owned
{
    private $columnName;

    public function __construct($columnName)
    {
        $this->columnName = $columnName;
    }

    public function preInsert(IPF_ORM_Event $event)
    {
        $this->setOrderValue($event->getInvoker());
    }

    public function preUpdate(IPF_ORM_Event $event)
    {
        $this->setOrderValue($event->getInvoker());
    }

    private function setOrderValue($obj)
    {
        $columnName = $this->columnName;
        if ($obj->$columnName)
            return;

        $request = IPF_Project::getInstance()->request;
        if ($request && !$request->user->isAnonymous()) {
            $obj->$columnName = $request->user->id;
        }
    }
}

