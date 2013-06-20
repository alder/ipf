<?php

class IPF_Shell
{
    public static function call()
    {
        return self::callv(func_get_args());
    }

    public static function callv($command)
    {
        $str = '';
        foreach ($command as $part) {
            $str .= escapeshellarg($part) . ' ';
        }
        $descriptorspec = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        );
        $process = proc_open($str, $descriptorspec, $pipes);
        proc_close($process);
    }

    public static function displayTwoColumns($rows, $firstColumnMin=7, $firstColumnMax=47)
    {
        $firstColumnSize = $firstColumnMin;
        foreach ($rows as $row) {
            $l = strlen($row[0]);
            if ($l > $firstColumnSize)
                $firstColumnSize = $l;
        }
        if ($firstColumnSize > $firstColumnMax)
            $firstColumnSize = $firstColumnMax;
        foreach ($rows as $row) {
            echo str_pad($row[0], $firstColumnSize) . "\t" . $row[1] . "\n";
        }
    }
}

