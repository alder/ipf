<?php

class IPF_Template_Compiler
{
    private static $allowedInVar = null,
                   $allowedInExpr = null,
                   $allowedAssign = null,
                   $allowedForeach = null;

    public static function init()
    {
        $vartype = array(T_CHARACTER, T_CONSTANT_ENCAPSED_STRING, T_DNUMBER,
            T_ENCAPSED_AND_WHITESPACE, T_LNUMBER, T_OBJECT_OPERATOR, T_STRING,
            T_WHITESPACE, T_ARRAY, T_VARIABLE);

        $assignOp = array(T_AND_EQUAL, T_DIV_EQUAL, T_MINUS_EQUAL, T_MOD_EQUAL,
            T_MUL_EQUAL, T_OR_EQUAL, T_PLUS_EQUAL, T_PLUS_EQUAL, T_SL_EQUAL,
            T_SR_EQUAL, T_XOR_EQUAL);

        $op = array(T_BOOLEAN_AND, T_BOOLEAN_OR, T_EMPTY, T_INC, T_ISSET,
            T_IS_EQUAL, T_IS_GREATER_OR_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL,
            T_IS_NOT_IDENTICAL, T_IS_SMALLER_OR_EQUAL, T_LOGICAL_AND,
            T_LOGICAL_OR, T_LOGICAL_XOR, T_SR, T_SL, T_DOUBLE_ARROW);

        self::$allowedInVar   = array_merge($vartype, $op);
        self::$allowedInExpr  = array_merge($vartype, $op);
        self::$allowedAssign  = array_merge($vartype, $op, $assignOp);
        self::$allowedForeach = array_merge(self::$allowedInExpr, array(T_AS));
    }

    protected $_modifier = array(
        'upper'       => 'strtoupper',
        'lower'       => 'strtolower',
        'escxml'      => 'htmlspecialchars',
        'escape'      => 'IPF_Utils::escape',
        'strip_tags'  => 'strip_tags',
        'escurl'      => 'rawurlencode',
        'capitalize'  => 'ucwords',
        'debug'       => 'print_r', // Not var_export because of recursive issues.
        'fulldebug'   => 'var_export',
        'count'       => 'count',
        'nl2br'       => 'nl2br',
        'trim'        => 'trim',
        'unsafe'      => 'IPF_Template_SafeString::markSafe',
        'safe'        => 'IPF_Template_SafeString::markSafe',
        'date'        => 'IPF_Template_dateFormat',
        'time'        => 'IPF_Template_timeFormat',
        'floatformat' => 'IPF_Template_floatFormat',
        'limit_words' => 'IPF_Utils::limitWords',
    );

    protected $_literals;

    public $_usedModifiers = array();

    protected $_blockStack = array();

    protected $_transStack = array();
    protected $_transPlural = false;

    private $environment;

    public $templateContent = '';

    public $_extendBlocks = array();

    function __construct($templateContent, $environment)
    {
        $modifiers = IPF::get('template_modifiers', array());
        $this->_modifier = array_merge($modifiers, $this->_modifier);

        $this->templateContent = $templateContent;
        $this->environment = $environment;
    }

    private function compile()
    {
        $this->compileBlocks();
        $tplcontent = $this->templateContent;
        $tplcontent = preg_replace('!{\*(.*?)\*}!s', '', $tplcontent);
        $tplcontent = preg_replace('!<\?php(.*?)\?>!s', '', $tplcontent);
        preg_match_all('!{literal}(.*?){/literal}!s', $tplcontent, $_match);
        $this->_literals = $_match[1];
        $tplcontent = preg_replace("!{literal}(.*?){/literal}!s", '{literal}', $tplcontent);
        // Core regex to parse the template
        $result = preg_replace_callback('/{((.).*?)}/s',
                                        array($this, '_callback'),
                                        $tplcontent);
        if (count($this->_blockStack)) {
            trigger_error(sprintf(__('End tag of a block missing: %s'), end($this->_blockStack)), E_USER_ERROR);
        }
        return $result;
    }

    public function getCompiledTemplate()
    {
        $result = $this->compile();
        if (count($this->_usedModifiers)) {
            $code = array();
            foreach ($this->_usedModifiers as $modifier) {
                $code[] = 'IPF::loadFunction(\''.$modifier.'\'); ';
            }
            $result = '<?php '.implode("\n", $code).'?>'.$result;
        }
        $result = str_replace(array('?><?php', '<?php ?>', '<?php  ?>'), '', $result);
        $result = str_replace("?>\n", "?>\n\n", $result);
        return $result;
    }

