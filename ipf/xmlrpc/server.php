<?php

class IPF_XmlRpc_Server
{
    protected $_encoding = 'UTF-8';
    protected $_methods = array();
    protected $_request = null;
    protected $_responseClass = 'IPF_XmlRpc_Response_Http';

    protected $_table = array();

    protected $_typeMap = array(
        'i4'               => 'i4',
        'int'              => 'int',
        'integer'          => 'int',
        'double'           => 'double',
        'float'            => 'double',
        'real'             => 'double',
        'boolean'          => 'boolean',
        'bool'             => 'boolean',
        'true'             => 'boolean',
        'false'            => 'boolean',
        'string'           => 'string',
        'str'              => 'string',
        'base64'           => 'base64',
        'dateTime.iso8601' => 'dateTime.iso8601',
        'date'             => 'dateTime.iso8601',
        'time'             => 'dateTime.iso8601',
        'time'             => 'dateTime.iso8601',
        'array'            => 'array',
        'struct'           => 'struct',
        'null'             => 'nil',
        'nil'              => 'nil',
        'void'             => 'void',
        'mixed'            => 'struct'
    );

    public function __construct()
    {
        // Setup system.* methods
        $system = array(
            'listMethods',
            'methodHelp',
            'methodSignature',
            'multicall'
        );

        $class = IPF_Server_Reflection::reflectClass($this);
        foreach ($system as $method) {
            $reflection = new IPF_Server_Reflection_Method($class, new ReflectionMethod($this, $method), 'system');
            $reflection->system = true;
            $this->_methods[] = $reflection;
        }

        $this->_buildDispatchTable();
    }

    protected function _fixTypes(IPF_Server_Reflection_Function_Abstract $method)
    {
        foreach ($method->getPrototypes() as $prototype) {
            foreach ($prototype->getParameters() as $param) {
                $pType = $param->getType();
                if (isset($this->_typeMap[$pType])) {
                    $param->setType($this->_typeMap[$pType]);
                } else {
                    $param->setType('void');
                }
            }
        }
    }

    protected function _buildDispatchTable()
    {
        $table      = array();
        foreach ($this->_methods as $dispatchable) {
            if ($dispatchable instanceof IPF_Server_Reflection_Function_Abstract) {
                // function/method call
                $ns   = $dispatchable->getNamespace();
                $name = $dispatchable->getName();
                $name = empty($ns) ? $name : $ns . '.' . $name;

                if (isset($table[$name])) {
                    throw new IPF_Exception('Duplicate method registered: ' . $name);
                }
                $table[$name] = $dispatchable;
                $this->_fixTypes($dispatchable);

                continue;
            }

            if ($dispatchable instanceof IPF_Server_Reflection_Class) {
                foreach ($dispatchable->getMethods() as $method) {
                    $ns   = $method->getNamespace();
                    $name = $method->getName();
                    $name = empty($ns) ? $name : $ns . '.' . $name;

                    if (isset($table[$name])) {
                        throw new IPF_Exception('Duplicate method registered: ' . $name);
                    }
                    $table[$name] = $method;
                    $this->_fixTypes($method);
                    continue;
                }
            }
        }

        $this->_table = $table;
    }

    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    public function addFunction($function, $namespace = '')
    {
        if (!is_string($function) && !is_array($function)) {
            throw new IPF_Exception('Unable to attach function; invalid', 611);
        }

        $argv = null;
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $function = (array) $function;
        foreach ($function as $func) {
            if (!is_string($func) || !function_exists($func)) {
                throw new IPF_Exception('Unable to attach function; invalid', 611);
            }
            $this->_methods[] = IPF_Server_Reflection::reflectFunction($func, $argv, $namespace);
        }

        $this->_buildDispatchTable();
    }

    public function loadFunctions($array)
    {
        if (!is_array($array)) {
            throw new IPF_Exception('Unable to load array; not an array', 612);
        }

        foreach ($array as $key => $value) {
            if (!$value instanceof IPF_Server_Reflection_Function_Abstract
                && !$value instanceof IPF_Server_Reflection_Class)
            {
                throw new IPF_Exception('One or more method records are corrupt or otherwise unusable', 613);
            }

            if ($value->system) {
                unset($array[$key]);
            }
        }

        foreach ($array as $dispatchable) {
            $this->_methods[] = $dispatchable;
        }

        $this->_buildDispatchTable();
    }

    public function setPersistence($class = null)
    {
    }

    public function setClass($class, $namespace = '', $argv = null)
    {
        if (is_string($class) && !class_exists($class)) {
            if (!class_exists($class)) {
                throw new IPF_Exception('Invalid method class', 610);
            }
        }

        $argv = null;
        if (3 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 3);
        }

