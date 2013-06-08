<?php

class IPF_ORM_Connection_Mysql extends IPF_ORM_Connection
{
    protected $driverName = 'Mysql';

    public function __construct(IPF_ORM_Manager $manager, $adapter)
    {
        $this->setAttribute(IPF_ORM::ATTR_DEFAULT_TABLE_TYPE, 'INNODB');
        $this->supported = array(
                          'sequences'            => 'emulated',
                          'indexes'              => true,
                          'affected_rows'        => true,
                          'transactions'         => true,
                          'savepoints'           => false,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => 'emulated',
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => true,
                          'sub_selects'          => true,
                          'auto_increment'       => true,
                          'primary_key'          => true,
                          'result_introspection' => true,
                          'prepared_statements'  => 'emulated',
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => true
                          );

        $this->properties['string_quoting'] = array('start' => "'",
                                                    'end' => "'",
                                                    'escape' => '\\',
                                                    'escape_pattern' => '\\');

        $this->properties['identifier_quoting'] = array('start' => '`',
                                                        'end' => '`',
                                                        'escape' => '`');

        $this->properties['sql_comments'] = array(
                                            array('start' => '-- ', 'end' => "\n", 'escape' => false),
                                            array('start' => '#', 'end' => "\n", 'escape' => false),
                                            array('start' => '/*', 'end' => '*/', 'escape' => false),
                                            );

        $this->properties['varchar_max_length'] = 255;

        parent::__construct($manager, $adapter);
    }

    protected function onConnect()
    {
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->exec('SET NAMES \'utf8\'');
    }

    public function getDatabaseName()
    {
        return $this->fetchOne('SELECT DATABASE()');
    }

    public function replace(IPF_ORM_Table $table, array $fields, array $keys)
    {
        if (empty($keys)) {
            throw new IPF_ORM_Exception('Not specified which fields are keys');
        }
        $columns = array();
        $values = array();
        $params = array();
        foreach ($fields as $fieldName => $value) {
            $columns[] = $table->getColumnName($fieldName);
            $values[] = '?';
            $params[] = $value;
        }
        $query = 'REPLACE INTO ' . $table->getTableName() . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
        return $this->exec($query, $params);
    }

    public function modifyLimitQuery($query, $limit = false,$offset = false,$isManip=false)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;
        
        if ($limit && $offset) {
            $query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        } elseif ($limit && ! $offset) {
            $query .= ' LIMIT ' . $limit;
        } elseif ( ! $limit && $offset) {
            $query .= ' LIMIT 999999999999 OFFSET ' . $offset;
        }
        return $query;
    }
}
