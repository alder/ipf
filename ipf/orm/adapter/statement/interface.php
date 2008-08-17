<?php

interface IPF_ORM_Adapter_Statement_Interface
{
    public function bindColumn($column, $param, $type = null);
    public function bindValue($param, $value, $type = null);
    public function bindParam($column, &$variable, $type = null, $length = null, $driverOptions = array());
    public function closeCursor();
    public function columnCount();
    public function errorCode();
    public function errorInfo();
    public function execute($params = null);
    public function fetch($fetchStyle = IPF_ORM::FETCH_BOTH,
                          $cursorOrientation = IPF_ORM::FETCH_ORI_NEXT,
                          $cursorOffset = null);
    public function fetchAll($fetchStyle = IPF_ORM::FETCH_BOTH);
    public function fetchColumn($columnIndex = 0);
    public function fetchObject($className = 'stdClass', $args = array());
    public function getAttribute($attribute);
    public function getColumnMeta($column);
    public function nextRowset();
    public function rowCount();
    public function setAttribute($attribute, $value);
    public function setFetchMode($mode, $arg1 = null, $arg2 = null);
}