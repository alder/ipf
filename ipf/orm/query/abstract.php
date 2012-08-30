<?php

abstract class IPF_ORM_Query_Abstract
{
    const SELECT = 0;
    const DELETE = 1;
    const UPDATE = 2;
    const INSERT = 3;
    const CREATE = 4;

    const STATE_CLEAN  = 1;
    const STATE_DIRTY  = 2;
    const STATE_DIRECT = 3;
    const STATE_LOCKED = 4;

    protected $_tableAliasMap = array();
    protected $_view;
    protected $_state = IPF_ORM_Query::STATE_CLEAN;
    protected $_params = array('join' => array(),
                               'where' => array(),
                               'set' => array(),
                               'having' => array());

    protected $_resultCache;
    protected $_expireResultCache = false;
    protected $_resultCacheTTL;

    protected $_queryCache;
    protected $_expireQueryCache = false;
    protected $_queryCacheTTL;

    protected $_conn;

    protected $_sqlParts = array(
            'select'    => array(),
            'distinct'  => false,
            'forUpdate' => false,
            'from'      => array(),
            'set'       => array(),
            'join'      => array(),
            'where'     => array(),
            'groupby'   => array(),
            'having'    => array(),
            'orderby'   => array(),
            'limit'     => false,
            'offset'    => false,
            );

    protected $_dqlParts = array(
                            'from'      => array(),
                            'select'    => array(),
                            'forUpdate' => false,
                            'set'       => array(),
                            'join'      => array(),
                            'where'     => array(),
                            'groupby'   => array(),
                            'having'    => array(),
                            'orderby'   => array(),
                            'limit'     => array(),
                            'offset'    => array(),
                            );

    protected $_queryComponents = array();
    protected $_type = self::SELECT;
    protected $_hydrator;
    protected $_tokenizer;
    protected $_parser;
    protected $_tableAliasSeeds = array();
    protected $_options    = array(
                            'fetchMode'      => IPF_ORM::FETCH_RECORD
                            );
    protected $_enumParams = array();

    protected $_isLimitSubqueryUsed = false;
    protected $_pendingSetParams = array();
    protected $_components;
    protected $_preQueried = false;

    public function __construct(IPF_ORM_Connection $connection = null,
            IPF_ORM_Hydrator_Abstract $hydrator = null)
    {
        if ($connection === null) {
            $connection = IPF_ORM_Manager::getInstance()->getCurrentConnection();
        }
        if ($hydrator === null) {
            $hydrator = new IPF_ORM_Hydrator();
        }
        $this->_conn = $connection;
        $this->_hydrator = $hydrator;
        $this->_tokenizer = new IPF_ORM_Query_Tokenizer();
        $this->_resultCacheTTL = $this->_conn->getAttribute(IPF_ORM::ATTR_RESULT_CACHE_LIFESPAN);
        $this->_queryCacheTTL = $this->_conn->getAttribute(IPF_ORM::ATTR_QUERY_CACHE_LIFESPAN);
    }

    public function setOption($name, $value)
    {
        if ( ! isset($this->_options[$name])) {
            throw new IPF_ORM_Exception('Unknown option ' . $name);
        }
        $this->_options[$name] = $value;
    }

    public function hasTableAlias($sqlTableAlias)
    {
        return $this->hasSqlTableAlias($sqlTableAlias);
    }

    public function hasSqlTableAlias($sqlTableAlias)
    {
        return (isset($this->_tableAliasMap[$sqlTableAlias]));
    }

    public function getTableAliases()
    {
        return $this->getTableAliasMap();
    }

    public function getTableAliasMap()
    {
        return $this->_tableAliasMap;
    }

    public function getQueryPart($part)
    {
        return $this->getSqlQueryPart($part);
    }

    public function getSqlQueryPart($part)
    {
        if ( ! isset($this->_sqlParts[$part])) {
            throw new IPF_ORM_Exception('Unknown SQL query part ' . $part);
        }
        return $this->_sqlParts[$part];
    }

