<?php

class IPF_Template_Tag_Url extends IPF_Template_Tag
{
    function start($view, $params=array(), $by_name=false, $get_params=array())
    {
        echo IPF_HTTP_URL::urlForView($view, $params, $by_name, $get_params);
    }
}