        $this->_methods[] = IPF_Reflection::reflectClass($class, $argv, $namespace);
        $this->_buildDispatchTable();
    }

    public function setRequest($request)
    {
        if (is_string($request) && class_exists($request)) {
            $request = new $request();
            if (!$request instanceof IPF_XmlRpc_Request) {
                throw new IPF_Exception('Invalid request class');
            }
            $request->setEncoding($this->getEncoding());
        } elseif (!$request instanceof IPF_XmlRpc_Request) {
            throw new IPF_Exception('Invalid request object');
        }

        $this->_request = $request;
        return $this;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function fault($fault, $code = 404)
    {
        if (!$fault instanceof Exception) {
            $fault = (string) $fault;
            $fault = new IPF_Exception($fault, $code);
        }
        return IPF_XmlRpc_Server_Fault::getInstance($fault);
    }

    protected function _handle(IPF_XmlRpc_Request $request)
    {
        $method = $request->getMethod();

        // Check for valid method
        if (!isset($this->_table[$method])) {
            throw new IPF_Exception('Method "' . $method . '" does not exist', 620);
        }

        $info     = $this->_table[$method];
        $params   = $request->getParams();
        $argv     = $info->getInvokeArguments();
        if (0 < count($argv)) {
            $params = array_merge($params, $argv);
        }

        // Check calling parameters against signatures
        $matched    = false;
        $sigCalled  = $request->getTypes();

        $sigLength  = count($sigCalled);
        $paramsLen  = count($params);
        if ($sigLength < $paramsLen) {
            for ($i = $sigLength; $i < $paramsLen; ++$i) {
                $xmlRpcValue = IPF_XmlRpc_Value::getXmlRpcValue($params[$i]);
                $sigCalled[] = $xmlRpcValue->getType();
            }
        }

        $signatures = $info->getPrototypes();
        foreach ($signatures as $signature) {
            $sigParams = $signature->getParameters();
            $tmpParams = array();
            foreach ($sigParams as $param) {
                $tmpParams[] = $param->getType();
            }
            if ($sigCalled === $tmpParams) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            throw new IPF_Exception('Calling parameters do not match signature', 623);
        }

        if ($info instanceof IPF_Server_Reflection_Function) {
            $func = $info->getName();
            $return = call_user_func_array($func, $params);
        } elseif (($info instanceof IPF_Server_Reflection_Method) && $info->system) {
            // System methods
            $return = $info->invokeArgs($this, $params);
        } elseif ($info instanceof IPF_Server_Reflection_Method) {
            // Get class
            $class = $info->getDeclaringClass()->getName();

            if ('static' == $info->isStatic()) {
                // for some reason, invokeArgs() does not work the same as
                // invoke(), and expects the first argument to be an object.
                // So, using a callback if the method is static.
                $return = call_user_func_array(array($class, $info->getName()), $params);
            } else {
                // Object methods
                try {
                    $object = $info->getDeclaringClass()->newInstance();
                } catch (Exception $e) {
                    throw new IPF_Exception('Error instantiating class ' . $class . ' to invoke method ' . $info->getName(), 621);
                }

                $return = $info->invokeArgs($object, $params);
            }
        } else {
            throw new IPF_Exception('Method missing implementation ' . get_class($info), 622);
        }

        $response = new ReflectionClass($this->_responseClass);
        return $response->newInstance($return);
    }

    public function handle(IPF_XmlRpc_Request $request = null)
    {
        // Get request
        if ((null === $request) && (null === ($request = $this->getRequest()))) {
            $request = new IPF_XmlRpc_Request_Http();
            $request->setEncoding($this->getEncoding());
        }

        $this->setRequest($request);

        if ($request->isFault()) {
            $response = $request->getFault();
        } else {
            try {
                $response = $this->_handle($request);
            } catch (Exception $e) {
                $response = $this->fault($e);
            }
        }

        // Set output encoding
        $response->setEncoding($this->getEncoding());

        return $response;
    }

    public function setResponseClass($class)
    {
        if (class_exists($class)) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isSubclassOf(new ReflectionClass('IPF_XmlRpc_Response'))) {
                $this->_responseClass = $class;
                return true;
            }
        }

        return false;
    }

    public function getFunctions()
    {
        $return = array();
        foreach ($this->_methods as $method) {
            if ($method instanceof IPF_Server_Reflection_Class
                && ($method->system))
            {
                continue;
            }

            $return[] = $method;
        }

        return $return;
    }

    public function listMethods()
    {
        return array_keys($this->_table);
    }

    public function methodHelp($method)
    {
        if (!isset($this->_table[$method])) {
            throw new IPF_Exception('Method "' . $method . '"does not exist', 640);
        }

        return $this->_table[$method]->getDescription();
    }

    public function methodSignature($method)
    {
        if (!isset($this->_table[$method])) {
            throw new IPF_Exception('Method "' . $method . '"does not exist', 640);
        }
        $prototypes = $this->_table[$method]->getPrototypes();

        $signatures = array();
        foreach ($prototypes as $prototype) {
            $signature = array($prototype->getReturnType());
            foreach ($prototype->getParameters() as $parameter) {
                $signature[] = $parameter->getType();
            }
            $signatures[] = $signature;
        }

        return $signatures;
    }

    public function multicall($methods)
    {
        $responses = array();
        foreach ($methods as $method) {
            $fault = false;
            if (!is_array($method)) {
                $fault = $this->fault('system.multicall expects each method to be a struct', 601);
            } elseif (!isset($method['methodName'])) {
                $fault = $this->fault('Missing methodName', 602);
            } elseif (!isset($method['params'])) {
                $fault = $this->fault('Missing params', 603);
            } elseif (!is_array($method['params'])) {
                $fault = $this->fault('Params must be an array', 604);
            } else {
                if ('system.multicall' == $method['methodName']) {
                    // don't allow recursive calls to multicall
                    $fault = $this->fault('Recursive system.multicall forbidden', 605);
                }
            }

            if (!$fault) {
                try {
                    $request = new IPF_XmlRpc_Request();
                    $request->setMethod($method['methodName']);
                    $request->setParams($method['params']);
                    $response = $this->_handle($request);
                    $responses[] = $response->getReturnValue();
                } catch (Exception $e) {
                    $fault = $this->fault($e);
                }
            }

            if ($fault) {
                $responses[] = array(
                    'faultCode'   => $fault->getCode(),
                    'faultString' => $fault->getMessage()
                );
            }
        }
        return $responses;
    }
}
