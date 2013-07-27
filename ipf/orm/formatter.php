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
}

