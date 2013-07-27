<?php

class IPF_ORM_Query extends IPF_ORM_Query_Abstract implements Countable, Serializable
{
    protected static $_keywords  = array('ALL',
                                         'AND',
                                         'ANY',
                                         'AS',
                                         'ASC',
                                         'AVG',
                                         'BETWEEN',
                                         'BIT_LENGTH',
                                         'BY',
                                         'CHARACTER_LENGTH',
                                         'CHAR_LENGTH',
                                         'CURRENT_DATE',
                                         'CURRENT_TIME',
                                         'CURRENT_TIMESTAMP',
                                         'DELETE',
                                         'DESC',
                                         'DISTINCT',
                                         'EMPTY',
                                         'EXISTS',
                                         'FALSE',
                                         'FETCH',
                                         'FROM',
                                         'GROUP',
                                         'HAVING',
                                         'IN',
                                         'INDEXBY',
                                         'INNER',
                                         'IS',
                                         'JOIN',
                                         'LEFT',
                                         'LIKE',
                                         'LOWER',
                                         'MEMBER',
                                         'MOD',
                                         'NEW',
                                         'NOT',
                                         'NULL',
                                         'OBJECT',
                                         'OF',
                                         'OR',
                                         'ORDER',
                                         'OUTER',
                                         'POSITION',
                                         'SELECT',
                                         'SOME',
                                         'TRIM',
                                         'TRUE',
                                         'UNKNOWN',
                                         'UPDATE',
                                         'WHERE');

    protected $_subqueryAliases = array();
    protected $_aggregateAliasMap      = array();
    protected $_pendingAggregates = array();
    protected $_needsSubquery = false;
    protected $_isSubquery;
    protected $_neededTables = array();
    protected $_pendingSubqueries = array();
    protected $_pendingFields = array();
    protected $_parsers = array();
    protected $_pendingJoinConditions = array();
    protected $_expressionMap = array();
    protected $_sql;

    public static function create($conn = null)
    {
        return new IPF_ORM_Query($conn);
    }

    public function reset()
    {
        $this->_pendingJoinConditions = array();
        $this->_pendingSubqueries = array();
        $this->_pendingFields = array();
        $this->_neededTables = array();
        $this->_expressionMap = array();
        $this->_subqueryAliases = array();
        $this->_needsSubquery = false;
    }

    public function createSubquery()
    {
        $class = get_class($this);
        $obj   = new $class();

        // copy the aliases to the subquery
        $obj->copyAliases($this);

        // this prevents the 'id' being selected, re ticket #307
        $obj->isSubquery(true);

        return $obj;
    }

    protected function _addPendingJoinCondition($componentAlias, $joinCondition)
    {
        $this->_pendingJoins[$componentAlias] = $joinCondition;
    }

    public function addEnumParam($key, $table = null, $column = null)
    {
        $array = (isset($table) || isset($column)) ? array($table, $column) : array();

        if ($key === '?') {
            $this->_enumParams[] = $array;
        } else {
            $this->_enumParams[$key] = $array;
        }
    }

    public function getEnumParams()
    {
        return $this->_enumParams;
    }