    private static function toplevelBlocks($content)
    {
        preg_match_all("!{block\s*([^} \r\t\n]*)}|{/block}!", $content, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $count = 0;
        $result = array();
        foreach ($tags as $tag) {
            $text = $tag[0][0];

            if (substr($text, 0, 6) === '{block') {
                if ($count == 0) {
                    $result[] = array(
                        'name' => $tag[1][0],
                        'start' => $tag[1][1] + strlen($tag[1][0]) + 1,
                    );
                }
                $count++;
            } elseif (substr($text, 0, 7) === '{/block') {
                $count--;
                if ($count == 0) {
                    $result[count($result)-1]['finish'] = $tag[0][1];
                }
            }
        }

        if ($count != 0)
            throw new IPF_Exception(sprintf(__('Blocks are not nested properly.')));

        return $result;
    }

    function compileBlocks()
    {
        $tplcontent = $this->templateContent;
        $extendedTemplate = '';
        // Match extends on the first line of the template
        if (preg_match("!{extends\s['\"](.*?)['\"]}!", $tplcontent, $_match)) {
            $extendedTemplate = $_match[1];
        }
        // Get the blocks in the current template
        $blocks = self::toplevelBlocks($this->templateContent);
        $cnt = count($blocks);
        // Compile the blocks
        for ($i=0; $i<$cnt; $i++) {
            $blockName = $blocks[$i]['name'];
            if (!isset($this->_extendBlocks[$blockName]) or false !== strpos($this->_extendBlocks[$blockName], '~~{~~superblock~~}~~')) {
                $compiler = clone($this);
                $compiler->templateContent = substr($this->templateContent, $blocks[$i]['start'], $blocks[$i]['finish'] - $blocks[$i]['start']);
                $_tmp = $compiler->compile();
                $this->updateModifierStack($compiler);
                if (!isset($this->_extendBlocks[$blockName])) {
                    $this->_extendBlocks[$blockName] = $_tmp;
                } else {
                    $this->_extendBlocks[$blockName] = str_replace('~~{~~superblock~~}~~', $_tmp, $this->_extendBlocks[$blockName]);
                }
            }
        }
        if (strlen($extendedTemplate) > 0) {
            // The template of interest is now the extended template
            // as we are not in a base template
            $this->templateContent = $this->environment->loadTemplateFile($extendedTemplate);
            $this->compileBlocks(); //It will recurse to the base template.
        } else {
            // Replace the current blocks by a place holder
            if ($cnt) {
                $this->templateContent = preg_replace("!{block\s(\S+?)}(.*?){/block}!s", "{block $1}", $tplcontent, -1);
            }
        }
    }

    private function _callback($matches)
    {
        list(,$tag, $firstcar) = $matches;
        if (!preg_match('/^\$|[\'"]|[a-zA-Z\/]$/', $firstcar)) {
            trigger_error(sprintf(__('Invalid tag syntax: %s'), $tag), E_USER_ERROR);
            return '';
        }
        if (in_array($firstcar, array('$', '\'', '"'))) {
            if ('blocktrans' !== end($this->_blockStack)) {
                return '<?php echo IPF_Template_SafeString::value('.$this->_parseVariable($tag).'); ?>';
            } else {
                $tok = explode('|', $tag);
                $this->_transStack[substr($tok[0], 1)] = $this->_parseVariable($tag);
                return '%%'.substr($tok[0], 1).'%%';
            }
        } else {
            if (!preg_match('/^(\/?[a-zA-Z0-9_]+)(?:(?:\s+(.*))|(?:\((.*)\)))?$/', $tag, $m)) {
                trigger_error(sprintf(__('Invalid function syntax: %s'), $tag), E_USER_ERROR);
                return '';
            }
            if (count($m) == 4){
                $m[2] = $m[3];
            }
            if (!isset($m[2])) $m[2] = '';
            if($m[1] == 'ldelim') return '{';
            if($m[1] == 'rdelim') return '}';
            if ($m[1] != 'include') {
                return '<?php '.$this->_parseFunction($m[1], $m[2]).'?>';
            } else {
                return $this->_parseFunction($m[1], $m[2]);
            }
        }
    }

    private function _parseVariable($expr)
    {
        $tok = explode('|', $expr);
        $res = $this->_parseFinal(array_shift($tok), self::$allowedInVar);
        foreach ($tok as $modifier) {
            if (!preg_match('/^(\w+)(?:\:(.*))?$/', $modifier, $m)) {
                trigger_error(sprintf(__('Invalid modifier syntax: (%s) %s'), $expr, $modifier), E_USER_ERROR);
                return '';
            }
            $targs = array($res);
            if (isset($m[2])) {
                $res = $this->_modifier[$m[1]].'('.$res.','.$m[2].')';
            } elseif (isset($this->_modifier[$m[1]])) {
                $res = $this->_modifier[$m[1]].'('.$res.')';
            } else {
                trigger_error(sprintf(__('Unknown modifier: (%s) %s'), $expr, $m[1]), E_USER_ERROR);
                return '';
            }
            if (!in_array($this->_modifier[$m[1]], $this->_usedModifiers)) {
                $this->_usedModifiers[] = $this->_modifier[$m[1]];
            }
        }
        return $res;
    }

    private function _parseFunction($name, $args)
    {
        switch ($name) {
        case 'if':
            $res = 'if ('.$this->_parseFinal($args, self::$allowedInExpr).'): ';
            $this->_blockStack[] = 'if';
            break;
        case 'else':
            if (end($this->_blockStack) != 'if') {
                trigger_error(sprintf(__('End tag of a block missing: %s'), end($this->_blockStack)), E_USER_ERROR);
            }
            $res = 'else: ';
            break;
        case 'elseif':
            if (end($this->_blockStack) != 'if') {
                trigger_error(sprintf(__('End tag of a block missing: %s'), end($this->_blockStack)), E_USER_ERROR);
            }
            $res = 'elseif('.$this->_parseFinal($args, self::$allowedInExpr).'):';
            break;
        case 'foreach':
            $tokens = $this->tokenize($args, self::$allowedForeach, array(';'));
            $asFound = false;
            $scopeVars = array('foreach_counter0', 'foreach_counter', 'foreach_first');
            foreach ($tokens as $token) {
                if (!is_array($token))
                    continue;
                if ($asFound) {
                    if ($token[0] == T_VARIABLE)
                        $scopeVars[] = substr($token[1], 1);
                } else {
                    if ($token[0] == T_AS)
                        $asFound = true;
                }
            }
            $res =
                '$t->push(\'' . implode('\', \'', $scopeVars) . '\'); ' .
                '$t->_vars[\'foreach_counter0\'] = 0;' .
                '$t->_vars[\'foreach_counter\'] = 1;' .
                '$t->_vars[\'foreach_first\'] = true;' .
                'foreach ('.$this->compileExpression($tokens).'): ';
            $this->_blockStack[] = 'foreach';
            break;
        case 'while':
            $res = 'while('.$this->_parseFinal($args, self::$allowedInExpr).'):';
            $this->_blockStack[] = 'while';
            break;
        case '/foreach':
            if(end($this->_blockStack) != 'foreach'){
                trigger_error(sprintf(__('End tag of a block missing: %s'), end($this->_blockStack)), E_USER_ERROR);
            }
            array_pop($this->_blockStack);
            $res =
                '$t->_vars[\'foreach_counter0\'] = $t->_vars[\'foreach_counter0\'] + 1;' .
                '$t->_vars[\'foreach_counter\'] = $t->_vars[\'foreach_counter\'] + 1;' .
                '$t->_vars[\'foreach_first\'] = false;' .
                'endforeach; ' .
                '$t->pop(); ';
            break;
        case '/if':
        case '/while':
            $short = substr($name,1);
            if(end($this->_blockStack) != $short){
                trigger_error(sprintf(__('End tag of a block missing: %s'), end($this->_blockStack)), E_USER_ERROR);
            }
            array_pop($this->_blockStack);
            $res = 'end'.$short.'; ';
            break;
        case 'assign':
            $res = $this->_parseFinal($args, self::$allowedAssign).'; ';
            break;
        case 'literal':
            if (count($this->_literals)) {
                $res = '?>'.array_shift($this->_literals).'<?php ';
            } else {
                trigger_error(__('End tag of a block missing: literal'), E_USER_ERROR);
            }
            break;
        case '/literal':
            trigger_error(__('Start tag of a block missing: literal'), E_USER_ERROR);
            break;
        case 'block':
            if (isset($this->_extendBlocks[$args]))
                $res = '?>'.$this->_extendBlocks[$args].'<?php ';
            else
                $res = '';
            break;
        case 'superblock':
            $res = '?>~~{~~superblock~~}~~<?php ';
            break;
        case 'trans':
            $argfct = $this->_parseFinal($args, self::$allowedAssign);
            $res = 'echo(__('.$argfct.'));';
            break;
        case 'blocktrans':
            $this->_blockStack[] = 'blocktrans';
            $res = '';
            $this->_transStack = array();
            if ($args) {
                $this->_transPlural = true;
                $_args = $this->_parseFinal($args, self::$allowedAssign, array(';', '[', ']'));
                $res .= '$_b_t_c='.trim($_args).'; ';
            }
            $res .= 'ob_start(); ';
            break;
        case '/blocktrans':
            $short = substr($name,1);
            if(end($this->_blockStack) != $short){
                trigger_error(sprintf(__('End tag of a block missing: %s'), end($this->_blockStack)), E_USER_ERROR);
            }
            $res = '';
            if ($this->_transPlural) {
                $res .= '$_b_t_p=ob_get_contents(); ob_end_clean(); echo(';
                $res .= 'IPF_Translation::sprintf(_n($_b_t_s, $_b_t_p, $_b_t_c), array(';
                $_tmp = array();
                foreach ($this->_transStack as $key=>$_trans) {
                    $_tmp[] = '\''.addslashes($key).'\' => IPF_Template_SafeString::value('.$_trans.')';
                }
                $res .= implode(', ', $_tmp);
                unset($_trans, $_tmp);
                $res .= ')));';
                $this->_transStack = array();
            } else {
                $res .= '$_b_t_s=ob_get_contents(); ob_end_clean(); ';
                if (count($this->_transStack) == 0) {
                    $res .= 'echo(__($_b_t_s)); ';
                } else {
                    $res .= 'echo(IPF_Translation::sprintf(__($_b_t_s), array(';
                    $_tmp = array();
                    foreach ($this->_transStack as $key=>$_trans) {
                        $_tmp[] = '\''.addslashes($key).'\' => IPF_Template_SafeString::value('.$_trans.')';
                    }
                    $res .= implode(', ', $_tmp);
                    unset($_trans, $_tmp);
                    $res .= '))); ';
                    $this->_transStack = array();
                }
            }
            $this->_transPlural = false;
            array_pop($this->_blockStack);
            break;
        case 'plural':
            $res = '$_b_t_s=ob_get_contents(); ob_end_clean(); ob_start(); ';
            break;
        case 'include':
            $argfct = preg_replace('!^[\'"](.*)[\'"]$!', '$1', $args);
            $includedTemplateContent = $this->environment->loadTemplateFile($argfct);
            $_comp = new IPF_Template_Compiler($includedTemplateContent, $this->environment);
            $res = $_comp->compile();
            $this->updateModifierStack($_comp);
            break;
        default:
            $_start = true;
            $oname = $name;
            if (substr($name, 0, 1) == '/') {
                $_start = false;
                $name = substr($name, 1);
            }
            // Here we should allow custom blocks.

            // Here we start the template tag calls at the template tag
            // {tag ...} is not a block, so it must be a function.
            if (!$this->environment->isTagAllowed($name)) {
                trigger_error(sprintf(__('The function tag "%s" is not allowed.'), $name), E_USER_ERROR);
            }
            // $argfct is a string that can be copy/pasted in the PHP code
            // but we need the array of args.
            $argfct = $this->_parseFinal($args, self::$allowedAssign);

            $res = '$_extra_tag = new '.$this->environment->allowedTags[$name].'($t);';
            if ($_start) {
                $res .= '$_extra_tag->start('.$argfct.'); ';
            } else {
                $res .= '$_extra_tag->end('.$argfct.'); ';
            }
        }
        return $res;
    }

    private function _parseFinal($string, &$allowed, $exceptchar=array(';'))
    {
        $tokens = $this->tokenize($string, $allowed, $exceptchar);
        return $this->compileExpression($tokens);
    }

    private function tokenize($string, $allowed, $exceptchar)
    {
        $tokens = token_get_all('<?php '.$string.'?>');
        $result = array();
        foreach ($tokens as $tok) {
            if (is_array($tok)) {
                list($type, $str) = $tok;
                if ($type == T_OPEN_TAG || $type == T_CLOSE_TAG) {
                    // skip
                } elseif (in_array($type, $allowed)) {
                    $result[] = $tok;
                } else {
                    throw new IPF_Exception_Template(sprintf(__('Invalid syntax: (%s) %s.'), $string, $str));
                }
            } else {
                if (in_array($tok, $exceptchar)) {
                    trigger_error(sprintf(__('Invalid character: (%s) %s.'), $string, $tok), E_USER_ERROR);
                } else {
                    $result[] = $tok;
                }
            }
        }
        return $result;
    }

    private function compileExpression($tokens)
    {
        $result = '';
        foreach ($tokens as $tok) {
            if (is_array($tok)) {
                list($type, $str) = $tok;
                if ($type == T_VARIABLE) {
                    $result .= '$t->_vars[\''.substr($str, 1).'\']';
                } else {
                    $result .= $str;
                }
            } else {
                if ($tok == '.') {
                    $result .= '->';
                } elseif ($tok == '~') {
                    $result .= '.';
                } else {
                    $result .= $tok;
                }
            }
        }
        return $result;
    }

    protected function updateModifierStack($compiler)
    {
        foreach ($compiler->_usedModifiers as $_um) {
            if (!in_array($_um, $this->_usedModifiers)) {
                $this->_usedModifiers[] = $_um;
            }
        }
    }
}

IPF_Template_Compiler::init();

