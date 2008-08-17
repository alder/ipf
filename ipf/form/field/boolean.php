<?php

class IPF_Form_Field_Boolean extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_CheckboxInput';

    public function clean($value)
    {
        if (in_array($value, array('on', 'y', '1', 1, true))) {
            return true;
        }
        return false;
    }
}
