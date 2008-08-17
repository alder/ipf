<?php

class IPF_ORM_Query_Tokenizer
{
    public function tokenizeQuery($query)
    {
        $parts = array();
        $tokens = $this->sqlExplode($query, ' ');

        foreach ($tokens as $index => $token) {
            $token = trim($token);
            switch (strtolower($token)) {
                case 'delete':
                case 'update':
                case 'select':
                case 'set':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $p = $token;
                    //$parts[$token] = array();
                    $parts[$token] = '';
                break;
                case 'order':
                case 'group':
                    $i = ($index + 1);
                    if (isset($tokens[$i]) && strtolower($tokens[$i]) === 'by') {
                        $p = $token;
                        $parts[$token] = '';
                        //$parts[$token] = array();
                    } else {
                        $parts[$p] .= "$token ";
                        //$parts[$p][] = $token;
                    }
                break;
                case 'by':
                    continue;
                default:
                    if ( ! isset($p)) {
                        throw new IPF_ORM_Exception(
                                "Couldn't tokenize query. Encountered invalid token: '$token'.");
                    }

                    $parts[$p] .= "$token ";
                    //$parts[$p][] = $token;
            }
        }
        return $parts;
    }

    public function bracketTrim($str, $e1 = '(', $e2 = ')')
    {
        if (substr($str, 0, 1) === $e1 && substr($str, -1) === $e2) {
            return substr($str, 1, -1);
        } else {
            return $str;
        }
    }

    public function bracketExplode($str, $d = ' ', $e1 = '(', $e2 = ')')
    {
        if (is_array($d)) {
            $a = preg_split('#('.implode('|', $d).')#i', $str);
            $d = stripslashes($d[0]);
        } else {
            $a = explode($d, $str);
        }

        $i = 0;
        $term = array();
        foreach($a as $key=>$val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);
                $s1 = substr_count($term[$i], $e1);
                $s2 = substr_count($term[$i], $e2);

                if ($s1 == $s2) {
                    $i++;
                }
            } else {
                $term[$i] .= $d . trim($val);
                $c1 = substr_count($term[$i], $e1);
                $c2 = substr_count($term[$i], $e2);

                if ($c1 == $c2) {
                    $i++;
                }
            }
        }
        return $term;
    }

    public function quoteExplode($str, $d = ' ')
    {
        if (is_array($d)) {
            $a = preg_split('/('.implode('|', $d).')/', $str);
            $d = stripslashes($d[0]);
        } else {
            $a = explode($d, $str);
        }

        $i = 0;
        $term = array();
        foreach ($a as $key => $val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);

                if ( ! (substr_count($term[$i], "'") & 1)) {
                    $i++;
                }
            } else {
                $term[$i] .= $d . trim($val);

                if ( ! (substr_count($term[$i], "'") & 1)) {
                    $i++;
                }
            }
        }
        return $term;
    }

    public function sqlExplode($str, $d = ' ', $e1 = '(', $e2 = ')')
    {
        if ($d == ' ') {
            $d = array(' ', '\s');
        }
        if (is_array($d)) {
            $d = array_map('preg_quote', $d);

            if (in_array(' ', $d)) {
                $d[] = '\s';
            }

            $split = '#(' . implode('|', $d) . ')#';

            $str = preg_split($split, $str);
            $d = stripslashes($d[0]);
        } else {
            $str = explode($d, $str);
        }

        $i = 0;
        $term = array();

        foreach ($str as $key => $val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);

                $s1 = substr_count($term[$i], $e1);
                $s2 = substr_count($term[$i], $e2);

                if (strpos($term[$i], '(') !== false) {
                    if ($s1 == $s2) {
                        $i++;
                    }
                } else {
                    if ( ! (substr_count($term[$i], "'") & 1) &&
                         ! (substr_count($term[$i], "\"") & 1)) {
                        $i++;
                    }
                }
            } else {
                $term[$i] .= $d . trim($val);
                $c1 = substr_count($term[$i], $e1);
                $c2 = substr_count($term[$i], $e2);

                if (strpos($term[$i], '(') !== false) {
                    if ($c1 == $c2) {
                        $i++;
                    }
                } else {
                    if ( ! (substr_count($term[$i], "'") & 1) &&
                         ! (substr_count($term[$i], "\"") & 1)) {
                        $i++;
                    }
                }
            }
        }
        return $term;
    }

    public function clauseExplode($str, array $d, $e1 = '(', $e2 = ')')
    {
        if (is_array($d)) {
            $d = array_map('preg_quote', $d);

            if (in_array(' ', $d)) {
                $d[] = '\s';
            }

            $split = '#(' . implode('|', $d) . ')#';

            $str = preg_split($split, $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        }

        $i = 0;
        $term = array();

        foreach ($str as $key => $val) {
            if ($key & 1) {
                if (isset($term[($i - 1)]) && ! is_array($term[($i - 1)])) {
                    $term[($i - 1)] = array($term[($i - 1)], $val);
                }
                continue;
            }
            if (empty($term[$i])) {
                $term[$i] = $val;
            } else {
                $term[$i] .= $str[($key - 1)] . $val;
            }

            $c1 = substr_count($term[$i], $e1);
            $c2 = substr_count($term[$i], $e2);

            if (strpos($term[$i], '(') !== false) {
                if ($c1 == $c2) {
                    $i++;
                }
            } else {
                if ( ! (substr_count($term[$i], "'") & 1) &&
                     ! (substr_count($term[$i], "\"") & 1)) {
                    $i++;
                }
            }
        }

        if (isset($term[$i - 1])) {
            $term[$i - 1] = array($term[$i - 1], '');
        }

        return $term;
    }
}
