<?php

class IPF_ORM_Connection_Statement implements IPF_ORM_Adapter_Statement_Interface
{
    protected $_conn;
    protected $_stmt;
    public function __construct(IPF_ORM_Connection $conn, $stmt)
    {
        $this->_conn = $conn;
        $this->_stmt = $stmt;

        if ($stmt === false) {
            throw new IPF_ORM_Exception('Unknown statement object given.');
        }
    }

    public function getConnection()
    {
        return $this->_conn;
    }
    public function getStatement()
    {
        return $this->_stmt;
    }
    public function getQuery()
    {
        return $this->_stmt->queryString;
    }

    public function bindColumn($column, $param, $type = null)
    {
        if ($type === null) {
            return $this->_stmt->bindColumn($column, $param);
        } else {
            return $this->_stmt->bindColumn($column, $param, $type);
        }
    }

    public function bindValue($param, $value, $type = null)
    {
        if ($type === null) {
            return $this->_stmt->bindValue($param, $value);
        } else {
            return $this->_stmt->bindValue($param, $value, $type);
        }
    }

    public function bindParam($column, &$variable, $type = null, $length = null, $driverOptions = array())
    {
        if ($type === null) {
            return $this->_stmt->bindParam($column, $variable);
        } else {
            return $this->_stmt->bindParam($column, $variable, $type, $length, $driverOptions);
        }
    }

    public function closeCursor()
    {
        return $this->_stmt->closeCursor();
    }

    public function columnCount()
    {
        return $this->_stmt->columnCount();
    }

    public function errorCode()
    {
        return $this->_stmt->errorCode();
    }

    public function errorInfo()
    {
        return $this->_stmt->errorInfo();
    }

    public function execute($params = null)
    {
        try {
            $event = new IPF_ORM_Event($this, IPF_ORM_Event::STMT_EXECUTE, $this->getQuery(), $params);
            $this->_conn->notifyDBListeners('preStmtExecute', $event);

            $result = true;
            if ( ! $event->skipOperation) {
                $result = $this->_stmt->execute($params);
                $this->_conn->incrementQueryCount();
            }

            $this->_conn->notifyDBListeners('postStmtExecute', $event);

            return $result;
        } catch (PDOException $e) {
        } catch (IPF_ORM_Exception_Adapter $e) {
        }

        $this->_conn->rethrowException($e, $this);

        return false;
    }

    public function fetch($fetchMode = IPF_ORM::FETCH_BOTH,
                          $cursorOrientation = IPF_ORM::FETCH_ORI_NEXT,
                          $cursorOffset = null)
    {
        $event = new IPF_ORM_Event($this, IPF_ORM_Event::STMT_FETCH, $this->getQuery());

        $event->fetchMode = $fetchMode;
        $event->cursorOrientation = $cursorOrientation;
        $event->cursorOffset = $cursorOffset;

        $data = $this->_conn->notifyDBListeners('preFetch', $event);

        if ( ! $event->skipOperation) {
            $data = $this->_stmt->fetch($fetchMode, $cursorOrientation, $cursorOffset);
        }

        $this->_conn->notifyDBListeners('postFetch', $event);

        return $data;
    }

    public function fetchAll($fetchMode = IPF_ORM::FETCH_BOTH,
                             $columnIndex = null)
    {
        $event = new IPF_ORM_Event($this, IPF_ORM_Event::STMT_FETCHALL, $this->getQuery());
        $event->fetchMode = $fetchMode;
        $event->columnIndex = $columnIndex;

        $this->_conn->notifyDBListeners('preFetchAll', $event);

        if ( ! $event->skipOperation) {
            if ($columnIndex !== null) {
                $data = $this->_stmt->fetchAll($fetchMode, $columnIndex);
            } else {
                $data = $this->_stmt->fetchAll($fetchMode);
            }

            $event->data = $data;
        }

        $this->_conn->notifyDBListeners('postFetchAll', $event);

        return $data;
    }

    public function fetchColumn($columnIndex = 0)
    {
        return $this->_stmt->fetchColumn($columnIndex);
    }

    public function fetchObject($className = 'stdClass', $args = array())
    {
        return $this->_stmt->fetchObject($className, $args);
    }

    public function getAttribute($attribute)
    {
        return $this->_stmt->getAttribute($attribute);
    }

    public function getColumnMeta($column)
    {
        return $this->_stmt->getColumnMeta($column);
    }

    public function nextRowset()
    {
        return $this->_stmt->nextRowset();
    }

    public function rowCount()
    {
        return $this->_stmt->rowCount();
    }

    public function setAttribute($attribute, $value)
    {
        return $this->_stmt->setAttribute($attribute, $value);
    }

    public function setFetchMode($mode, $arg1 = null, $arg2 = null)
    {
        return $this->_stmt->setFetchMode($mode, $arg1, $arg2);
    }
}

