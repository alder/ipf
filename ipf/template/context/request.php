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
        foreach (IPF_Project::getInstance()->appList() as $app) {
            $vars = array_merge($app->templateContext($request), $vars);
        }
        parent::__construct($vars);
    }
}

