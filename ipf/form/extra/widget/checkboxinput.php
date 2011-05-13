<?php

class IPF_Form_Extra_Widget_CheckboxInput extends IPF_Form_Widget_Input
{
    public $input_type = 'checkbox';

    public function render($name, $checked, $extra_attrs=array())
    {
        if ($checked)
            $extra_attrs['checked'] = 'checked';
        return parent::render($name, '', $extra_attrs);
    }

    public function valueFromFormData($name, &$data)
    {
        return (!isset($data[$name]) || false === $data[$name] || (string)$data[$name] === '0' || (string)$data[$name] === 'off') ? false : true;
    }
}
