<?php

class IPF_Auth_Forms_Widget_Permissions extends IPF_Form_Extra_Widget_CheckboxGroupInput
{
    public function __construct($attrs=array())
    {
        if (isset($attrs['choices']))
            ksort($attrs['choices']);
       
        parent::__construct($attrs);
    }
}