    public function getDql()
    {
        $q = '';
        $q .= ( ! empty($this->_dqlParts['select']))?  'SELECT '    . implode(', ', $this->_dqlParts['select']) : '';
        $q .= ( ! empty($this->_dqlParts['from']))?    ' FROM '     . implode(' ', $this->_dqlParts['from']) : '';
        $q .= ( ! empty($this->_dqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_dqlParts['where']) : '';
        $q .= ( ! empty($this->_dqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_dqlParts['groupby']) : '';
        $q .= ( ! empty($this->_dqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_dqlParts['having']) : '';
        $q .= ( ! empty($this->_dqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_dqlParts['orderby']) : '';
        $q .= ( ! empty($this->_dqlParts['limit']))?   ' LIMIT '    . implode(' ', $this->_dqlParts['limit']) : '';
        $q .= ( ! empty($this->_dqlParts['offset']))?  ' OFFSET '   . implode(' ', $this->_dqlParts['offset']) : '';

        return $q;
    }


    public function fetchArray($params = array()) {
        return $this->execute($params, IPF_ORM::HYDRATE_ARRAY);
    }

    public function fetchOne($params = array(), $hydrationMode = null)
    {
        $collection = $this->execute($params, $hydrationMode);

        if (count($collection) === 0) {
            return false;
        }

        if ($collection instanceof IPF_ORM_Collection) {
            return $collection->getFirst();
        } else if (is_array($collection)) {
            return array_shift($collection);
        }

        return false;
    }

    public function isSubquery($bool = null)
    {
        if ($bool === null) {
            return $this->_isSubquery;
        }

        $this->_isSubquery = (bool) $bool;
        return $this;
    }

    public function getAggregateAlias($dqlAlias)
    {
        return $this->getSqlAggregateAlias($dqlAlias);
    }

    public function getSqlAggregateAlias($dqlAlias)
    {
        if (isset($this->_aggregateAliasMap[$dqlAlias])) {
            // mark the expression as used
            $this->_expressionMap[$dqlAlias][1] = true;

            return $this->_aggregateAliasMap[$dqlAlias];
        } else if ( ! empty($this->_pendingAggregates)) {
            $this->processPendingAggregates();

            return $this->getSqlAggregateAlias($dqlAlias);
        } else {
            throw new IPF_ORM_Exception('Unknown aggregate alias: ' . $dqlAlias);
        }
    }

    public function getDqlPart($queryPart)
    {
        if ( ! isset($this->_dqlParts[$queryPart])) {
           throw new IPF_ORM_Exception('Unknown query part ' . $queryPart);
        }

        return $this->_dqlParts[$queryPart];
    }

    public function contains($dql)
    {
      return stripos($this->getDql(), $dql) === false ? false : true;
    }

    public function processPendingFields($componentAlias)
    {
        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_queryComponents[$componentAlias]['table'];

        if ( ! isset($this->_pendingFields[$componentAlias])) {
            if ($this->_hydrator->getHydrationMode() != IPF_ORM::HYDRATE_NONE) {
                if ( ! $this->_isSubquery && $componentAlias == $this->getRootAlias()) {
                    throw new IPF_ORM_Exception("The root class of the query (alias $componentAlias) "
                            . " must have at least one field selected.");
                }
            }
            return;
        }

        // At this point we know the component is FETCHED (either it's the base class of
        // the query (FROM xyz) or its a "fetch join").

        // Check that the parent join (if there is one), is a "fetch join", too.
        if (isset($this->_queryComponents[$componentAlias]['parent'])) {
            $parentAlias = $this->_queryComponents[$componentAlias]['parent'];
            if (is_string($parentAlias) && ! isset($this->_pendingFields[$parentAlias])
                    && $this->_hydrator->getHydrationMode() != IPF_ORM::HYDRATE_NONE) {
                throw new IPF_ORM_Exception("The left side of the join between "
                        . "the aliases '$parentAlias' and '$componentAlias' must have at least"
                        . " the primary key field(s) selected.");
            }
        }

        $fields = $this->_pendingFields[$componentAlias];

        // check for wildcards
        if (in_array('*', $fields)) {
            $fields = $table->getFieldNames();
        } else {
            // only auto-add the primary key fields if this query object is not
            // a subquery of another query object
            if ( ! $this->_isSubquery || $this->_hydrator->getHydrationMode() === IPF_ORM::HYDRATE_NONE) {
                $fields = array_unique(array_merge((array) $table->getIdentifier(), $fields));
            }
        }

        $sql = array();
        foreach ($fields as $fieldName) {
            $columnName = $table->getColumnName($fieldName);
            $sql[] = $this->_conn->quoteIdentifier($tableAlias . '.' . $columnName)
                   . ' AS '
                   . $this->_conn->quoteIdentifier($tableAlias . '__' . $columnName);
        }

        $this->_neededTables[] = $tableAlias;

        return implode(', ', $sql);
    }

    public function parseSelectField($field)
    {
        $terms = explode('.', $field);

        if (isset($terms[1])) {
            $componentAlias = $terms[0];
            $field = $terms[1];
        } else {
            reset($this->_queryComponents);
            $componentAlias = key($this->_queryComponents);
            $fields = $terms[0];
        }

        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_queryComponents[$componentAlias]['table'];


        // check for wildcards
        if ($field === '*') {
            $sql = array();

            foreach ($table->getColumnNames() as $field) {
                $sql[] = $this->parseSelectField($componentAlias . '.' . $field);
            }

            return implode(', ', $sql);
        } else {
            $name = $table->getColumnName($field);

            $this->_neededTables[] = $tableAlias;

            return $this->_conn->quoteIdentifier($tableAlias . '.' . $name)
                   . ' AS '
                   . $this->_conn->quoteIdentifier($tableAlias . '__' . $name);
        }
    }

    public function getExpressionOwner($expr)
    {
        if (strtoupper(substr(trim($expr, '( '), 0, 6)) !== 'SELECT') {
            preg_match_all("/[a-z_][a-z0-9_]*\.[a-z_][a-z0-9_]*[\.[a-z0-9]+]*/i", $expr, $matches);

            $match = current($matches);

            if (isset($match[0])) {
                $terms = explode('.', $match[0]);

                return $terms[0];
            }
        }
        return $this->getRootAlias();

    }

    public function parseSelect($dql)
    {
        $refs = $this->_tokenizer->sqlExplode($dql, ',');

        $pos   = strpos(trim($refs[0]), ' ');
        $first = substr($refs[0], 0, $pos);

        // check for DISTINCT keyword
        if ($first === 'DISTINCT') {
            $this->_sqlParts['distinct'] = true;

            $refs[0] = substr($refs[0], ++$pos);
        }

        $parsedComponents = array();

        foreach ($refs as $reference) {
            $reference = trim($reference);

            if (empty($reference)) {
                continue;
            }

            $terms = $this->_tokenizer->sqlExplode($reference, ' ');

            $pos   = strpos($terms[0], '(');

            if (count($terms) > 1 || $pos !== false) {
                $expression = array_shift($terms);
                $alias = array_pop($terms);

                if ( ! $alias) {
                    $alias = substr($expression, 0, $pos);
                }

                $componentAlias = $this->getExpressionOwner($expression);
                $expression = $this->parseClause($expression);

                $tableAlias = $this->getTableAlias($componentAlias);

                $index    = count($this->_aggregateAliasMap);

                $sqlAlias = $this->_conn->quoteIdentifier($tableAlias . '__' . $index);

                $this->_sqlParts['select'][] = $expression . ' AS ' . $sqlAlias;

                $this->_aggregateAliasMap[$alias] = $sqlAlias;
                $this->_expressionMap[$alias][0] = $expression;

                $this->_queryComponents[$componentAlias]['agg'][$index] = $alias;

                $this->_neededTables[] = $tableAlias;
            } else {
                $e = explode('.', $terms[0]);

                if (isset($e[1])) {
                    $componentAlias = $e[0];
                    $field = $e[1];
                } else {
                    reset($this->_queryComponents);
                    $componentAlias = key($this->_queryComponents);
                    $field = $e[0];
                }

                $this->_pendingFields[$componentAlias][] = $field;
            }
        }
    }

    public function parseClause($clause)
    {
      	$clause = trim($clause);

      	if (is_numeric($clause)) {
      	    return $clause;
      	}

        $terms = $this->_tokenizer->clauseExplode($clause, array(' ', '+', '-', '*', '/', '<', '>', '=', '>=', '<='));

        $str = '';
        foreach ($terms as $term) {
            $pos = strpos($term[0], '(');

            if ($pos !== false) {
                $name = substr($term[0], 0, $pos);
                $term[0] = $this->parseFunctionExpression($term[0]);
            } else {
                if (substr($term[0], 0, 1) !== "'" && substr($term[0], -1) !== "'") {

                    if (strpos($term[0], '.') !== false) {
                        if ( ! is_numeric($term[0])) {
                            $e = explode('.', $term[0]);

                            $field = array_pop($e);

                            if ($this->getType() === IPF_ORM_Query::SELECT) {
                                $componentAlias = implode('.', $e);

                                if (empty($componentAlias)) {
                                    $componentAlias = $this->getRootAlias();
                                }

                                $this->load($componentAlias);

                                // check the existence of the component alias
                                if ( ! isset($this->_queryComponents[$componentAlias])) {
                                    throw new IPF_ORM_Exception('Unknown component alias ' . $componentAlias);
                                }

                                $table = $this->_queryComponents[$componentAlias]['table'];

                                $def = $table->getDefinitionOf($field);

                                // get the actual field name from alias
                                $field = $table->getColumnName($field);

                                // check column existence
                                if ( ! $def) {
                                    throw new IPF_ORM_Exception('Unknown column ' . $field);
                                }

                                if (isset($def['owner'])) {
                                    $componentAlias = $componentAlias . '.' . $def['owner'];
                                }

                                $tableAlias = $this->getTableAlias($componentAlias);

                                // build sql expression
                                $term[0] = $this->_conn->quoteIdentifier($tableAlias)
                                         . '.'
                                         . $this->_conn->quoteIdentifier($field);
                            } else {
                                // build sql expression
                                $field = $this->getRoot()->getColumnName($field);
                                $term[0] = $this->_conn->quoteIdentifier($field);
                            }
                        }
                    } else {
                        if ( ! empty($term[0]) &&
                             ! in_array(strtoupper($term[0]), self::$_keywords) &&
                             ! is_numeric($term[0])) {

                            $componentAlias = $this->getRootAlias();

                            $found = false;

                            if ($componentAlias !== false &&
                                $componentAlias !== null) {
                                $table = $this->_queryComponents[$componentAlias]['table'];

                                // check column existence
                                if ($table->hasField($term[0])) {
                                    $found = true;

                                    $def = $table->getDefinitionOf($term[0]);

                                    // get the actual column name from field name
                                    $term[0] = $table->getColumnName($term[0]);


                                    if (isset($def['owner'])) {
                                        $componentAlias = $componentAlias . '.' . $def['owner'];
                                    }

                                    $tableAlias = $this->getTableAlias($componentAlias);

                                    if ($this->getType() === IPF_ORM_Query::SELECT) {
                                        // build sql expression
                                        $term[0] = $this->_conn->quoteIdentifier($tableAlias)
                                                 . '.'
                                                 . $this->_conn->quoteIdentifier($term[0]);
                                    } else {
                                        // build sql expression
                                        $term[0] = $this->_conn->quoteIdentifier($term[0]);
                                    }
                                } else {
                                    $found = false;
                                }
                            }

                            if ( ! $found) {
                                $term[0] = $this->getSqlAggregateAlias($term[0]);
                            }
                        }
                    }
                }
            }

            $str .= $term[0] . $term[1];
        }
        return $str;
    }

    public function parseIdentifierReference($expr)
    {
    }

    public function parseFunctionExpression($expr)
    {
        $pos = strpos($expr, '(');

        $name = substr($expr, 0, $pos);

        if ($name === '') {
            return $this->parseSubquery($expr);
        }

        $argStr = substr($expr, ($pos + 1), -1);

        $args   = array();
        // parse args

        foreach ($this->_tokenizer->sqlExplode($argStr, ',') as $arg) {
           $args[] = $this->parseClause($arg);
        }

        // convert DQL function to its RDBMS specific equivalent
        try {
            $expr = call_user_func_array(array($this->_conn->expression, $name), $args);
        } catch (IPF_ORM_Exception $e) {
            throw new IPF_ORM_Exception('Unknown function ' . $name . '.');
        }

        return $expr;
    }

    public function parseSubquery($subquery)
    {
        $trimmed = trim($this->_tokenizer->bracketTrim($subquery));

        // check for possible subqueries
        if (substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {
            // parse subquery
            $trimmed = $this->createSubquery()->parseDqlQuery($trimmed)->getQuery();
        } else {
            // parse normal clause
            $trimmed = $this->parseClause($trimmed);
        }

        return '(' . $trimmed . ')';
    }

    public function processPendingSubqueries()
    {
        foreach ($this->_pendingSubqueries as $value) {
            list($dql, $alias) = $value;

            $subquery = $this->createSubquery();

            $sql = $subquery->parseDqlQuery($dql, false)->getQuery();

            reset($this->_queryComponents);
            $componentAlias = key($this->_queryComponents);
            $tableAlias = $this->getTableAlias($componentAlias);

            $sqlAlias = $tableAlias . '__' . count($this->_aggregateAliasMap);

            $this->_sqlParts['select'][] = '(' . $sql . ') AS ' . $this->_conn->quoteIdentifier($sqlAlias);

            $this->_aggregateAliasMap[$alias] = $sqlAlias;
            $this->_queryComponents[$componentAlias]['agg'][] = $alias;
        }
        $this->_pendingSubqueries = array();
    }

    public function processPendingAggregates()
    {
        // iterate trhough all aggregates
        foreach ($this->_pendingAggregates as $aggregate) {
            list ($expression, $components, $alias) = $aggregate;

            $tableAliases = array();

            // iterate through the component references within the aggregate function
            if ( ! empty ($components)) {
                foreach ($components as $component) {

                    if (is_numeric($component)) {
                        continue;
                    }

                    $e = explode('.', $component);

                    $field = array_pop($e);
                    $componentAlias = implode('.', $e);

                    // check the existence of the component alias
                    if ( ! isset($this->_queryComponents[$componentAlias])) {
                        throw new IPF_ORM_Exception('Unknown component alias ' . $componentAlias);
                    }

                    $table = $this->_queryComponents[$componentAlias]['table'];

                    $field = $table->getColumnName($field);

                    // check column existence
                    if ( ! $table->hasColumn($field)) {
                        throw new IPF_ORM_Exception('Unknown column ' . $field);
                    }

                    $sqlTableAlias = $this->getSqlTableAlias($componentAlias);

                    $tableAliases[$sqlTableAlias] = true;

                    // build sql expression

                    $identifier = $this->_conn->quoteIdentifier($sqlTableAlias . '.' . $field);
                    $expression = str_replace($component, $identifier, $expression);
                }
            }

            if (count($tableAliases) !== 1) {
                $componentAlias = reset($this->_tableAliasMap);
                $tableAlias = key($this->_tableAliasMap);
            }

            $index    = count($this->_aggregateAliasMap);
            $sqlAlias = $this->_conn->quoteIdentifier($tableAlias . '__' . $index);

            $this->_sqlParts['select'][] = $expression . ' AS ' . $sqlAlias;

            $this->_aggregateAliasMap[$alias] = $sqlAlias;
            $this->_expressionMap[$alias][0] = $expression;

            $this->_queryComponents[$componentAlias]['agg'][$index] = $alias;

            $this->_neededTables[] = $tableAlias;
        }
        // reset the state
        $this->_pendingAggregates = array();
    }

    protected function _buildSqlQueryBase()
    {
        switch ($this->_type) {
            case self::DELETE:
                $q = 'DELETE FROM ';
            break;
            case self::UPDATE:
                $q = 'UPDATE ';
            break;
            case self::SELECT:
                $distinct = ($this->_sqlParts['distinct']) ? 'DISTINCT ' : '';
                $q = 'SELECT ' . $distinct . implode(', ', $this->_sqlParts['select']) . ' FROM ';
            break;
        }
        return $q;
    }

    protected function _buildSqlFromPart()
    {
        $q = '';
        foreach ($this->_sqlParts['from'] as $k => $part) {
            if ($k === 0) {
                $q .= $part;
                continue;
            }

            // preserve LEFT JOINs only if needed
            // Check if it's JOIN, if not add a comma separator instead of space
            if (!preg_match('/\bJOIN\b/i', $part) && !isset($this->_pendingJoinConditions[$k])) {
                $q .= ', ' . $part;
            } else {
                $e = explode(' ', $part);

                if (substr($part, 0, 9) === 'LEFT JOIN') {
                    $aliases = array_merge($this->_subqueryAliases,
                                array_keys($this->_neededTables));

                    if ( ! in_array($e[3], $aliases) &&
                        ! in_array($e[2], $aliases) &&

                        ! empty($this->_pendingFields)) {
                        continue;
                    }

                }

                if (isset($this->_pendingJoinConditions[$k])) {
                    $parser = new IPF_ORM_JoinCondition($this, $this->_tokenizer);

                    if (strpos($part, ' ON ') !== false) {
                        $part .= ' AND ';
                    } else {
                        $part .= ' ON ';
                    }
                    $part .= $parser->parse($this->_pendingJoinConditions[$k]);

                    unset($this->_pendingJoinConditions[$k]);
                }

                $tableAlias = trim($e[3], '"');
                $componentAlias = $this->getComponentAlias($tableAlias);

                $string = $this->getInheritanceCondition($componentAlias);

                if ($string) {
                    $q .= ' ' . $part . ' AND ' . $string;
                } else {
                    $q .= ' ' . $part;
                }
            }

            $this->_sqlParts['from'][$k] = $part;
        }
        return $q;
    }

    public function getSqlQuery($params = array())
    {
        if ($this->_state !== self::STATE_DIRTY) {
           return $this->_sql;
        }

        // reset the state
        if ( ! $this->isSubquery()) {
            $this->_queryComponents = array();
            $this->_pendingAggregates = array();
            $this->_aggregateAliasMap = array();
        }
        $this->reset();

        // invoke the preQuery hook
        $this->_preQuery();

        // process the DQL parts => generate the SQL parts.
        // this will also populate the $_queryComponents.
        foreach ($this->_dqlParts as $queryPartName => $queryParts) {
            $this->_processDqlQueryPart($queryPartName, $queryParts);
        }
        $this->_state = self::STATE_CLEAN;

        $params = $this->convertEnums($params);

        // Proceed with the generated SQL

        if (empty($this->_sqlParts['from'])) {
            return false;
        }

        $needsSubQuery = false;
        $subquery = '';
        $map = reset($this->_queryComponents);
        $table = $map['table'];
        $rootAlias = key($this->_queryComponents);

        if (!empty($this->_sqlParts['limit']) && $this->_needsSubquery) {
            $needsSubQuery = true;
        }

        $sql = array();
        if ( ! empty($this->_pendingFields)) {
            foreach ($this->_queryComponents as $alias => $map) {
                $fieldSql = $this->processPendingFields($alias);
                if ( ! empty($fieldSql)) {
                    $sql[] = $fieldSql;
                }
            }
        }
        if ( ! empty($sql)) {
            array_unshift($this->_sqlParts['select'], implode(', ', $sql));
        }

        $this->_pendingFields = array();

        // build the basic query
        $q  = $this->_buildSqlQueryBase();
        $q .= $this->_buildSqlFromPart();

        if ( ! empty($this->_sqlParts['set'])) {
            $q .= ' SET ' . implode(', ', $this->_sqlParts['set']);
        }

        $string = $this->getInheritanceCondition($this->getRootAlias());

        // apply inheritance to WHERE part
        if ( ! empty($string)) {
            if (substr($string, 0, 1) === '(' && substr($string, -1) === ')') {
                $this->_sqlParts['where'][] = $string;
            } else {
                $this->_sqlParts['where'][] = '(' . $string . ')';
            }
        }

        $modifyLimit = true;
        if ( ! empty($this->_sqlParts['limit']) || ! empty($this->_sqlParts['offset'])) {
            if ($needsSubQuery) {
                $subquery = $this->getLimitSubquery();
                // what about composite keys?
                $idColumnName = $table->getColumnName($table->getIdentifier());
                switch (strtolower($this->_conn->getDriverName())) {
                    case 'Mysql':
                        // mysql doesn't support LIMIT in subqueries
                        $list = $this->_conn->execute($subquery, $params)->fetchAll(IPF_ORM::FETCH_COLUMN);
                        $subquery = implode(', ', array_map(array($this->_conn, 'quote'), $list));
                        break;
                    case 'Pgsql':
                        // pgsql needs special nested LIMIT subquery
                        $subquery = 'SELECT ipf_orm_subquery_alias.' . $idColumnName . ' FROM (' . $subquery . ') AS ipf_orm_subquery_alias';
                        break;
                }

                $field = $this->getSqlTableAlias($rootAlias) . '.' . $idColumnName;

                // only append the subquery if it actually contains something
                if ($subquery !== '') {
                    array_unshift($this->_sqlParts['where'], $this->_conn->quoteIdentifier($field) . ' IN (' . $subquery . ')');
                }

                $modifyLimit = false;
            }
        }

        $q .= ( ! empty($this->_sqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_sqlParts['where']) : '';
        $q .= ( ! empty($this->_sqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_sqlParts['groupby'])  : '';
        $q .= ( ! empty($this->_sqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_sqlParts['having']): '';
        $q .= ( ! empty($this->_sqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_sqlParts['orderby'])  : '';

        if ($modifyLimit) {
            $q = $this->_conn->modifyLimitQuery($q, $this->_sqlParts['limit'], $this->_sqlParts['offset']);
        }

        // return to the previous state
        if ( ! empty($string)) {
            array_pop($this->_sqlParts['where']);
        }
        if ($needsSubQuery) {
            array_shift($this->_sqlParts['where']);
        }
        $this->_sql = $q;

        return $q;
    }

    public function getLimitSubquery()
    {
        $map = reset($this->_queryComponents);
        $table = $map['table'];
        $componentAlias = key($this->_queryComponents);

        // get short alias
        $alias = $this->getTableAlias($componentAlias);
        // what about composite keys?
        $primaryKey = $alias . '.' . $table->getColumnName($table->getIdentifier());

        // initialize the base of the subquery
        $subquery = 'SELECT DISTINCT ' . $this->_conn->quoteIdentifier($primaryKey);

        $driverName = $this->_conn->getDriverName();

        // pgsql needs the order by fields to be preserved in select clause
        if ($driverName == 'Pgsql') {
            foreach ($this->_sqlParts['orderby'] as $part) {
                $part = trim($part);
                $e = $this->_tokenizer->bracketExplode($part, ' ');
                $part = trim($e[0]);

                if (strpos($part, '.') === false) {
                    continue;
                }

                // don't add functions
                if (strpos($part, '(') !== false) {
                    continue;
                }

                // don't add primarykey column (its already in the select clause)
                if ($part !== $primaryKey) {
                    $subquery .= ', ' . $part;
                }
            }
        }

        if ($driverName == 'Mysql' || $driverName == 'Pgsql') {
            foreach ($this->_expressionMap as $dqlAlias => $expr) {
                if (isset($expr[1])) {
                    $subquery .= ', ' . $expr[0] . ' AS ' . $this->_aggregateAliasMap[$dqlAlias];
                }
            }
        }

        $subquery .= ' FROM';

        foreach ($this->_sqlParts['from'] as $part) {
            // preserve LEFT JOINs only if needed
            if (substr($part, 0, 9) === 'LEFT JOIN') {
                $e = explode(' ', $part);

                if (empty($this->_sqlParts['orderby']) && empty($this->_sqlParts['where'])) {
                    continue;
                }
            }

            $subquery .= ' ' . $part;
        }

        // all conditions must be preserved in subquery
        $subquery .= ( ! empty($this->_sqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_sqlParts['where'])  : '';
        $subquery .= ( ! empty($this->_sqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_sqlParts['groupby'])   : '';
        $subquery .= ( ! empty($this->_sqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_sqlParts['having']) : '';

        $subquery .= ( ! empty($this->_sqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_sqlParts['orderby'])   : '';

        // add driver specific limit clause
        $subquery = $this->_conn->modifyLimitSubquery($table, $subquery, $this->_sqlParts['limit'], $this->_sqlParts['offset']);

        $parts = $this->_tokenizer->quoteExplode($subquery, ' ', "'", "'");

        foreach ($parts as $k => $part) {
            if (strpos($part, ' ') !== false) {
                continue;
            }

            $part = str_replace(array('"', "'", '`'), "", $part);

            if ($this->hasSqlTableAlias($part)) {
                $parts[$k] = $this->_conn->quoteIdentifier($this->generateNewSqlTableAlias($part));
                continue;
            }

            if (strpos($part, '.') === false) {
                continue;
            }

            preg_match_all("/[a-zA-Z0-9_]+\.[a-z0-9_]+/i", $part, $m);

            foreach ($m[0] as $match) {
                $e = explode('.', $match);

                // Rebuild the original part without the newly generate alias and with quoting reapplied
                $e2 = array();
                foreach ($e as $k2 => $v2) {
                  $e2[$k2] = $this->_conn->quoteIdentifier($v2);
                }
                $match = implode('.', $e2);

                // Generate new table alias
                $e[0] = $this->generateNewSqlTableAlias($e[0]);

                // Requote the part with the newly generated alias
                foreach ($e as $k2 => $v2) {
                  $e[$k2] = $this->_conn->quoteIdentifier($v2);
                }

                $replace = implode('.' , $e);

                // Replace the original part with the new part with new sql table alias
                $parts[$k] = str_replace($match, $replace, $parts[$k]);
            }
        }

        if ($driverName == 'Mysql' || $driverName == 'Pgsql') {
            foreach ($parts as $k => $part) {
                if (strpos($part, "'") !== false) {
                    continue;
                }
                if (strpos($part, '__') == false) {
                    continue;
                }

                preg_match_all("/[a-zA-Z0-9_]+\_\_[a-z0-9_]+/i", $part, $m);

                foreach ($m[0] as $match) {
                    $e = explode('__', $match);
                    $e[0] = $this->generateNewTableAlias($e[0]);

                    $parts[$k] = str_replace($match, implode('__', $e), $parts[$k]);
                }
            }
        }

        $subquery = implode(' ', $parts);
        return $subquery;
    }

    public function parseDqlQuery($query, $clear = true)
    {
        if ($clear) {
            $this->clear();
        }

        $query = trim($query);
        $query = str_replace("\r", "\n", str_replace("\r\n", "\n", $query));
        $query = str_replace("\n", ' ', $query);

        $parts = $this->_tokenizer->tokenizeQuery($query);

        foreach ($parts as $partName => $subParts) {
            $subParts = trim($subParts);
            $partName = strtolower($partName);
            switch ($partName) {
                case 'create':
                    $this->_type = self::CREATE;
                break;
                case 'insert':
                    $this->_type = self::INSERT;
                break;
                case 'delete':
                    $this->_type = self::DELETE;
                break;
                case 'select':
                    $this->_type = self::SELECT;
                    $this->_addDqlQueryPart($partName, $subParts);
                break;
                case 'update':
                    $this->_type = self::UPDATE;
                    $partName = 'from';
                case 'from':
                    $this->_addDqlQueryPart($partName, $subParts);
                break;
                case 'set':
                    $this->_addDqlQueryPart($partName, $subParts, true);
                break;
                case 'group':
                case 'order':
                    $partName .= 'by';
                case 'where':
                case 'having':
                case 'limit':
                case 'offset':
                    $this->_addDqlQueryPart($partName, $subParts);
                break;
            }
        }

        return $this;
    }

    public function load($path, $loadFields = true)
    {
        if (isset($this->_queryComponents[$path])) {
            return $this->_queryComponents[$path];
        }

        $e = $this->_tokenizer->quoteExplode($path, ' INDEXBY ');

        $mapWith = null;
        if (count($e) > 1) {
            $mapWith = trim($e[1]);

            $path = $e[0];
        }

        // parse custom join conditions
        $e = explode(' ON ', $path);

        $joinCondition = '';

        if (count($e) > 1) {
            $joinCondition = $e[1];
            $overrideJoin = true;
            $path = $e[0];
        } else {
            $e = explode(' WITH ', $path);

            if (count($e) > 1) {
                $joinCondition = $e[1];
                $path = $e[0];
            }
            $overrideJoin = false;
        }

        $tmp            = explode(' ', $path);
        $componentAlias = $originalAlias = (count($tmp) > 1) ? end($tmp) : null;

        $e = preg_split("/[.:]/", $tmp[0], -1);

        $fullPath = $tmp[0];
        $prevPath = '';
        $fullLength = strlen($fullPath);

        if (isset($this->_queryComponents[$e[0]])) {
            $table = $this->_queryComponents[$e[0]]['table'];
            $componentAlias = $e[0];

            $prevPath = $parent = array_shift($e);
        }

        foreach ($e as $key => $name) {
            // get length of the previous path
            $length = strlen($prevPath);

            // build the current component path
            $prevPath = ($prevPath) ? $prevPath . '.' . $name : $name;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($prevPath) === $fullLength && isset($originalAlias)) {
                $componentAlias = $originalAlias;
            } else {
                $componentAlias = $prevPath;
            }

            // if the current alias already exists, skip it
            if (isset($this->_queryComponents[$componentAlias])) {
                throw new IPF_ORM_Exception("Duplicate alias '$componentAlias' in query.");
            }

            if ( ! isset($table)) {
                // process the root of the path

                $table = $this->loadRoot($name, $componentAlias);
            } else {
                $join = ($delimeter == ':') ? 'INNER JOIN ' : 'LEFT JOIN ';

                $relation = $table->getRelation($name);
                $localTable = $table;

                $table    = $relation->getTable();
                $this->_queryComponents[$componentAlias] = array('table' => $table,
                                                                 'parent'   => $parent,
                                                                 'relation' => $relation,
                                                                 'map'      => null);
                if ( ! $relation->isOneToOne()) {
                   $this->_needsSubquery = true;
                }

                $localAlias   = $this->getTableAlias($parent, $table->getTableName());
                $foreignAlias = $this->getTableAlias($componentAlias, $relation->getTable()->getTableName());

                $foreignSql   = $this->_conn->quoteIdentifier($relation->getTable()->getTableName())
                              . ' '
                              . $this->_conn->quoteIdentifier($foreignAlias);

                $map = $relation->getTable()->inheritanceMap;

                if ( ! $loadFields || ! empty($map) || $joinCondition) {
                    $this->_subqueryAliases[] = $foreignAlias;
                }

                if ($relation instanceof IPF_ORM_Relation_Association) {
                    $asf = $relation->getAssociationTable();

                    $assocTableName = $asf->getTableName();

                    if ( ! $loadFields || ! empty($map) || $joinCondition) {
                        $this->_subqueryAliases[] = $assocTableName;
                    }

                    $assocPath = $prevPath . '.' . $asf->getComponentName();

                    $this->_queryComponents[$assocPath] = array('parent' => $prevPath, 'relation' => $relation, 'table' => $asf);

                    $assocAlias = $this->getTableAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $assocTableName . ' ' . $assocAlias;

                    $queryPart .= ' ON ' . $localAlias
                                . '.'
                                . $localTable->getColumnName($localTable->getIdentifier()) // what about composite keys?
                                . ' = '
                                . $assocAlias . '.' . $relation->getLocal();

                    if ($relation->isEqual()) {
                        // equal nest relation needs additional condition
                        $queryPart .= ' OR ' . $localAlias
                                    . '.'
                                    . $table->getColumnName($table->getIdentifier())
                                    . ' = '
                                    . $assocAlias . '.' . $relation->getForeign();
                    }

                    $this->_sqlParts['from'][] = $queryPart;

                    $queryPart = $join . $foreignSql;

                    if ( ! $overrideJoin) {
                        $queryPart .= $this->buildAssociativeRelationSql($relation, $assocAlias, $foreignAlias, $localAlias);
                    }
                } else {
                    $queryPart = $this->buildSimpleRelationSql($relation, $foreignAlias, $localAlias, $overrideJoin, $join);
                }

                $queryPart .= $this->buildInheritanceJoinSql($table->getComponentName(), $componentAlias);

                $this->_sqlParts['from'][$componentAlias] = $queryPart;
                if ( ! empty($joinCondition)) {
                    $this->_pendingJoinConditions[$componentAlias] = $joinCondition;
                }
            }
            if ($loadFields) {

                $restoreState = false;
                // load fields if necessary
                if ($loadFields && empty($this->_dqlParts['select'])) {
                    $this->_pendingFields[$componentAlias] = array('*');
                }
            }
            $parent = $prevPath;
        }

        $table = $this->_queryComponents[$componentAlias]['table'];

        return $this->buildIndexBy($componentAlias, $mapWith);
    }

    protected function buildSimpleRelationSql(IPF_ORM_Relation $relation, $foreignAlias, $localAlias, $overrideJoin, $join)
    {
        $queryPart = $join . $this->_conn->quoteIdentifier($relation->getTable()->getTableName())
                           . ' '
                           . $this->_conn->quoteIdentifier($foreignAlias);

        if ( ! $overrideJoin) {
            $queryPart .= ' ON '
                       . $this->_conn->quoteIdentifier($localAlias . '.' . $relation->getLocal())
                       . ' = '
                       . $this->_conn->quoteIdentifier($foreignAlias . '.' . $relation->getForeign());
        }

        return $queryPart;
    }

    protected function buildIndexBy($componentAlias, $mapWith = null)
    {
        $table = $this->_queryComponents[$componentAlias]['table'];

        $indexBy = null;

        if (isset($mapWith)) {
            $terms = explode('.', $mapWith);

            if (isset($terms[1])) {
                $indexBy = $terms[1];
            }
        } elseif ($table->getBoundQueryPart('indexBy') !== null) {
            $indexBy = $table->getBoundQueryPart('indexBy');
        }

        if ($indexBy !== null) {
            if ( ! $table->hasColumn($table->getColumnName($indexBy))) {
                throw new IPF_ORM_Exception("Couldn't use key mapping. Column " . $indexBy . " does not exist.");
            }

            $this->_queryComponents[$componentAlias]['map'] = $indexBy;
        }

        return $this->_queryComponents[$componentAlias];
    }


    protected function buildAssociativeRelationSql(IPF_ORM_Relation $relation, $assocAlias, $foreignAlias, $localAlias)
    {
        $table = $relation->getTable();

        $queryPart = ' ON ';

        if ($relation->isEqual()) {
            $queryPart .= '(';
        }

        $localIdentifier = $table->getColumnName($table->getIdentifier());

        $queryPart .= $this->_conn->quoteIdentifier($foreignAlias . '.' . $localIdentifier)
                    . ' = '
                    . $this->_conn->quoteIdentifier($assocAlias . '.' . $relation->getForeign());

        if ($relation->isEqual()) {
            $queryPart .= ' OR '
                        . $this->_conn->quoteIdentifier($foreignAlias . '.' . $localIdentifier)
                        . ' = '
                        . $this->_conn->quoteIdentifier($assocAlias . '.' . $relation->getLocal())
                        . ') AND '
                        . $this->_conn->quoteIdentifier($foreignAlias . '.' . $localIdentifier)
                        . ' != '
                        . $this->_conn->quoteIdentifier($localAlias . '.' . $localIdentifier);
        }

        return $queryPart;
    }

    public function loadRoot($name, $componentAlias)
    {
        // get the connection for the component
        $manager = IPF_ORM_Manager::getInstance();
        if ($manager->hasConnectionForComponent($name)) {
            $this->_conn = $manager->getConnectionForComponent($name);
        }

        $table = $this->_conn->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->getTableAlias($componentAlias, $tableName);
        // quote table name
        $queryPart = $this->_conn->quoteIdentifier($tableName);

        if ($this->_type === self::SELECT) {
            $queryPart .= ' ' . $this->_conn->quoteIdentifier($tableAlias);
        }

        $this->_tableAliasMap[$tableAlias] = $componentAlias;

        $queryPart .= $this->buildInheritanceJoinSql($name, $componentAlias);

        $this->_sqlParts['from'][] = $queryPart;

        $this->_queryComponents[$componentAlias] = array('table' => $table, 'map' => null);

        return $table;
    }

    public function buildInheritanceJoinSql($name, $componentAlias)
    {
        // get the connection for the component
        $manager = IPF_ORM_Manager::getInstance();
        if ($manager->hasConnectionForComponent($name)) {
            $this->_conn = $manager->getConnectionForComponent($name);
        }

        $table = $this->_conn->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->getTableAlias($componentAlias, $tableName);

        return '';
    }

    public function getCountQuery()
    {
        // triggers dql parsing/processing
        $this->getQuery(); // this is ugly

        // initialize temporary variables
        $where  = $this->_sqlParts['where'];
        $having = $this->_sqlParts['having'];
        $groupby = $this->_sqlParts['groupby'];
        $map = reset($this->_queryComponents);
        $componentAlias = key($this->_queryComponents);
        $tableAlias = $this->_conn->quoteIdentifier($this->getTableAlias($componentAlias));
        $table = $map['table'];

        $idColumnNames = array();
        foreach ($table->getIdentifierColumnNames() as $column) {
            $idColumnNames[] = $tableAlias . '.' . $this->_conn->quoteIdentifier($column);
        }

        // build the query base
        $q  = 'SELECT COUNT(DISTINCT ' . implode(' || ', $idColumnNames) . ') AS num_results';

        foreach ($this->_sqlParts['select'] as $field) {
            if (strpos($field, '(') !== false) {
                $q .= ', ' . $field;
            }
        }

        $q .= ' FROM ' . $this->_buildSqlFromPart();

        // append column aggregation inheritance (if needed)
        $string = $this->getInheritanceCondition($this->getRootAlias());

        if ( ! empty($string)) {
            $where[] = $string;
        }

        // append conditions
        $q .= ( ! empty($where)) ?  ' WHERE '  . implode(' AND ', $where) : '';

        if ( ! empty($groupby)) {
            // Maintain existing groupby
            $q .= ' GROUP BY '  . implode(', ', $groupby);
        } else {
            // Default groupby to primary identifier. Database defaults to this internally
            // This is required for situations where the user has aggregate functions in the select part
            // Without the groupby it fails
            $q .= ' GROUP BY ' . implode(', ', $idColumnNames);
        }

        $q .= ( ! empty($having)) ? ' HAVING ' . implode(' AND ', $having): '';

        return $q;
    }

    public function count($params = array())
    {
        $q = $this->getCountQuery();

        if ( ! is_array($params)) {
            $params = array($params);
        }

        $params = array_merge($this->_params['join'], $this->_params['where'], $this->_params['having'], $params);

        $params = $this->convertEnums($params);

        $results = $this->getConnection()->fetchAll($q, $params);

        if (count($results) > 1) {
            $count = count($results);
        } else {
            if (isset($results[0])) {
                $results[0] = array_change_key_case($results[0], CASE_LOWER);
                $count = $results[0]['num_results'];
            } else {
                $count = 0;
            }
        }

        return (int) $count;
    }

    public function query($query, $params = array(), $hydrationMode = null)
    {
        $this->parseDqlQuery($query);
        return $this->execute($params, $hydrationMode);
    }

    public function copy(IPF_ORM_Query $query = null)
    {
        if ( ! $query) {
            $query = $this;
        }

        $new = clone $query;

        return $new;
    }

    public function __clone()
    {
        $this->_parsers = array();
    }

    public function free()
    {
        $this->reset();
        $this->_parsers = array();
        $this->_dqlParts = array();
        $this->_enumParams = array();
    }

    public function serialize()
    {
        $vars = get_object_vars($this);
    }

    public function unserialize($serialized)
    {
    }
}
