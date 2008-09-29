<?php

interface IPF_Server_Interface
{
    public function addFunction($function, $namespace = '');
    public function setClass($class, $namespace = '', $argv = null);
    public function fault($fault = null, $code = 404);
    public function handle($request = false);
    public function getFunctions();
    public function loadFunctions($definition);
    public function setPersistence($mode);
}
