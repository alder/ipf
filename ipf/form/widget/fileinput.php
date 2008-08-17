<?php

class IPF_Form_Widget_FileInput extends IPF_Form_Widget_Input
{
    public $input_type = 'file';
    public $needs_multipart_form = true;

    public function render($name, $value, $extra_attrs=array())
    {
        $value = '';
        return parent::render($name, $value, $extra_attrs);
    }

}