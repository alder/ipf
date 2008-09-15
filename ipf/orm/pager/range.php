<?php

abstract class IPF_ORM_Pager_Range
{
    protected $_options;
    private $pager;

    final public function __construct($options = array(), $pager = null)
    {
        $this->_setOptions($options);

        if ($pager !== null) {
            $this->setPager($pager);
        }
    }

    public function getPager()
    {
        return $this->pager;
    }

    public function setPager($pager)
    {
        $this->pager = $pager;

        // Lazy-load initialization. It only should be called when all
        // needed information data is ready (this can only happens when we have
        // options stored and a IPF_ORM_Pager assocated)
        $this->_initialize();
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function getOption($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }

        throw new IPF_ORM_Exception(
            'Cannot access unexistent option \'' . $option . '\' in IPF_ORM_Pager_Range class'
        );
    }

    protected function _setOptions($options)
    {
        $this->_options = $options;
    }

    public function isInRange($page)
    {
        return (array_search($page, $this->rangeAroundPage()) !== false);
    }

    abstract protected function _initialize();

    abstract public function rangeAroundPage();
}
