<?php

class IPF_ORM_Transaction extends IPF_ORM_Connection_Module
{
    const STATE_SLEEP       = 0;
    const STATE_ACTIVE      = 1;
    const STATE_BUSY        = 2;

    protected $_nestingLevel = 0;
    protected $_internalNestingLevel = 0;
    protected $invalid          = array();
    protected $savePoints       = array();
    protected $_collections     = array();

    public function addCollection(IPF_ORM_Collection $coll)
    {
        $this->_collections[] = $coll;

        return $this;
    }

    public function getState()
    {
        switch ($this->_nestingLevel) {
            case 0:
                return IPF_ORM_Transaction::STATE_SLEEP;
                break;
            case 1:
                return IPF_ORM_Transaction::STATE_ACTIVE;
                break;
            default:
                return IPF_ORM_Transaction::STATE_BUSY;
        }
    }

    public function addInvalid(IPF_ORM_Record $record)
    {
        if (in_array($record, $this->invalid, true)) {
            return false;
        }
        $this->invalid[] = $record;
        return true;
    }

    public function getInvalid()
    {
        return $this->invalid;
    }

    public function getTransactionLevel()
    {
        return $this->_nestingLevel;
    }
    
    public function getInternalTransactionLevel()
    {
        return $this->_internalNestingLevel;
    }

    public function beginTransaction($savepoint = null)
    {
        $this->conn->connect();

        if ( ! is_null($savepoint)) {
            $this->savePoints[] = $savepoint;

            $event = new IPF_ORM_Event($this, IPF_ORM_Event::SAVEPOINT_CREATE);

            $this->conn->notifyDBListeners('preSavepointCreate', $event);

            if ( ! $event->skipOperation) {
                $this->createSavePoint($savepoint);
            }

            $this->conn->notifyDBListeners('postSavepointCreate', $event);
        } else {
            if ($this->_nestingLevel == 0) {
                $event = new IPF_ORM_Event($this, IPF_ORM_Event::TX_BEGIN);

                $this->conn->notifyDBListeners('preTransactionBegin', $event);

                if ( ! $event->skipOperation) {
                    try {
                        $this->_doBeginTransaction();
                    } catch (Exception $e) {
                        throw new IPF_ORM_Exception($e->getMessage());
                    }
                }
                $this->conn->notifyDBListeners('postTransactionBegin', $event);
            }
        }

        $level = ++$this->_nestingLevel;

        return $level;
    }

    public function commit($savepoint = null)
    {
        if ($this->_nestingLevel == 0) {
            throw new IPF_ORM_Exception("Commit failed. There is no active transaction.");
        }
        
        $this->conn->connect();

        if ( ! is_null($savepoint)) {
            $this->_nestingLevel -= $this->removeSavePoints($savepoint);

            $event = new IPF_ORM_Event($this, IPF_ORM_Event::SAVEPOINT_COMMIT);

            $this->conn->notifyDBListeners('preSavepointCommit', $event);

            if ( ! $event->skipOperation) {
                $this->releaseSavePoint($savepoint);
            }

            $this->conn->notifyDBListeners('postSavepointCommit', $event);
        } else {
                 
            if ($this->_nestingLevel == 1 || $this->_internalNestingLevel == 1) {
                if ( ! empty($this->invalid)) {
                    if ($this->_internalNestingLevel == 1) {
                        // transaction was started by IPF_ORM, so we are responsible
                        // for a rollback
                        $this->rollback();
                        $tmp = $this->invalid;
                        $this->invalid = array();
                        throw new IPF_ORM_Exception_Validator($tmp);
                    }
                }
                if ($this->_nestingLevel == 1) {
                    // take snapshots of all collections used within this transaction
                    foreach ($this->_collections as $coll) {
                        $coll->takeSnapshot();
                    }
                    $this->_collections = array();

                    $event = new IPF_ORM_Event($this, IPF_ORM_Event::TX_COMMIT);

                    $this->conn->notifyDBListeners('preTransactionCommit', $event);
                    if ( ! $event->skipOperation) {
                        $this->_doCommit();
                    }
                    $this->conn->notifyDBListeners('postTransactionCommit', $event);
                }
            }
            
            if ($this->_nestingLevel > 0) {
                $this->_nestingLevel--;
            }            
            if ($this->_internalNestingLevel > 0) {
                $this->_internalNestingLevel--;
            } 
        }

        return true;
    }

    public function rollback($savepoint = null)
    {
        if ($this->_nestingLevel == 0) {
            throw new IPF_ORM_Exception("Rollback failed. There is no active transaction.");
        }
        
        $this->conn->connect();

        if ($this->_internalNestingLevel > 1 || $this->_nestingLevel > 1) {
            $this->_internalNestingLevel--;
            $this->_nestingLevel--;
            return false;
        }

        if ( ! is_null($savepoint)) {
            $this->_nestingLevel -= $this->removeSavePoints($savepoint);

            $event = new IPF_ORM_Event($this, IPF_ORM_Event::SAVEPOINT_ROLLBACK);

            $this->conn->notifyDBListeners('preSavepointRollback', $event);
            
            if ( ! $event->skipOperation) {
                $this->rollbackSavePoint($savepoint);
            }

            $this->conn->notifyDBListeners('postSavepointRollback', $event);
        } else {
            $event = new IPF_ORM_Event($this, IPF_ORM_Event::TX_ROLLBACK);
    
            $this->conn->notifyDBListeners('preTransactionRollback', $event);
            
            if ( ! $event->skipOperation) {
                $this->_nestingLevel = 0;
                $this->_internalNestingLevel = 0;
                try {
                    $this->_doRollback();
                } catch (Exception $e) {
                    throw new IPF_ORM_Exception($e->getMessage());
                }
            }

            $this->conn->notifyDBListeners('postTransactionRollback', $event);
        }

        return true;
    }

    protected function createSavePoint($savepoint)
    {
        throw new IPF_ORM_Exception('Savepoints not supported by this driver.');
    }

    protected function releaseSavePoint($savepoint)
    {
        throw new IPF_ORM_Exception('Savepoints not supported by this driver.');
    }

    protected function rollbackSavePoint($savepoint)
    {
        throw new IPF_ORM_Exception('Savepoints not supported by this driver.');
    }
    
    protected function _doRollback()
    {
        $this->conn->getDbh()->rollback();
    }
    
    protected function _doCommit()
    {
        $this->conn->getDbh()->commit();
    }
    
    protected function _doBeginTransaction()
    {
        $this->conn->getDbh()->beginTransaction();
    }

    private function removeSavePoints($savepoint)
    {
        $this->savePoints = array_values($this->savePoints);

        $found = false;
        $i = 0;

        foreach ($this->savePoints as $key => $sp) {
            if ( ! $found) {
                if ($sp === $savepoint) {
                    $found = true;
                }
            }
            if ($found) {
                $i++;
                unset($this->savePoints[$key]);
            }
        }

        return $i;
    }

    public function setIsolation($isolation)
    {
        throw new IPF_ORM_Exception('Transaction isolation levels not supported by this driver.');
    }

    public function getIsolation()
    {
        throw new IPF_ORM_Exception('Fetching transaction isolation level not supported by this driver.');
    }
    
    public function beginInternalTransaction($savepoint = null)
    {
        $this->_internalNestingLevel++;
        return $this->beginTransaction($savepoint);
    }
}
