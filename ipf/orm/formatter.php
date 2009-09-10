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

    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $k => $value) {
                if (is_bool($value)) {
                    $item[$k] = (int) $value;
                }
            }
        } else {
            if (is_bool($item)) {
                $item = (int) $item;
            }
        }
        return $item;
    }

    public function quoteIdentifier($str, $checkOption = true)
    {
        if ($checkOption && ! $this->conn->getAttribute(IPF_ORM::ATTR_QUOTE_IDENTIFIER)) {
            return $str;
        }
        $tmp = $this->conn->identifier_quoting;
        $str = str_replace($tmp['end'],
            $tmp['escape'] .
            $tmp['end'], $str);

        return $tmp['start'] . $str . $tmp['end'];
    }
    
    public function quoteMultipleIdentifier($arr, $checkOption = true)
    {
        foreach ($arr as $k => $v) {
            $arr[$k] = $this->quoteIdentifier($v, $checkOption);
        }

        return $arr;
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
            $this->conn->connect();

            return $this->conn->getDbh()->quote($input);
        }
    }

    public function fixSequenceName($sqn)
    {
        $seqPattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)',  $this->conn->getAttribute(IPF_ORM::ATTR_SEQNAME_FORMAT)).'$/i';
        $seqName    = preg_replace($seqPattern, '\\1', $sqn);

        if ($seqName && ! strcasecmp($sqn, $this->getSequenceName($seqName))) {
            return $seqName;
        }
        return $sqn;
    }

    public function fixIndexName($idx)
    {
        $indexPattern   = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $this->conn->getAttribute(IPF_ORM::ATTR_IDXNAME_FORMAT)).'$/i';
        $indexName      = preg_replace($indexPattern, '\\1', $idx);
        if ($indexName && ! strcasecmp($idx, $this->getIndexName($indexName))) {
            return $indexName;
        }
        return $idx;
    }

    public function getSequenceName($sqn)
    {
        return sprintf($this->conn->getAttribute(IPF_ORM::ATTR_SEQNAME_FORMAT),
            preg_replace('/[^a-z0-9_\$.]/i', '_', $sqn));
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
