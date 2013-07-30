<?php

class IPF_Template_Tag_Url extends IPF_Template_Tag
{
    function start()
    {
        $args = func_get_args();
        $count = count($args);
        if ($count === 0)
            throw new IPF_Exception('No view specified');

        $view = array_shift($args);

        if ($count === 2 && is_array($args[0])) {
            echo IPF_HTTP_URL::urlForView($view, $args[0]);
        } elseif ($count === 3 && is_array($args[0]) && is_array($args[1])) {
            echo IPF_HTTP_URL::urlForView($view, $args[0], $args[1]);
        } else {
            echo IPF_HTTP_URL::urlForView($view, $args);
        }
    }
}

