<?php

class IPF_Getopt {

    function getopt2($args, $short_options, $long_options = null)
    {
        return IPF_Getopt::doGetopt(2, $args, $short_options, $long_options);
    }

    function getopt($args, $short_options, $long_options = null)
    {
        return IPF_Getopt::doGetopt(1, $args, $short_options, $long_options);
    }

    function doGetopt($version, $args, $short_options, $long_options = null)
    {
        if (empty($args)) {
            return array(array(), array());
        }
        $opts     = array();
        $non_opts = array();

        settype($args, 'array');

        if ($long_options) {
            sort($long_options);
        }

        if ($version < 2) {
            if (isset($args[0]{0}) && $args[0]{0} != '-') {
                array_shift($args);
            }
        }

        reset($args);
        while (list($i, $arg) = each($args)) {

            if ($arg == '--') {
                $non_opts = array_merge($non_opts, array_slice($args, $i + 1));
                break;
            }

            if ($arg{0} != '-' || (strlen($arg) > 1 && $arg{1} == '-' && !$long_options)) {
                $non_opts = array_merge($non_opts, array_slice($args, $i));
                break;
            } elseif (strlen($arg) > 1 && $arg{1} == '-') {
                $error = IPF_Getopt::_parseLongOption(substr($arg, 2), $long_options, $opts, $args);
            } elseif ($arg == '-') {
                // - is stdin
                $non_opts = array_merge($non_opts, array_slice($args, $i));
                break;
            } else {
                $error = IPF_Getopt::_parseShortOption(substr($arg, 1), $short_options, $opts, $args);
            }
        }

        return array($opts, $non_opts);
    }

    /**
     * @access private
     *
     */
    function _parseShortOption($arg, $short_options, &$opts, &$args)
    {
        for ($i = 0; $i < strlen($arg); $i++) {
            $opt = $arg{$i};
            $opt_arg = null;

            /* Try to find the short option in the specifier string. */
            if (($spec = strstr($short_options, $opt)) === false || $arg{$i} == ':')
            {
                throw new IPF_Exception("IPF_Getopt: unrecognized option -- $opt");
            }

            if (strlen($spec) > 1 && $spec{1} == ':') {
                if (strlen($spec) > 2 && $spec{2} == ':') {
                    if ($i + 1 < strlen($arg)) {
                        /* Option takes an optional argument. Use the remainder of
                           the arg string if there is anything left. */
                        $opts[] = array($opt, substr($arg, $i + 1));
                        break;
                    }
                } else {
                    /* Option requires an argument. Use the remainder of the arg
                       string if there is anything left. */
                    if ($i + 1 < strlen($arg)) {
                        $opts[] = array($opt,  substr($arg, $i + 1));
                        break;
                    } else if (list(, $opt_arg) = each($args)) {
                        /* Else use the next argument. */;
                        if (IPF_Getopt::_isShortOpt($opt_arg) || IPF_Getopt::_isLongOpt($opt_arg)) {
                            throw new IPF_Exception("IPF_Getopt: option requires an argument -- $opt");
                        }
                    } else {
                        throw new IPF_Exception("IPF_Getopt: option requires an argument -- $opt");
                    }
                }
            }

            $opts[] = array($opt, $opt_arg);
        }
    }

    /**
     * @access private
     *
     */
    function _isShortOpt($arg)
    {
        return strlen($arg) == 2 && $arg[0] == '-' && preg_match('/[a-zA-Z]/', $arg[1]);
    }

    /**
     * @access private
     *
     */
    function _isLongOpt($arg)
    {
        return strlen($arg) > 2 && $arg[0] == '-' && $arg[1] == '-' &&
            preg_match('/[a-zA-Z]+$/', substr($arg, 2));
    }

    /**
     * @access private
     *
     */
    function _parseLongOption($arg, $long_options, &$opts, &$args)
    {
        @list($opt, $opt_arg) = explode('=', $arg, 2);
        $opt_len = strlen($opt);

        for ($i = 0; $i < count($long_options); $i++) {
            $long_opt  = $long_options[$i];
            $opt_start = substr($long_opt, 0, $opt_len);
            $long_opt_name = str_replace('=', '', $long_opt);

            /* Option doesn't match. Go on to the next one. */
            if ($long_opt_name != $opt) {
                continue;
            }

            $opt_rest  = substr($long_opt, $opt_len);

            /* Check that the options uniquely matches one of the allowed
               options. */
            if ($i + 1 < count($long_options)) {
                $next_option_rest = substr($long_options[$i + 1], $opt_len);
            } else {
                $next_option_rest = '';
            }
            if ($opt_rest != '' && $opt{0} != '=' &&
                $i + 1 < count($long_options) &&
                $opt == substr($long_options[$i+1], 0, $opt_len) &&
                $next_option_rest != '' &&
                $next_option_rest{0} != '=') {
                throw new IPF_Exception("IPF_Getopt: option --$opt is ambiguous");
            }

            if (substr($long_opt, -1) == '=') {
                if (substr($long_opt, -2) != '==') {
                    /* Long option requires an argument.
                       Take the next argument if one wasn't specified. */;
                    if (!strlen($opt_arg) && !(list(, $opt_arg) = each($args))) {
                        throw new IPF_Exception("IPF_Getopt: option --$opt requires an argument");
                    }
                    if (IPF_Getopt::_isShortOpt($opt_arg) || IPF_Getopt::_isLongOpt($opt_arg)) {
                        throw new IPF_Exception("IPF_Getopt: option requires an argument --$opt");
                    }
                }
            } else if ($opt_arg) {
                throw new IPF_Exception("IPF_Getopt: option --$opt doesn't allow an argument");
            }

            $opts[] = array('--' . $opt, $opt_arg);
            return;
        }
        throw new IPF_Exception("IPF_Getopt: unrecognized option --$opt");
    }

    function readPHPArgv()
    {
        global $argv;
        if (!is_array($argv)) {
            if (!@is_array($_SERVER['argv'])) {
                if (!@is_array($GLOBALS['HTTP_SERVER_VARS']['argv'])) {
                    throw new IPF_Exception("IPF_Getopt: Could not read cmd args (register_argc_argv=Off?)");
                }
                return $GLOBALS['HTTP_SERVER_VARS']['argv'];
            }
            return $_SERVER['argv'];
        }
        return $argv;
    }
}

?>