    public function setQueryPart($name, $part)
    {
        return $this->setSqlQueryPart($name, $part);
    }

    public function setSqlQueryPart($name, $part)
    {
        if ( ! isset($this->_sqlParts[$name])) {
            throw new IPF_ORM_Exception('Unknown query part ' . $name);
        }

        if ($name !== 'limit' && $name !== 'offset') {
            if (is_array($part)) {
                $this->_sqlParts[$name] = $part;
            } else {
                $this->_sqlParts[$name] = array($part);
            }
        } else {
            $this->_sqlParts[$name] = $part;
        }

        return $this;
    }

    public function addQueryPart($name, $part)
    {
        return $this->addSqlQueryPart($name, $part);
    }

    public function addSqlQueryPart($name, $part)
    {
        if ( ! isset($this->_sqlParts[$name])) {
            throw new IPF_ORM_Exception('Unknown query part ' . $name);
        }
        if (is_array($part)) {
            $this->_sqlParts[$name] = array_merge($this->_sqlParts[$name], $part);
        } else {
            $this->_sqlParts[$name][] = $part;
        }
        return $this;
    }

    public function removeQueryPart($name)
    {
        return $this->removeSqlQueryPart($name);
    }

    public function removeSqlQueryPart($name)
    {
        if ( ! isset($this->_sqlParts[$name])) {
            throw new IPF_ORM_Exception('Unknown query part ' . $name);
        }

        if ($name == 'limit' || $name == 'offset') {
            $this->_sqlParts[$name] = false;
        } else {
            $this->_sqlParts[$name] = array();
        }

        return $this;
    }

    public function removeDqlQueryPart($name)
    {
        if ( ! isset($this->_dqlParts[$name])) {
            throw new IPF_ORM_Exception('Unknown query part ' . $name);
        }

        if ($name == 'limit' || $name == 'offset') {
            $this->_dqlParts[$name] = false;
        } else {
            $this->_dqlParts[$name] = array();
        }

        return $this;
    }

    public function getParams($params = array())
    {
        return array_merge($this->_params['join'], $this->_params['set'], $this->_params['where'], $this->_params['having'], $params);
    }

    public function setParams(array $params = array()) {
        $this->_params = $params;
    }

    public function setView(IPF_ORM_View $view)
    {
        $this->_view = $view;
    }

    public function getView()
    {
        return $this->_view;
    }

    public function isLimitSubqueryUsed()
    {
        return $this->_isLimitSubqueryUsed;
    }

    public function convertEnums($params)
    {
        $table = $this->getRoot();

        // $position tracks the position of the parameter, to ensure we're converting
        // the right parameter value when simple ? placeholders are used.
        // This only works because SET is only allowed in update statements and it's
        // the first place where parameters can occur.. see issue #935
        $position = 0;
        foreach ($this->_pendingSetParams as $fieldName => $value) {
            $e = explode('.', $fieldName);
            $fieldName = isset($e[1]) ? $e[1]:$e[0];
            if ($table->getTypeOf($fieldName) == 'enum') {
                $value = $value === '?' ? $position : $value;
                $this->addEnumParam($value, $table, $fieldName);
            }
            ++$position;
        }
        $this->_pendingSetParams = array();

        foreach ($this->_enumParams as $key => $values) {
            if (isset($params[$key])) {
                if ( ! empty($values)) {
                    $params[$key] = $values[0]->enumIndex($values[1], $params[$key]);
                }
            }
        }

        return $params;
    }

    public function getInheritanceCondition($componentAlias)
    {
        $map = $this->_queryComponents[$componentAlias]['table']->inheritanceMap;

        // No inheritance map so lets just return
        if (empty($map)) {
          return;
        }

        $tableAlias = $this->getSqlTableAlias($componentAlias);

        if ($this->_type !== IPF_ORM_Query::SELECT) {
            $tableAlias = '';
        } else {
            $tableAlias .= '.';
        }

        $field = key($map);
        $value = current($map);
        $identifier = $this->_conn->quoteIdentifier($tableAlias . $field);

        return $identifier . ' = ' . $this->_conn->quote($value);;
    }

