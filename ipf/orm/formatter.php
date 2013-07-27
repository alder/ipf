<?php

class IPF_ORM_Formatter extends IPF_ORM_Connection_Module
{
    public function escapePattern($text)
    {
        if ( ! $this->string_quoting['escape_pattern']) {
            return $text;
        }
        $tmp = $this->conn->string_quoting;

        $text = str_replace($tmp['escape_pattern'], 
            $tmp['escape_pattern'] .
            $tmp['escape_pattern'], $text);

        foreach ($this->wildcards as $wildcard) {
            $text = str_replace($wildcard, $tmp['escape_pattern'] . $wildcard, $text);
        }
        return $text;
    }

    public function quote($input, $type = null)
    {
        if ($type == null) {
            $type = gettype($input);
        }
        switch ($type) {
        case 'integer':
        case 'enum':
        case 'boolean':
        case 'double':
        case 'float':
        case 'bool':
        case 'decimal':
        case 'int':
            return $input;
        case 'array':
        case 'object':
            $input = serialize($input);
        case 'date':
        case 'time':
        case 'timestamp':
        case 'string':
        case 'char':
        case 'varchar':
        case 'text':
        case 'gzip':
        case 'blob':
        case 'clob':
            return $this->conn->getDbh()->quote($input);
        }
    }

    public function getIndexName($idx)
    {
        return sprintf($this->conn->getAttribute(IPF_ORM::ATTR_IDXNAME_FORMAT),
            preg_replace('/[^a-z0-9_\$]/i', '_', $idx));
    }

    public function getTableName($table)
    {
        return sprintf($this->conn->getAttribute(IPF_ORM::ATTR_TBLNAME_FORMAT),
                $table);
    }
}

