<?php

class IPF_Form_Widget_CheckboxInput extends IPF_Form_Widget_Input
{
    public $input_type = 'checkbox';

    public function render($name, $value, $extra_attrs=array())
    {
        if ($value)
            $extra_attrs['checked'] = 'checked';
        if (!array_key_exists('value', $extra_attrs))
            $extra_attrs['value'] = '1';
        return parent::render($name, '', $extra_attrs);
    }

    public function valueFromFormData($name, &$data)
    {
        return isset($data[$name]) && false !== $data[$name] && (string)$data[$name] !== '0' && (string)$data[$name] !== 'off';
    }
}