    public function getTableAlias($componentAlias, $tableName = null)
    {
        return $this->getSqlTableAlias($componentAlias, $tableName);
    }

    public function getSqlTableAlias($componentAlias, $tableName = null)
    {
        $alias = array_search($componentAlias, $this->_tableAliasMap);

        if ($alias !== false) {
            return $alias;
        }

        if ($tableName === null) {
            throw new IPF_ORM_Exception("Couldn't get short alias for " . $componentAlias);
        }

        return $this->generateTableAlias($componentAlias, $tableName);
    }

    public function generateNewTableAlias($oldAlias)
    {
        return $this->generateNewSqlTableAlias($oldAlias);
    }

    public function generateNewSqlTableAlias($oldAlias)
    {
        if (isset($this->_tableAliasMap[$oldAlias])) {
            // generate a new alias
            $name = substr($oldAlias, 0, 1);
            $i    = ((int) substr($oldAlias, 1));

            if ($i == 0) {
                $i = 1;
            }

            $newIndex  = ($this->_tableAliasSeeds[$name] + $i);

            return $name . $newIndex;
        }

        return $oldAlias;
    }

    public function getTableAliasSeed($sqlTableAlias)
    {
        return $this->getSqlTableAliasSeed($sqlTableAlias);
    }

    public function getSqlTableAliasSeed($sqlTableAlias)
    {
        if ( ! isset($this->_tableAliasSeeds[$sqlTableAlias])) {
            return 0;
        }
        return $this->_tableAliasSeeds[$sqlTableAlias];
    }

    public function hasAliasDeclaration($componentAlias)
    {
        return isset($this->_queryComponents[$componentAlias]);
    }

    public function getAliasDeclaration($componentAlias)
    {
        return $this->getQueryComponent($componentAlias);
    }

    public function getQueryComponent($componentAlias)
    {
        if ( ! isset($this->_queryComponents[$componentAlias])) {
            throw new IPF_ORM_Exception('Unknown component alias ' . $componentAlias);
        }

        return $this->_queryComponents[$componentAlias];
    }

    public function copyAliases(IPF_ORM_Query_Abstract $query)
    {
        $this->_tableAliasMap = $query->_tableAliasMap;
        $this->_queryComponents     = $query->_queryComponents;
        $this->_tableAliasSeeds = $query->_tableAliasSeeds;
        return $this;
    }

    public function getRootAlias()
    {
        if ( ! $this->_queryComponents) {
          $this->getSql();
        }
        reset($this->_queryComponents);

        return key($this->_queryComponents);
    }

    public function getRootDeclaration()
    {
        $map = reset($this->_queryComponents);
        return $map;
    }

    public function getRoot()
    {
        $map = reset($this->_queryComponents);

        if ( ! isset($map['table'])) {
            throw new IPF_ORM_Exception('Root component not initialized.');
        }

        return $map['table'];
    }

    public function generateTableAlias($componentAlias, $tableName)
    {
        return $this->generateSqlTableAlias($componentAlias, $tableName);
    }

    public function generateSqlTableAlias($componentAlias, $tableName)
    {
        preg_match('/([^_])/', $tableName, $matches);
        $char = strtolower($matches[0]);

        $alias = $char;

        if ( ! isset($this->_tableAliasSeeds[$alias])) {
            $this->_tableAliasSeeds[$alias] = 1;
        }

        while (isset($this->_tableAliasMap[$alias])) {
            if ( ! isset($this->_tableAliasSeeds[$alias])) {
                $this->_tableAliasSeeds[$alias] = 1;
            }
            $alias = $char . ++$this->_tableAliasSeeds[$alias];
        }

        $this->_tableAliasMap[$alias] = $componentAlias;

        return $alias;
    }

    public function getComponentAlias($sqlTableAlias)
    {
        if ( ! isset($this->_tableAliasMap[$sqlTableAlias])) {
            throw new IPF_ORM_Exception('Unknown table alias ' . $sqlTableAlias);
        }
        return $this->_tableAliasMap[$sqlTableAlias];
    }

