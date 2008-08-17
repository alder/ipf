<?php

class IPF_ORM_Exception_Mysql extends IPF_ORM_Exception_Connection
{
    protected static $errorCodeMap = array(
                                      1004 => IPF_ORM::ERR_CANNOT_CREATE,
                                      1005 => IPF_ORM::ERR_CANNOT_CREATE,
                                      1006 => IPF_ORM::ERR_CANNOT_CREATE,
                                      1007 => IPF_ORM::ERR_ALREADY_EXISTS,
                                      1008 => IPF_ORM::ERR_CANNOT_DROP,
                                      1022 => IPF_ORM::ERR_ALREADY_EXISTS,
                                      1044 => IPF_ORM::ERR_ACCESS_VIOLATION,
                                      1046 => IPF_ORM::ERR_NODBSELECTED,
                                      1048 => IPF_ORM::ERR_CONSTRAINT,
                                      1049 => IPF_ORM::ERR_NOSUCHDB,
                                      1050 => IPF_ORM::ERR_ALREADY_EXISTS,
                                      1051 => IPF_ORM::ERR_NOSUCHTABLE,
                                      1054 => IPF_ORM::ERR_NOSUCHFIELD,
                                      1061 => IPF_ORM::ERR_ALREADY_EXISTS,
                                      1062 => IPF_ORM::ERR_ALREADY_EXISTS,
                                      1064 => IPF_ORM::ERR_SYNTAX,
                                      1091 => IPF_ORM::ERR_NOT_FOUND,
                                      1100 => IPF_ORM::ERR_NOT_LOCKED,
                                      1136 => IPF_ORM::ERR_VALUE_COUNT_ON_ROW,
                                      1142 => IPF_ORM::ERR_ACCESS_VIOLATION,
                                      1146 => IPF_ORM::ERR_NOSUCHTABLE,
                                      1216 => IPF_ORM::ERR_CONSTRAINT,
                                      1217 => IPF_ORM::ERR_CONSTRAINT,
                                      1451 => IPF_ORM::ERR_CONSTRAINT,
                                      );
    public function processErrorInfo(array $errorInfo)
    {
        $code = $errorInfo[1];
        if (isset(self::$errorCodeMap[$code])) {
            $this->portableCode = self::$errorCodeMap[$code];
            return true;
        }
        return false;
    }
}
