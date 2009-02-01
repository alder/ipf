<?php

class IPF_Form_Widget_CheckboxInput extends IPF_Form_Widget_Input
{
    public $input_type = 'checkbox';

    public function render($name, $value, $extra_attrs=array())
    {
        if ((bool)$value) {
            $extra_attrs['checked'] = 'checked';
        }
        $extra_attrs['value'] = '1';
        return parent::render($name, '', $extra_attrs);
    }

    public function valueFromFormData($name, $data)
    {
        if (!isset($data[$name]) or false === $data[$name] or (string)$data[$name] === '0' or (string)$data[$name] === 'off') {
            return false;
        }
        return true;
    }
}