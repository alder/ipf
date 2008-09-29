<?php

class IPF_Server_Reflection_Function_Abstract
{
    protected $_reflection;
    protected $_argv = array();
    protected $_config = array();
    protected $_class;
    protected $_description = '';
    protected $_namespace;

    protected $_prototypes = array();

    private $_return;
    private $_returnDesc;
    private $_paramDesc;
    private $_sigParams;
    private $_sigParamsDepth;

    public function __construct(Reflector $r, $namespace = null, $argv = array())
    {
        // In PHP 5.1.x, ReflectionMethod extends ReflectionFunction. In 5.2.x,
        // both extend ReflectionFunctionAbstract. So, we can't do normal type
        // hinting in the prototype, but instead need to do some explicit
        // testing here.
        if ((!$r instanceof ReflectionFunction)
            && (!$r instanceof ReflectionMethod)) {
            throw new IPF_Exception('Invalid reflection class');
        }
        $this->_reflection = $r;

        // Determine namespace
        if (null !== $namespace){
            $this->setNamespace($namespace);
        }

        // Determine arguments
        if (is_array($argv)) {
            $this->_argv = $argv;
        }

        // If method call, need to store some info on the class
        if ($r instanceof ReflectionMethod) {
            $this->_class = $r->getDeclaringClass()->getName();
        }

        // Perform some introspection
        $this->_reflect();
    }

    protected function _addTree(IPF_Server_Reflection_Node $parent, $level = 0)
    {
        if ($level >= $this->_sigParamsDepth) {
            return;
        }

        foreach ($this->_sigParams[$level] as $value) {
            $node = new IPF_Server_Reflection_Node($value, $parent);
            if ((null !== $value) && ($this->_sigParamsDepth > $level + 1)) {
                $this->_addTree($node, $level + 1);
            }
        }
    }

    protected function _buildTree()
    {
        $returnTree = array();
        foreach ((array) $this->_return as $value) {
            $node = new IPF_Server_Reflection_Node($value);
            $this->_addTree($node);
            $returnTree[] = $node;
        }

        return $returnTree;
    }

    protected function _buildSignatures($return, $returnDesc, $paramTypes, $paramDesc)
    {
        $this->_return         = $return;
        $this->_returnDesc     = $returnDesc;
        $this->_paramDesc      = $paramDesc;
        $this->_sigParams      = $paramTypes;
        $this->_sigParamsDepth = count($paramTypes);
        $signatureTrees        = $this->_buildTree();
        $signatures            = array();

        $endPoints = array();
        foreach ($signatureTrees as $root) {
            $tmp = $root->getEndPoints();
            if (empty($tmp)) {
                $endPoints = array_merge($endPoints, array($root));
            } else {
                $endPoints = array_merge($endPoints, $tmp);
            }
        }

        foreach ($endPoints as $node) {
            if (!$node instanceof IPF_Server_Reflection_Node) {
                continue;
            }

            $signature = array();
            do {
                array_unshift($signature, $node->getValue());
                $node = $node->getParent();
            } while ($node instanceof IPF_Server_Reflection_Node);

            $signatures[] = $signature;
        }

        // Build prototypes
        $params = $this->_reflection->getParameters();
        foreach ($signatures as $signature) {
            $return = new IPF_Server_Reflection_ReturnValue(array_shift($signature), $this->_returnDesc);
            $tmp    = array();
            foreach ($signature as $key => $type) {
                $param = new IPF_Server_Reflection_Parameter($params[$key], $type, $this->_paramDesc[$key]);
                $param->setPosition($key);
                $tmp[] = $param;
            }

            $this->_prototypes[] = new IPF_Server_Reflection_Prototype($return, $tmp);
        }
    }

