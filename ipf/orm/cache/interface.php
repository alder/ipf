<?php

interface IPF_ORM_Cache_Interface 
{
    public function fetch($id, $testCacheValidity = true);
    public function contains($id);
    public function save($id, $data, $lifeTime = false);
    public function delete($id);
}
