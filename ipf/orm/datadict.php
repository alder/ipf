<?php

class IPF_ORM_DataDict extends IPF_ORM_Connection_Module
{
    public function compareDefinition($current, $previous)
    {
        $type = !empty($current['type']) ? $current['type'] : null;

        if ( ! method_exists($this, "_compare{$type}Definition")) {
            throw new IPF_ORM_Exception('type "'.$current['type'].'" is not yet supported');
        }

        if (empty($previous['type']) || $previous['type'] != $type) {
            return $current;
        }

        $change = $this->{"_compare{$type}Definition"}($current, $previous);

        if ($previous['type'] != $type) {
            $change['type'] = true;
        }

        $previous_notnull = !empty($previous['notnull']) ? $previous['notnull'] : false;
        $notnull = !empty($current['notnull']) ? $current['notnull'] : false;
        if ($previous_notnull != $notnull) {
            $change['notnull'] = true;
        }

        $previous_default = array_key_exists('default', $previous) ? $previous['default'] :
            ($previous_notnull ? '' : null);
        $default = array_key_exists('default', $current) ? $current['default'] :
            ($notnull ? '' : null);
        if ($previous_default !== $default) {
            $change['default'] = true;
        }

        return $change;
    }

    public function parseBoolean($value)
    {
        // parse booleans
        if ($value == 'true') {
            $value = 1;
        } elseif ($value == 'false') {
            $value = 0;
        }
        return $value;
    }
}