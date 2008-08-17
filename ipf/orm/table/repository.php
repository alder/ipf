<?php

class IPF_ORM_Table_Repository implements Countable, IteratorAggregate
{
    private $table;
    private $registry = array();

    public function __construct(IPF_ORM_Table $table)
    {
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function add(IPF_ORM_Record $record)
    {
        $oid = $record->getOID();

        if (isset($this->registry[$oid])) {
            return false;
        }
        $this->registry[$oid] = $record;

        return true;
    }

    public function get($oid)
    {
        if ( ! isset($this->registry[$oid])) {
            throw new IPF_ORM_Exception("Unknown object identifier");
        }
        return $this->registry[$oid];
    }

    public function count()
    {
        return count($this->registry);
    }

    public function evict($oid)
    {
        if ( ! isset($this->registry[$oid])) {
            return false;
        }
        unset($this->registry[$oid]);
        return true;
    }

    public function evictAll()
    {
        $evicted = 0;
        foreach ($this->registry as $oid=>$record) {
            if ($this->evict($oid)) {
                $evicted++;
            }
        }
        return $evicted;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->registry);
    }

    public function contains($oid)
    {
        return isset($this->registry[$oid]);
    }

    public function loadAll()
    {
        $this->table->findAll();
    }
}