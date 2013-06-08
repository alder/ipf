<?php

class IPF_ORM_Template_Listener_Orderable
{
    private $columnName = 'ord';

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

        $res = IPF_ORM_Query::create()
             ->select('max('.$this->columnName.') as x_ord')
             ->from(get_class($obj))
             ->execute();
        if (isset($res[0]->x_ord))
            $obj->$columnName = (int)$res[0]->x_ord + 1;
        else
            $obj->$columnName = 1;
    }
}

