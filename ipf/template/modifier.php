<?php

final class IPF_Template_Modifier
{
    public static function escape($string)
    {
        return htmlspecialchars((string)$string, ENT_COMPAT, 'UTF-8');
    }

    public static function dateFormat($date, $format='%b %e, %Y')
    {
        if (substr(PHP_OS,0,3) == 'WIN') {
            $_win_from = array ('%e',  '%T',       '%D');
            $_win_to   = array ('%#d', '%H:%M:%S', '%m/%d/%y');
            $format	= str_replace($_win_from, $_win_to, $format);
        }
        $date = date('Y-m-d H:i:s', strtotime($date.' GMT'));
        return strftime($format, strtotime($date));
    }

    public static function timeFormat($time, $format='Y-m-d H:i:s')
    {
        return date($format, $time);
    }

    public static function floatFormat($number, $decimals=2, $dec_point='.', $thousands_sep=',')
    {
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }

    /**
     * Word Limiter
     *
     * Limits a string to X number of words.
     *
     * @param	string
     * @param	integer
     * @param	string	the end character. Usually an ellipsis
     * @return	string
     */
    public static function limitWords($str, $limit=100, $end_char='&#8230;')
    {
        if (trim($str) == '')
            return $str;
        preg_match('/^\s*+(?:\S++\s*+){1,'.(int) $limit.'}/', $str, $matches);
        if (strlen($str) == strlen($matches[0]))
            $end_char = '';
        return rtrim($matches[0]).$end_char;
    }

    /**
     * Character Limiter
     *
     * Limits the string based on the character count.  Preserves complete words
     * so the character count may not be exactly as specified.
     *
     * @param	string
     * @param	integer
     * @param	string	the end character. Usually an ellipsis
     * @return	string
     */
    function limitCharacters($str, $n=500, $end_char='&#8230;')
    {
        if (strlen($str) < $n)
            return $str;

        $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

        if (strlen($str) <= $n)
            return $str;

        $out = "";
        foreach (explode(' ', trim($str)) as $val) {
            $out .= $val.' ';
            if (strlen($out) >= $n) {
                $out = trim($out);
                return (strlen($out) == strlen($str)) ? $out : $out.$end_char;
            }
        }
    }
}

