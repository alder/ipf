<?php

class IPF_ORM_Builder
{
    public function varExport($var)
    {
        $export = var_export($var, true);
        $export = str_replace("\n", '', $export);
        $export = str_replace('  ', ' ', $export);
        $export = str_replace('array ( ', 'array(', $export);
        $export = str_replace('array( ', 'array(', $export);
        $export = str_replace(',)', ')', $export);
        $export = str_replace(', )', ')', $export);
        $export = str_replace('  ', ' ', $export);

        return $export;
    }
}