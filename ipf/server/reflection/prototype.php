<?php

class IPF_Server_Reflection_Prototype
{
    public function __construct(IPF_Server_Reflection_ReturnValue $return, $params = null)
    {
        $this->_return = $return;

        if (!is_array($params) && (null !== $params)) {
            throw new IPF_Exception('Invalid parameters');
        }

        if (is_array($params)) {
            foreach ($params as $param) {
                if (!$param instanceof IPF_Server_Reflection_Parameter) {
                    throw new IPF_Exception('One or more params are invalid');
                }
            }
        }

        $this->_params = $params;
    }

    public function getReturnType()
    {
        return $this->_return->getType();
    }

    public function getReturnValue()
    {
        return $this->_return;
    }

    public function getParameters()
    {
        return $this->_params;
    }
}
