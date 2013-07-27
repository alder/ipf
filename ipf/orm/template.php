<?php

abstract class IPF_ORM_Template
{
    protected $_invoker;

    public function setInvoker(IPF_ORM_Record $invoker)
    {
        $this->_invoker = $invoker;
    }

    public function getInvoker()
    {
        return $this->_invoker;
    }

    abstract public function setTableDefinition(IPF_ORM_Table $table)
}

