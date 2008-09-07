<?php

class IPF_Mail_Protocol_Imap
{
    protected $_socket;
    protected $_tagCount = 0;

    function __construct($host = '', $port = null, $ssl = false)
    {
        if ($host) {
            $this->connect($host, $port, $ssl);
        }
    }

    public function __destruct()
    {
        $this->logout();
    }

    public function connect($host, $port = null, $ssl = false)
    {
        if ($ssl == 'SSL') {
            $host = 'ssl://' . $host;
        }

        if ($port === null) {
            $port = $ssl === 'SSL' ? 993 : 143;
        }

        $this->_socket = @fsockopen($host, $port);
        if (!$this->_socket) {
            throw new IPF_Exception_Mail('cannot connect to host');
        }

        if (!$this->_assumedNextLine('* OK')) {
            throw new IPF_Exception_Mail('host doesn\'t allow connection');
        }

        if ($ssl === 'TLS') {
            $result = $this->requestAndResponse('STARTTLS');
            $result = $result && stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$result) {
                throw new IPF_Exception_Mail('cannot enable TLS');
            }
        }
    }

    protected function _nextLine()
    {
        $line = @fgets($this->_socket);
        if ($line === false) {
            throw new IPF_Exception_Mail('cannot read - connection closed?');
        }

        return $line;
    }

    protected function _assumedNextLine($start)
    {
        $line = $this->_nextLine();
        return strpos($line, $start) === 0;
    }

    protected function _nextTaggedLine(&$tag)
    {
        $line = $this->_nextLine();

        // seperate tag from line
        list($tag, $line) = explode(' ', $line, 2);

        return $line;
    }

    protected function _decodeLine($line)
    {
        $tokens = array();
        $stack = array();

        /*
            We start to decode the response here. The unterstood tokens are:
                literal
                "literal" or also "lit\\er\"al"
                {bytes}<NL>literal
                (literals*)
            All tokens are returned in an array. Literals in braces (the last unterstood
            token in the list) are returned as an array of tokens. I.e. the following response:
                "foo" baz {3}<NL>bar ("f\\\"oo" bar)
            would be returned as:
                array('foo', 'baz', 'bar', array('f\\\"oo', 'bar'));
                
            // TODO: add handling of '[' and ']' to parser for easier handling of response text 
        */
        //  replace any trailling <NL> including spaces with a single space
        $line = rtrim($line) . ' ';
        while (($pos = strpos($line, ' ')) !== false) {
            $token = substr($line, 0, $pos);
            while ($token[0] == '(') {
                array_push($stack, $tokens);
                $tokens = array();
                $token = substr($token, 1);
            }
            if ($token[0] == '"') {
                if (preg_match('%^"((.|\\\\|\\")*?)" *%', $line, $matches)) {
                    $tokens[] = $matches[1];
                    $line = substr($line, strlen($matches[0]));
                    continue;
                }
            }
            if ($token[0] == '{') {
                $endPos = strpos($token, '}');
                $chars = substr($token, 1, $endPos - 1);
                if (is_numeric($chars)) {
                    $token = '';
                    while (strlen($token) < $chars) {
                        $token .= $this->_nextLine();
                    }
                    $line = '';
                    if (strlen($token) > $chars) {
                        $line = substr($token, $chars);
                        $token = substr($token, 0, $chars);
                    } else {
                        $line .= $this->_nextLine();
                    }
                    $tokens[] = $token;
                    $line = trim($line) . ' ';
                    continue;
                }
            }
            if ($stack && $token[strlen($token) - 1] == ')') {
                // closing braces are not seperated by spaces, so we need to count them
                $braces = strlen($token);
                $token = rtrim($token, ')');
                // only count braces if more than one
                $braces -= strlen($token) + 1;
                // only add if token had more than just closing braces
                if ($token) {
                    $tokens[] = $token;
                }
                $token = $tokens;
                $tokens = array_pop($stack);
                // special handline if more than one closing brace
                while ($braces-- > 0) {
                    $tokens[] = $token;
                    $token = $tokens;
                    $tokens = array_pop($stack);
                }
            }
            $tokens[] = $token;
            $line = substr($line, $pos + 1);
        }

        // maybe the server forgot to send some closing braces
        while ($stack) {
            $child = $tokens;
            $tokens = array_pop($stack);
            $tokens[] = $child;
        }

        return $tokens;
    }

    public function readLine(&$tokens = array(), $wantedTag = '*', $dontParse = false)
    {
        $line = $this->_nextTaggedLine($tag);
        if (!$dontParse) {
            $tokens = $this->_decodeLine($line);
        } else {
            $tokens = $line;
        }

        // if tag is wanted tag we might be at the end of a multiline response
        return $tag == $wantedTag;
    }

    public function readResponse($tag, $dontParse = false)
    {
        $lines = array();
        while (!$this->readLine($tokens, $tag, $dontParse)) {
            $lines[] = $tokens;
        }

        if ($dontParse) {
            // last to chars are still needed for response code
            $tokens = array(substr($tokens, 0, 2));
        }
        // last line has response code
        if ($tokens[0] == 'OK') {
            return $lines ? $lines : true;
        } else if ($tokens[0] == 'NO'){
            return false;
        }
        return null;
    }

    public function sendRequest($command, $tokens = array(), &$tag = null)
    {
        if (!$tag) {
            ++$this->_tagCount;
            $tag = 'TAG' . $this->_tagCount;
        }

        $line = $tag . ' ' . $command;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (@fputs($this->_socket, $line . ' ' . $token[0] . "\r\n") === false) {
                    throw new IPF_Exception_Mail('cannot write - connection closed?');
                }
                if (!$this->_assumedNextLine('+ ')) {
                    throw new IPF_Exception_Mail('cannot send literal string');
                }
                $line = $token[1];
            } else {
                $line .= ' ' . $token;
            }
        }

        if (@fputs($this->_socket, $line . "\r\n") === false) {
            throw new IPF_Exception_Mail('cannot write - connection closed?');
        }
    }

    public function requestAndResponse($command, $tokens = array(), $dontParse = false)
    {
        $this->sendRequest($command, $tokens, $tag);
        $response = $this->readResponse($tag, $dontParse);

        return $response;
    }

    public function escapeString($string)
    {
        if (func_num_args() < 2) {
            if (strpos($string, "\n") !== false) {
                return array('{' . strlen($string) . '}', $string);
            } else {
                return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $string) . '"';
            }
        }
        $result = array();
        foreach (func_get_args() as $string) {
            $result[] = $this->escapeString($string);
        }
        return $result;
    }

    public function escapeList($list)
    {
        $result = array();
        foreach ($list as $k => $v) {
            if (!is_array($v)) {
//              $result[] = $this->escapeString($v);
                $result[] = $v;
                continue;
            }
            $result[] = $this->escapeList($v);
        }
        return '(' . implode(' ', $result) . ')';
    }

    public function login($user, $password)
    {
        return $this->requestAndResponse('LOGIN', $this->escapeString($user, $password), true);
    }

    public function logout()
    {
        $result = false;
        if ($this->_socket) {
            try {
                $result = $this->requestAndResponse('LOGOUT', array(), true);
            } catch (IPF_Exception_Mail $e) {
                // ignoring exception
            }
            fclose($this->_socket);
            $this->_socket = null;
        }
        return $result;
    }

    public function capability()
    {
        $response = $this->requestAndResponse('CAPABILITY');

        if (!$response) {
            return $response;
        }

        $capabilities = array();
        foreach ($response as $line) {
            $capabilities = array_merge($capabilities, $line);
        }
        return $capabilities;
    }

    public function examineOrSelect($command = 'EXAMINE', $box = 'INBOX')
    {
        $this->sendRequest($command, array($this->escapeString($box)), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            if ($tokens[0] == 'FLAGS') {
                array_shift($tokens);
                $result['flags'] = $tokens;
                continue;
            }
            switch ($tokens[1]) {
                case 'EXISTS':
                case 'RECENT':
                    $result[strtolower($tokens[1])] = $tokens[0];
                    break;
                case '[UIDVALIDITY':
                    $result['uidvalidity'] = (int)$tokens[2];
                    break;
                default:
                    // ignore
            }
        }

        if ($tokens[0] != 'OK') {
            return false;
        }
        return $result;
    }

    public function select($box = 'INBOX')
    {
        return $this->examineOrSelect('SELECT', $box);
    }

    public function examine($box = 'INBOX')
    {
        return $this->examineOrSelect('EXAMINE', $box);
    }

    public function fetch($items, $from, $to = null)
    {
        if (is_array($from)) {
            $set = implode(',', $from);
        } else if ($to === null) {
            $set = (int)$from;
        } else if ($to === INF) {
            $set = (int)$from . ':*';
        } else {
            $set = (int)$from . ':' . (int)$to;
        }

        $items = (array)$items;
        $itemList = $this->escapeList($items);

        $this->sendRequest('FETCH', array($set, $itemList), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            // ignore other responses
            if ($tokens[1] != 'FETCH') {
                continue;
            }
            // ignore other messages
            if ($to === null && !is_array($from) && $tokens[0] != $from) {
                continue;
            }
            // if we only want one item we return that one directly
            if (count($items) == 1) {
                if ($tokens[2][0] == $items[0]) {
                    $data = $tokens[2][1];
                } else {
                    // maybe the server send an other field we didn't wanted
                    $count = count($tokens[2]);
                    // we start with 2, because 0 was already checked
                    for ($i = 2; $i < $count; $i += 2) {
                        if ($tokens[2][$i] != $items[0]) {
                            continue;
                        }
                        $data = $tokens[2][$i + 1];
                        break;
                    }
                }
            } else {
                $data = array();
                while (key($tokens[2]) !== null) {
                    $data[current($tokens[2])] = next($tokens[2]);
                    next($tokens[2]);
                }
            }
            // if we want only one message we can ignore everything else and just return
            if ($to === null && !is_array($from) && $tokens[0] == $from) {
                // we still need to read all lines
                while (!$this->readLine($tokens, $tag));
                return $data;
            }
            $result[$tokens[0]] = $data;
        }

        if ($to === null && !is_array($from)) {
            throw new IPF_Exception_Mail('the single id was not found in response');
        }

        return $result;
    }

    public function listMailbox($reference = '', $mailbox = '*')
    {
        $result = array();
        $list = $this->requestAndResponse('LIST', $this->escapeString($reference, $mailbox));
        if (!$list || $list === true) {
            return $result;
        }

        foreach ($list as $item) {
            if (count($item) != 4 || $item[0] != 'LIST') {
                continue;
            }
            $result[$item[3]] = array('delim' => $item[2], 'flags' => $item[1]);
        }

        return $result;
    }

    public function store(array $flags, $from, $to = null, $mode = null, $silent = true)
    {
        $item = 'FLAGS';
        if ($mode == '+' || $mode == '-') {
            $item = $mode . $item;
        }
        if ($silent) {
            $item .= '.SILENT';
        }

        $flags = $this->escapeList($flags);
        $set = (int)$from;
        if ($to != null) {
            $set .= ':' . ($to == INF ? '*' : (int)$to);
        }

        $result = $this->requestAndResponse('STORE', array($set, $item, $flags), $silent);

        if ($silent) {
            return $result ? true : false;
        }

        $tokens = $result;
        $result = array();
        foreach ($tokens as $token) {
            if ($token[1] != 'FETCH' || $token[2][0] != 'FLAGS') {
                continue;
            }
            $result[$token[0]] = $token[2][1];
        }

        return $result;
    }

    public function append($folder, $message, $flags = null, $date = null)
    {
        $tokens = array();
        $tokens[] = $this->escapeString($folder);
        if ($flags !== null) {
            $tokens[] = $this->escapeList($flags);
        }
        if ($date !== null) {
            $tokens[] = $this->escapeString($date);
        }
        $tokens[] = $this->escapeString($message);

        return $this->requestAndResponse('APPEND', $tokens, true);
    }

    public function copy($folder, $from, $to = null)
    {
        $set = (int)$from;
        if ($to != null) {
            $set .= ':' . ($to == INF ? '*' : (int)$to);
        }

        return $this->requestAndResponse('COPY', array($set, $this->escapeString($folder)), true);
    }

    public function create($folder)
    {
        return $this->requestAndResponse('CREATE', array($this->escapeString($folder)), true);
    }

    public function rename($old, $new)
    {
        return $this->requestAndResponse('RENAME', $this->escapeString($old, $new), true);
    }

    public function delete($folder)
    {
        return $this->requestAndResponse('DELETE', array($this->escapeString($folder)), true);
    }

    public function expunge()
    {
        // TODO: parse response?
        return $this->requestAndResponse('EXPUNGE');
    }

    public function noop()
    {
        // TODO: parse response
        return $this->requestAndResponse('NOOP');
    }
}
