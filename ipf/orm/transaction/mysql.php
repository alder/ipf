<?php

class IPF_ORM_Transaction_Mysql extends IPF_ORM_Transaction
{
    protected function createSavePoint($savepoint)
    {
        $query = 'SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }

    protected function releaseSavePoint($savepoint)
    {
        $query = 'RELEASE SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }

    protected function rollbackSavePoint($savepoint)
    {
        $query = 'ROLLBACK TO SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }

    public function setIsolation($isolation)
    {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
                break;
            default:
                throw new IPF_ORM_Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $isolation;

        return $this->conn->execute($query);
    }

    public function getIsolation()
    {
        return $this->conn->fetchOne('SELECT @@tx_isolation');
    }
}