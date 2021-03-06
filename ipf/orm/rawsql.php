<?php

class IPF_ORM_RawSql extends IPF_ORM_Query_Abstract
{
    private $fields = array();

    public function parseDqlQueryPart($queryPartName, $queryPart, $append=false)
    {
        if ($queryPartName == 'select') {
            $this->_parseSelectFields($queryPart, $append);
            return $this;
        }

        if (!isset($this->parts[$queryPartName])) {
            $this->_sqlParts[$queryPartName] = array();
        }

        if (!$append) {
            $this->_sqlParts[$queryPartName] = array($queryPart);
        } else {
            $this->_sqlParts[$queryPartName][] = $queryPart;
        }
        return $this;
    }

    protected function _addDqlQueryPart($queryPartName, $queryPart, $append=false)
    {
        return $this->parseDqlQueryPart($queryPartName, $queryPart, $append);
    }

    private function _parseSelectFields($queryPart, $append=false)
    {
        if ($append)
            $this->fields[] = $queryPart;
        else
            $this->fields = array($queryPart);

        $this->_sqlParts['select'] = array();
    }

    public function parseDqlQuery($query)
    {
        $this->_parseSelectFields($query);
        $this->clear();

        $tokens = $this->_tokenizer->sqlExplode($query, ' ');

        $parts = array();
        foreach ($tokens as $key => $part) {
            $partLowerCase = strtolower($part);
            switch ($partLowerCase) {
                case 'select':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $type = $partLowerCase;
                    if ( ! isset($parts[$partLowerCase])) {
                        $parts[$partLowerCase] = array();
                    }
                    break;
                case 'order':
                case 'group':
                    $i = $key + 1;
                    if (isset($tokens[$i]) && strtolower($tokens[$i]) === 'by') {
                        $type = $partLowerCase . 'by';
                        $parts[$type] = array();
                    } else {
                        //not a keyword so we add it to the previous type
                        $parts[$type][] = $part;
                    }
                    break;
                case 'by':
                    continue;
                default:
                    //not a keyword so we add it to the previous type.
                    if ( ! isset($parts[$type][0])) {
                        $parts[$type][0] = $part;
                    } else {
                        // why does this add to index 0 and not append to the 
                        // array. If it had done that one could have used 
                        // parseQueryPart.
                        $parts[$type][0] .= ' '.$part;
                    }
            }
        }

        $this->_sqlParts = $parts;
        $this->_sqlParts['select'] = array();

        return $this;
    }

    public function getSqlQuery($params=array())
    {
        $select = array();

        foreach ($this->fields as $field) {
            if (preg_match('/^{([^}{]+)\.([^}{]+)}$/U', $field, $e)) {
                // try to auto-add component
                if (!$this->hasSqlTableAlias($e[1])) {
                    try {
                        $this->addComponent($e[1], ucwords($e[1]));
                    } catch (IPF_ORM_Exception $exception) {
                        throw new IPF_ORM_Exception('The associated component for table alias ' . $e[1] . ' couldn\'t be found.');
                    }
                }

                $componentAlias = $this->getComponentAlias($e[1]);
                
                if ($e[2] == '*') {
                    foreach ($this->_queryComponents[$componentAlias]['table']->getColumnNames() as $name) {
                        $field = $e[1] . '.' . $name;

                        $select[$componentAlias][$field] = $field . ' AS ' . $e[1] . '__' . $name;
                    }
                } else {
                    $field = $e[1] . '.' . $e[2];
                    $select[$componentAlias][$field] = $field . ' AS ' . $e[1] . '__' . $e[2];
                }
            } else {
                $select['__raw__'][] = $field;
            }
        }

        // force-add all primary key fields

        foreach ($this->getTableAliasMap() as $tableAlias => $componentAlias) {
            $map = $this->_queryComponents[$componentAlias];

            foreach ((array) $map['table']->getIdentifierColumnNames() as $key) {
                $field = $tableAlias . '.' . $key;

                if ( ! isset($this->_sqlParts['select'][$field])) {
                    $select[$componentAlias][$field] = $field . ' AS ' . $tableAlias . '__' . $key;
                }
            }
        }
        
        // first add the fields of the root component
        reset($this->_queryComponents);
        $componentAlias = key($this->_queryComponents);

        $q = 'SELECT ' . implode(', ', $select[$componentAlias]);
        unset($select[$componentAlias]);

        foreach ($select as $component => $fields) {
            if ( ! empty($fields)) {
                $q .= ', ' . implode(', ', $fields);
            }
        }

        $string = $this->getInheritanceCondition($this->getRootAlias());
        if ( ! empty($string)) {
            $this->_sqlParts['where'][] = $string;
        }
        $copy = $this->_sqlParts;
        unset($copy['select']);

        $q .= ( ! empty($this->_sqlParts['from']))?    ' FROM '     . implode(' ', $this->_sqlParts['from']) : '';
        $q .= ( ! empty($this->_sqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_sqlParts['where']) : '';
        $q .= ( ! empty($this->_sqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_sqlParts['groupby']) : '';
        $q .= ( ! empty($this->_sqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_sqlParts['having']) : '';
        $q .= ( ! empty($this->_sqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_sqlParts['orderby']) : '';
        $q .= ( ! empty($this->_sqlParts['limit']))?   ' LIMIT ' . implode(' ', $this->_sqlParts['limit']) : '';
        $q .= ( ! empty($this->_sqlParts['offset']))?  ' OFFSET ' . implode(' ', $this->_sqlParts['offset']) : '';

        if ( ! empty($string)) {
            array_pop($this->_sqlParts['where']);
        }
        return $q;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function addComponent($tableAlias, $path)
    {
        $tmp           = explode(' ', $path);
        $originalAlias = (count($tmp) > 1) ? end($tmp) : null;

        $e = explode('.', $tmp[0]);

        $fullPath = $tmp[0];
        $fullLength = strlen($fullPath);

        $table = null;

        $currPath = '';

        if (isset($this->_queryComponents[$e[0]])) {
            $table = $this->_queryComponents[$e[0]]['table'];
            $currPath = $parent = array_shift($e);
        }

        foreach ($e as $k => $component) {
            // get length of the previous path
            $length = strlen($currPath);

            // build the current component path
            $currPath = ($currPath) ? $currPath . '.' . $component : $component;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($currPath) === $fullLength && isset($originalAlias)) {
                $componentAlias = $originalAlias;
            } else {
                $componentAlias = $currPath;
            }

            if (!isset($table)) {
                $conn = IPF_ORM_Manager::getInstance()
                        ->getConnectionForComponent($component);

                $table = $conn->getTable($component);
                $this->_queryComponents[$componentAlias] = array('table' => $table);
            } else {
                $relation = $table->getRelation($component);

                $this->_queryComponents[$componentAlias] = array('table'    => $relation->getTable(),
                                                                 'parent'   => $parent,
                                                                 'relation' => $relation);
            }
            $this->addSqlTableAlias($tableAlias, $componentAlias);

            $parent = $currPath;
        }

        return $this;
    }
}

