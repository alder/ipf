<?php

class IPF_ORM_Validator_Unique
{
    public function validate($value)
    {
        $table = $this->invoker->getTable();
        $pks = $table->getIdentifier();

        if ( is_array($pks) ) {
            $pks = join(',', $pks);
        }

        $sql   = 'SELECT ' . $pks . ' FROM ' . $table->getTableName() . ' WHERE ' . $this->field . ' = ?';
        
        $values = array();
        $values[] = $value;
        
        // If the record is not new we need to add primary key checks because its ok if the 
        // unique value already exists in the database IF the record in the database is the same
        // as the one that is validated here.
        $state = $this->invoker->state();
        if ( ! ($state == IPF_ORM_Record::STATE_TDIRTY || $state == IPF_ORM_Record::STATE_TCLEAN)) {
            foreach ((array) $table->getIdentifier() as $pk) {
                $sql .= " AND {$pk} != ?";
                $values[] = $this->invoker->$pk;
            }
        }
        
        $stmt  = $table->getConnection()->getDbh()->prepare($sql);
        $stmt->execute($values);

        return ( ! is_array($stmt->fetch()));
    }
}