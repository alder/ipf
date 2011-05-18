<?php

class IPF_Form_Widget_DateInput extends IPF_Form_Widget_Input
{
    public $input_type = 'text';
    public $format = 'Y-m-d';

    public function render($name, $value, $extra_attrs=array())
    {
        if (strlen($value) > 0) {
            $value = date($this->format, strtotime($value));
        }
        $extra_attrs['class'] = 'dateinput';
        return parent::render($name, $value, $extra_attrs);
    }
}