    protected function _reflect()
    {
        $function           = $this->_reflection;
        $helpText           = '';
        $signatures         = array();
        $returnDesc         = '';
        $paramCount         = $function->getNumberOfParameters();
        $paramCountRequired = $function->getNumberOfRequiredParameters();
        $parameters         = $function->getParameters();
        $docBlock           = $function->getDocComment();

        if (!empty($docBlock)) {
            // Get help text
            if (preg_match(':/\*\*\s*\r?\n\s*\*\s(.*?)\r?\n\s*\*(\s@|/):s', $docBlock, $matches))
            {
                $helpText = $matches[1];
                $helpText = preg_replace('/(^\s*\*\s)/m', '', $helpText);
                $helpText = preg_replace('/\r?\n\s*\*\s*(\r?\n)*/s', "\n", $helpText);
                $helpText = trim($helpText);
            }

            // Get return type(s) and description
            $return     = 'void';
            if (preg_match('/@return\s+(\S+)/', $docBlock, $matches)) {
                $return = explode('|', $matches[1]);
                if (preg_match('/@return\s+\S+\s+(.*?)(@|\*\/)/s', $docBlock, $matches))
                {
                    $value = $matches[1];
                    $value = preg_replace('/\s?\*\s/m', '', $value);
                    $value = preg_replace('/\s{2,}/', ' ', $value);
                    $returnDesc = trim($value);
                }
            }

            // Get param types and description
            if (preg_match_all('/@param\s+([^\s]+)/m', $docBlock, $matches)) {
                $paramTypesTmp = $matches[1];
                if (preg_match_all('/@param\s+\S+\s+(\$^\S+)\s+(.*?)(@|\*\/)/s', $docBlock, $matches))
                {
                    $paramDesc = $matches[2];
                    foreach ($paramDesc as $key => $value) {
                        $value = preg_replace('/\s?\*\s/m', '', $value);
                        $value = preg_replace('/\s{2,}/', ' ', $value);
                        $paramDesc[$key] = trim($value);
                    }
                }
            }
        } else {
            $helpText = $function->getName();
            $return   = 'void';
        }

        // Set method description
        $this->setDescription($helpText);

        // Get all param types as arrays
        if (!isset($paramTypesTmp) && (0 < $paramCount)) {
            $paramTypesTmp = array_fill(0, $paramCount, 'mixed');
        } elseif (!isset($paramTypesTmp)) {
            $paramTypesTmp = array();
        } elseif (count($paramTypesTmp) < $paramCount) {
            $start = $paramCount - count($paramTypesTmp);
            for ($i = $start; $i < $paramCount; ++$i) {
                $paramTypesTmp[$i] = 'mixed';
            }
        }

        // Get all param descriptions as arrays
        if (!isset($paramDesc) && (0 < $paramCount)) {
            $paramDesc = array_fill(0, $paramCount, '');
        } elseif (!isset($paramDesc)) {
            $paramDesc = array();
        } elseif (count($paramDesc) < $paramCount) {
            $start = $paramCount - count($paramDesc);
            for ($i = $start; $i < $paramCount; ++$i) {
                $paramDesc[$i] = '';
            }
        }


        $paramTypes = array();
        foreach ($paramTypesTmp as $i => $param) {
            $tmp = explode('|', $param);
            if ($parameters[$i]->isOptional()) {
                array_unshift($tmp, null);
            }
            $paramTypes[] = $tmp;
        }

        $this->_buildSignatures($return, $returnDesc, $paramTypes, $paramDesc);
    }

    public function __call($method, $args)
    {
        if (method_exists($this->_reflection, $method)) {
            return call_user_func_array(array($this->_reflection, $method), $args);
        }

        throw new IPF_Exception('Invalid reflection method ("' .$method. '")');
    }

    public function __get($key)
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }

        return null;
    }

    public function __set($key, $value)
    {
        $this->_config[$key] = $value;
    }

    public function setNamespace($namespace)
    {
        if (empty($namespace)) {
            $this->_namespace = '';
            return;
        }

        if (!is_string($namespace) || !preg_match('/[a-z0-9_\.]+/i', $namespace)) {
            throw new IPF_Exception('Invalid namespace');
        }

        $this->_namespace = $namespace;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function setDescription($string)
    {
        if (!is_string($string)) {
            throw new IPF_Exception('Invalid description');
        }

        $this->_description = $string;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getPrototypes()
    {
        return $this->_prototypes;
    }

    public function getInvokeArguments()
    {
        return $this->_argv;
    }

    public function __wakeup()
    {
        if ($this->_reflection instanceof ReflectionMethod) {
            $class = new ReflectionClass($this->_class);
            $this->_reflection = new ReflectionMethod($class->newInstance(), $this->getName());
        } else {
            $this->_reflection = new ReflectionFunction($this->getName());
        }
    }
}
