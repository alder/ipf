<?php

class IPF_ORM_Sequence_Mysql extends IPF_ORM_Sequence
{
    public function nextId($seqName, $onDemand = true)
    {
        $sequenceName  = $this->conn->quoteIdentifier($seqName, true);
        $seqcolName    = $this->conn->quoteIdentifier($this->conn->getAttribute(IPF_ORM::ATTR_SEQCOL_NAME), true);
        $query         = 'INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (NULL)';
        
        try {

            $this->conn->exec($query);

        } catch(IPF_ORM_Exception $e) {
            if ($onDemand && $e->getPortableCode() == IPF_ORM::ERR_NOSUCHTABLE) {
                // Since we are creating the sequence on demand
                // we know the first id = 1 so initialize the
                // sequence at 2
                try {
                    $result = $this->conn->export->createSequence($seqName, 2);
                } catch(IPF_ORM_Exception $e) {
                    throw new IPF_ORM_Exception('on demand sequence ' . $seqName . ' could not be created');
                }
                // First ID of a newly created sequence is 1
                return 1;
            }
            throw $e;
        }

        $value = $this->lastInsertId();

        if (is_numeric($value)) {
            $query = 'DELETE FROM ' . $sequenceName . ' WHERE ' . $seqcolName . ' < ' . $value;
            $this->conn->exec($query);
        }
        return $value;
    }

    public function lastInsertId($table = null, $field = null)
    {
        return $this->conn->getDbh()->lastInsertId();
    }

    public function currId($seqName)
    {
        $sequenceName   = $this->conn->quoteIdentifier($seqName, true);
        $seqcolName     = $this->conn->quoteIdentifier($this->conn->getAttribute(IPF_ORM::ATTR_SEQCOL_NAME), true);
        $query          = 'SELECT MAX(' . $seqcolName . ') FROM ' . $sequenceName;
        return (int) $this->conn->fetchOne($query);
    }
}