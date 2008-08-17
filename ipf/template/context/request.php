<?php

class IPF_Template_Context_Request extends IPF_Template_Context
{
    function __construct($request, $vars=array())
    {
        $vars = array_merge(array('request' => $request), $vars);
        foreach (IPF::get('template_context_processors', array()) as $proc) {
            IPF::loadFunction($proc);
            $vars = array_merge($proc($request), $vars); 
        }
        $params = array('request' => $request,
                        'context' => $vars);

        //IPF_Signal::send('IPF_Template_Context_Request::construct', 
        //                  'IPF_Template_Context_Request', $params);
        $this->_vars = new IPF_Template_ContextVars($params['context']);
    }
}