    protected function _execute($params)
    {
        $params = $this->_conn->convertBooleans($params);

        if ( ! $this->_view) {
            if ($this->_queryCache || $this->_conn->getAttribute(IPF_ORM::ATTR_QUERY_CACHE)) {
                $queryCacheDriver = $this->getQueryCacheDriver();
                // calculate hash for dql query
                $dql = $this->getDql();
                $hash = md5($dql . 'IPF_ORM_QUERY_CACHE_SALT');
                $cached = $queryCacheDriver->fetch($hash);
                if ($cached) {
                    $query = $this->_constructQueryFromCache($cached);
                } else {
                    $query = $this->getSqlQuery($params);
                    $serializedQuery = $this->getCachedForm($query);
                    $queryCacheDriver->save($hash, $serializedQuery, $this->getQueryCacheLifeSpan());
                }
            } else {
                $query = $this->getSqlQuery($params);
            }
            $params = $this->convertEnums($params);
        } else {
            $query = $this->_view->getSelectSql();
        }

        if ($this->isLimitSubqueryUsed() &&
                $this->_conn->getAttribute(IPF_ORM::ATTR_DRIVER_NAME) !== 'mysql') {
            $params = array_merge($params, $params);
        }

        if ($this->_type !== self::SELECT) {
            return $this->_conn->exec($query, $params);
        }

        $stmt = $this->_conn->execute($query, $params);
        return $stmt;
    }

    public function execute($params = array(), $hydrationMode = null)
    {
        $this->_preQuery();

        if ($hydrationMode !== null) {
            $this->_hydrator->setHydrationMode($hydrationMode);
        }

        $params = $this->getParams($params);

        if ($this->_resultCache && $this->_type == self::SELECT) {
            $cacheDriver = $this->getResultCacheDriver();

            $dql = $this->getDql();
            // calculate hash for dql query
            $hash = md5($dql . var_export($params, true));

            $cached = ($this->_expireResultCache) ? false : $cacheDriver->fetch($hash);

            if ($cached === false) {
                // cache miss
                $stmt = $this->_execute($params);
                $this->_hydrator->setQueryComponents($this->_queryComponents);
                $result = $this->_hydrator->hydrateResultSet($stmt, $this->_tableAliasMap);

                $cached = $this->getCachedForm($result);
                $cacheDriver->save($hash, $cached, $this->getResultCacheLifeSpan());
            } else {
                $result = $this->_constructQueryFromCache($cached);
            }
        } else {
            $stmt = $this->_execute($params);

            if (is_integer($stmt)) {
                $result = $stmt;
            } else {
                $this->_hydrator->setQueryComponents($this->_queryComponents);
                $result = $this->_hydrator->hydrateResultSet($stmt, $this->_tableAliasMap);
            }
        }

        return $result;
    }

    protected function _getDqlCallback()
    {
        $callback = false;
        if ( ! empty($this->_dqlParts['from'])) {
            switch ($this->_type) {
                case self::DELETE:
                    $callback = array(
                        'callback' => 'preDqlDelete',
                        'const' => IPF_ORM_Event::RECORD_DQL_DELETE
                    );
                break;
                case self::UPDATE:
                    $callback = array(
                        'callback' => 'preDqlUpdate',
                        'const' => IPF_ORM_Event::RECORD_DQL_UPDATE
                    );
                break;
                case self::SELECT:
                    $callback = array(
                        'callback' => 'preDqlSelect',
                        'const' => IPF_ORM_Event::RECORD_DQL_SELECT
                    );
                break;
            }
        }

        return $callback;
    }

