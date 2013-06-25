<?php

class IPF_ORM_Exception extends IPF_Exception
{
    protected static $_errorMessages = array(
        IPF_ORM::ERR                    => 'unknown error',
        IPF_ORM::ERR_ALREADY_EXISTS     => 'already exists',
        IPF_ORM::ERR_CANNOT_CREATE      => 'can not create',
        IPF_ORM::ERR_CANNOT_ALTER       => 'can not alter',
        IPF_ORM::ERR_CANNOT_REPLACE     => 'can not replace',
        IPF_ORM::ERR_CANNOT_DELETE      => 'can not delete',
        IPF_ORM::ERR_CANNOT_DROP        => 'can not drop',
        IPF_ORM::ERR_CONSTRAINT         => 'constraint violation',
        IPF_ORM::ERR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
        IPF_ORM::ERR_DIVZERO            => 'division by zero',
        IPF_ORM::ERR_INVALID            => 'invalid',
        IPF_ORM::ERR_INVALID_DATE       => 'invalid date or time',
        IPF_ORM::ERR_INVALID_NUMBER     => 'invalid number',
        IPF_ORM::ERR_MISMATCH           => 'mismatch',
        IPF_ORM::ERR_NODBSELECTED       => 'no database selected',
        IPF_ORM::ERR_NOSUCHFIELD        => 'no such field',
        IPF_ORM::ERR_NOSUCHTABLE        => 'no such table',
        IPF_ORM::ERR_NOT_CAPABLE        => 'IPF backend not capable',
        IPF_ORM::ERR_NOT_FOUND          => 'not found',
        IPF_ORM::ERR_NOT_LOCKED         => 'not locked',
        IPF_ORM::ERR_SYNTAX             => 'syntax error',
        IPF_ORM::ERR_UNSUPPORTED        => 'not supported',
        IPF_ORM::ERR_VALUE_COUNT_ON_ROW => 'value count on row',
        IPF_ORM::ERR_INVALID_DSN        => 'invalid DSN',
        IPF_ORM::ERR_CONNECT_FAILED     => 'connect failed',
        IPF_ORM::ERR_NEED_MORE_DATA     => 'insufficient data supplied',
        IPF_ORM::ERR_EXTENSION_NOT_FOUND=> 'extension not found',
        IPF_ORM::ERR_NOSUCHDB           => 'no such database',
        IPF_ORM::ERR_ACCESS_VIOLATION   => 'insufficient permissions',
        IPF_ORM::ERR_LOADMODULE         => 'error while including on demand module',
        IPF_ORM::ERR_TRUNCATED          => 'truncated',
        IPF_ORM::ERR_DEADLOCK           => 'deadlock detected',
    );

    public function errorMessage($value = null)
    {
        if (is_null($value)) {
            return self::$_errorMessages;
        }

        return isset(self::$_errorMessages[$value]) ?
           self::$_errorMessages[$value] : self::$_errorMessages[IPF_ORM::ERR];
    }
}

