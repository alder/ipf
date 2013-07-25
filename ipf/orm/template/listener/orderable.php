<?php

class IPF_ORM_Template_Listener_Orderable
{
    private $columnName, $prepend;

    public function __construct($columnName, $prepend)
    {
        $this->columnName = $columnName;
        $this->prepend = $prepend;
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
        if ($obj->$columnName !== null)
            return;

        if ($this->prepend) {
            $f = 'min';
            $d = '-';
        } else {
            $f = 'max';
            $d = '+';
        }

        $res = IPF_ORM_Query::create()
            ->select('coalesce('.$f.'('.$this->columnName.') '.$d.' 1, 1) as x_ord')
            ->from(get_class($obj))
            ->execute();

        $obj->$columnName = (int)$res[0]->x_ord;
    }
}