    protected function _preQuery()
    {
        if ( ! $this->_preQueried && IPF_ORM_Manager::getInstance()->getAttribute('use_dql_callbacks')) {
            $this->_preQueried = true;

            $callback = $this->_getDqlCallback();

            // if there is no callback for the query type, then we can return early
            if ( ! $callback) {
                return;
            }

            $copy = $this->copy();
            $copy->getSqlQuery();

            foreach ($copy->getQueryComponents() as $alias => $component) {
                $table = $component['table'];
                $record = $table->getRecordInstance();

                // Trigger preDql*() callback event
                $params = array('component' => $component, 'alias' => $alias);
                $event = new IPF_ORM_Event($record, $callback['const'], $this, $params);

                $record->$callback['callback']($event);
                $table->getRecordListener()->$callback['callback']($event);
            }
        }

        // Invoke preQuery() hook on IPF_ORM_Query for child classes which implement this hook
        $this->preQuery();
    }

    public function preQuery()
    {
    }

    protected function _constructQueryFromCache($cached)
    {
        $cached = unserialize($cached);
        $this->_tableAliasMap = $cached[2];
        $customComponent = $cached[0];

        $queryComponents = array();
        $cachedComponents = $cached[1];
        foreach ($cachedComponents as $alias => $components) {
            $e = explode('.', $components[0]);
            if (count($e) === 1) {
                $queryComponents[$alias]['table'] = $this->_conn->getTable($e[0]);
            } else {
                $queryComponents[$alias]['parent'] = $e[0];
                $queryComponents[$alias]['relation'] = $queryComponents[$e[0]]['table']->getRelation($e[1]);
                $queryComponents[$alias]['table'] = $queryComponents[$alias]['relation']->getTable();
            }
            if (isset($components[1])) {
                $queryComponents[$alias]['agg'] = $components[1];
            }
            if (isset($components[2])) {
                $queryComponents[$alias]['map'] = $components[2];
            }
        }
        $this->_queryComponents = $queryComponents;

        return $customComponent;
    }

    public function getCachedForm($customComponent = null)
    {
        $componentInfo = array();

        foreach ($this->getQueryComponents() as $alias => $components) {
            if ( ! isset($components['parent'])) {
                $componentInfo[$alias][] = $components['table']->getComponentName();
            } else {
                $componentInfo[$alias][] = $components['parent'] . '.' . $components['relation']->getAlias();
            }
            if (isset($components['agg'])) {
                $componentInfo[$alias][] = $components['agg'];
            }
            if (isset($components['map'])) {
                $componentInfo[$alias][] = $components['map'];
            }
        }

        return serialize(array($customComponent, $componentInfo, $this->getTableAliasMap()));
    }

    public function addSelect($select)
    {
        return $this->_addDqlQueryPart('select', $select, true);
    }

    public function addTableAlias($tableAlias, $componentAlias)
    {
        return $this->addSqlTableAlias($tableAlias, $componentAlias);
    }

    public function addSqlTableAlias($sqlTableAlias, $componentAlias)
    {
        $this->_tableAliasMap[$sqlTableAlias] = $componentAlias;
        return $this;
    }

    public function addFrom($from)
    {
        return $this->_addDqlQueryPart('from', $from, true);
    }

    public function addWhere($where, $params = array())
    {
        if (is_array($params)) {
            $this->_params['where'] = array_merge($this->_params['where'], $params);
        } else {
            $this->_params['where'][] = $params;
        }
        return $this->_addDqlQueryPart('where', $where, true);
    }

    public function whereIn($expr, $params = array(), $not = false)
    {
        $params = (array) $params;

        // if there's no params, return (else we'll get a WHERE IN (), invalid SQL)
        if (!count($params))
          return $this;

        $a = array();
        foreach ($params as $k => $value) {
            if ($value instanceof IPF_ORM_Expression) {
                $value = $value->getSql();
                unset($params[$k]);
            } else {
                $value = '?';
            }
            $a[] = $value;
        }

        $this->_params['where'] = array_merge($this->_params['where'], $params);

        $where = $expr . ($not === true ? ' NOT ':'') . ' IN (' . implode(', ', $a) . ')';

        return $this->_addDqlQueryPart('where', $where, true);
    }

    public function whereNotIn($expr, $params = array())
    {
        return $this->whereIn($expr, $params, true);
    }

