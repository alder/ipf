<?php

class IPF_HTTP
{
    function removeTheMagic()
    {
        if (get_magic_quotes_gpc()) {
            if (!empty($_GET)) {
                array_walk($_GET, 'IPF_HTTP_magicStrip');
            }
            if (!empty($_POST)) {
                array_walk($_POST, 'IPF_HTTP_magicStrip');
            }
            if (!empty($_REQUEST)) {
                array_walk($_REQUEST, 'IPF_HTTP_magicStrip');
            }
            if (!empty($_COOKIE)) {
                array_walk($_COOKIE, 'IPF_HTTP_magicStrip');
            }
        }
        if (function_exists('ini_set')) {
            @ini_set('session.use_cookies', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.use_trans_sid', '0');
            @ini_set('url_rewriter.tags', '');
        }
    }
}

function IPF_HTTP_magicStrip(&$k, $key)
{
    $k = IPF_HTTP_handleMagicQuotes($k);
}

function IPF_HTTP_handleMagicQuotes(&$value)
{
    if (is_array($value)) {
        $result = array();
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $result[$k] = IPF_HTTP_handleMagicQuotes($v);
            } else {
                $result[$k] = stripslashes($v);
            }
        }
        return $result;
    } else {
        return stripslashes($value);
    }
}
