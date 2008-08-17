<?php

abstract class IPF_ORM_Hydrator_Abstract extends IPF_ORM_Locator_Injectable
{
    protected $_queryComponents = array();

    protected $_hydrationMode = IPF_ORM::HYDRATE_RECORD;

    public function __construct() {}

    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrationMode = $hydrationMode;
    }

    public function getHydrationMode()
    {
        return $this->_hydrationMode;
    }

    public function setQueryComponents(array $queryComponents)
    {
        $this->_queryComponents = $queryComponents;
    }

    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }

    abstract public function hydrateResultSet($stmt, $tableAliases);
}