    public function addGroupBy($groupby)
    {
        return $this->_addDqlQueryPart('groupby', $groupby, true);
    }

    public function addHaving($having, $params = array())
    {
        if (is_array($params)) {
            $this->_params['having'] = array_merge($this->_params['having'], $params);
        } else {
            $this->_params['having'][] = $params;
        }
        return $this->_addDqlQueryPart('having', $having, true);
    }

    public function addOrderBy($orderby)
    {
        return $this->_addDqlQueryPart('orderby', $orderby, true);
    }

    public function select($select)
    {
        return $this->_addDqlQueryPart('select', $select);
    }

    public function distinct($flag = true)
    {
        $this->_sqlParts['distinct'] = (bool) $flag;
        return $this;
    }

    public function forUpdate($flag = true)
    {
        $this->_sqlParts['forUpdate'] = (bool) $flag;
        return $this;
    }

    public function delete()
    {
        $this->_type = self::DELETE;
        return $this;
    }

    public function update($update)
    {
        $this->_type = self::UPDATE;
        return $this->_addDqlQueryPart('from', $update);
    }

    public function set($key, $value, $params = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, '?', array($v));
            }
            return $this;
        } else {
            if ($params !== null) {
                if (is_array($params)) {
                    $this->_params['set'] = array_merge($this->_params['set'], $params);
                } else {
                    $this->_params['set'][] = $params;
                }
            }

            $this->_pendingSetParams[$key] = $value;

            return $this->_addDqlQueryPart('set', $key . ' = ' . $value, true);
        }
    }

    public function from($from)
    {
        return $this->_addDqlQueryPart('from', $from);
    }

    public function innerJoin($join, $params = array())
    {
        if (is_array($params)) {
            $this->_params['join'] = array_merge($this->_params['join'], $params);
        } else {
            $this->_params['join'][] = $params;
        }

        return $this->_addDqlQueryPart('from', 'INNER JOIN ' . $join, true);
    }

    public function leftJoin($join, $params = array())
    {
        if (is_array($params)) {
            $this->_params['join'] = array_merge($this->_params['join'], $params);
        } else {
            $this->_params['join'][] = $params;
        }

        return $this->_addDqlQueryPart('from', 'LEFT JOIN ' . $join, true);
    }

    public function groupBy($groupby)
    {
        return $this->_addDqlQueryPart('groupby', $groupby);
    }

    public function where($where, $params = array())
    {
        $this->_params['where'] = array();
        if (is_array($params)) {
            $this->_params['where'] = $params;
        } else {
            $this->_params['where'][] = $params;
        }

        return $this->_addDqlQueryPart('where', $where);
    }

    public function having($having, $params = array())
    {
        $this->_params['having'] = array();
        if (is_array($params)) {
            $this->_params['having'] = $params;
        } else {
            $this->_params['having'][] = $params;
        }

        return $this->_addDqlQueryPart('having', $having);
    }

    public function orderBy($orderby)
    {
        return $this->_addDqlQueryPart('orderby', $orderby);
    }

    public function limit($limit)
    {
        return $this->_addDqlQueryPart('limit', $limit);
    }

    public function offset($offset)
    {
        return $this->_addDqlQueryPart('offset', $offset);
    }

    public function getSql($params = array())
    {
        return $this->getSqlQuery($params);
    }

    protected function clear()
    {
        $this->_sqlParts = array(
                    'select'    => array(),
                    'distinct'  => false,
                    'forUpdate' => false,
                    'from'      => array(),
                    'set'       => array(),
                    'join'      => array(),
                    'where'     => array(),
                    'groupby'   => array(),
                    'having'    => array(),
                    'orderby'   => array(),
                    'limit'     => false,
                    'offset'    => false,
                    );
    }

    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrator->setHydrationMode($hydrationMode);
        return $this;
    }

    public function getAliasMap()
    {
        return $this->_queryComponents;
    }

    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }

    public function getParts()
    {
        return $this->getSqlParts();
    }

    public function getSqlParts()
    {
        return $this->_sqlParts;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function useCache($driver = true, $timeToLive = null)
    {
        return $this->useResultCache($driver, $timeToLive);
    }

    public function useResultCache($driver = true, $timeToLive = null)
    {
        if ($driver !== null && $driver !== true && ! ($driver instanceOf IPF_ORM_Cache_Interface)) {
            $msg = 'First argument should be instance of IPF_ORM_Cache_Interface or null.';
            throw new IPF_ORM_Exception($msg);
        }
        $this->_resultCache = $driver;

        return $this->setResultCacheLifeSpan($timeToLive);
    }

    public function useQueryCache(IPF_ORM_Cache_Interface $driver, $timeToLive = null)
    {
        $this->_queryCache = $driver;
        return $this->setQueryCacheLifeSpan($timeToLive);
    }

    public function expireCache($expire = true)
    {
        return $this->expireResultCache($expire);
    }

    public function expireResultCache($expire = true)
    {
        $this->_expireResultCache = true;
        return $this;
    }

    public function expireQueryCache($expire = true)
    {
        $this->_expireQueryCache = true;
        return $this;
    }

    public function setCacheLifeSpan($timeToLive)
    {
        return $this->setResultCacheLifeSpan($timeToLive);
    }

    public function setResultCacheLifeSpan($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }
        $this->_resultCacheTTL = $timeToLive;

        return $this;
    }

    public function getResultCacheLifeSpan()
    {
        return $this->_resultCacheTTL;
    }

    public function setQueryCacheLifeSpan($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }
        $this->_queryCacheTTL = $timeToLive;

        return $this;
    }

    public function getQueryCacheLifeSpan()
    {
        return $this->_queryCacheTTL;
    }

    public function getCacheDriver()
    {
        return $this->getResultCacheDriver();
    }

    public function getResultCacheDriver()
    {
        if ($this->_resultCache instanceof IPF_ORM_Cache_Interface) {
            return $this->_resultCache;
        } else {
            return $this->_conn->getResultCacheDriver();
        }
    }

    public function getQueryCacheDriver()
    {
        if ($this->_queryCache instanceof IPF_ORM_Cache_Interface) {
            return $this->_queryCache;
        } else {
            return $this->_conn->getQueryCacheDriver();
        }
    }

    public function getConnection()
    {
        return $this->_conn;
    }

    protected function _addDqlQueryPart($queryPartName, $queryPart, $append = false)
    {
        if ($append) {
            $this->_dqlParts[$queryPartName][] = $queryPart;
        } else {
            $this->_dqlParts[$queryPartName] = array($queryPart);
        }

        $this->_state = IPF_ORM_Query::STATE_DIRTY;
        return $this;
    }

    protected function _processDqlQueryPart($queryPartName, $queryParts)
    {
        $this->removeSqlQueryPart($queryPartName);

        if (is_array($queryParts) && ! empty($queryParts)) {
            foreach ($queryParts as $queryPart) {
                $parser = $this->_getParser($queryPartName);
                $sql = $parser->parse($queryPart);
                if (isset($sql)) {
                    if ($queryPartName == 'limit' || $queryPartName == 'offset') {
                        $this->setSqlQueryPart($queryPartName, $sql);
                    } else {
                        $this->addSqlQueryPart($queryPartName, $sql);
                    }
                }
            }
        }
    }

    protected function _getParser($name)
    {
        if ( ! isset($this->_parsers[$name])) {
            $class = 'IPF_ORM_Query_' . ucwords(strtolower($name));

            //IPF_ORM::autoload($class);

            if ( ! class_exists($class)) {
                throw new IPF_ORM_Exception('Unknown parser ' . $name);
            }

            $this->_parsers[$name] = new $class($this, $this->_tokenizer);
        }

        return $this->_parsers[$name];
    }

    abstract public function getSqlQuery($params = array());

    abstract public function parseDqlQuery($query);

    public function parseQuery($query)
    {
        return $this->parseDqlQuery($query);
    }

    public function getQuery($params = array())
    {
        return $this->getSqlQuery($params);
    }
}
