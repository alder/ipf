<?php

abstract class IPF_ORM_Template extends IPF_ORM_Record_Abstract
{
    protected $_invoker;
    protected $_plugin;

    public function setTable(IPF_ORM_Table $table)
    {
        $this->_table = $table;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function setInvoker(IPF_ORM_Record $invoker)
    {
        $this->_invoker = $invoker;
    }

    public function getInvoker()
    {
        return $this->_invoker;
    }

    public function addChild(IPF_ORM_Template $template)
    {
        $this->_plugin->addChild($template);
        return $this;
    }

    public function getPlugin()
    {
        return $this->_plugin;
    }

    public function get($name) 
    {
        throw new IPF_ORM_Exception("Templates doesn't support accessors.");
    }

    public function set($name, $value)
    {
        throw new IPF_ORM_Exception("Templates doesn't support accessors.");
    }

    public function setUp()
    {
    }

    public function setTableDefinition()
    {
    }
}

