<?php

class IPF_Form_Widget_PasswordInput extends IPF_Form_Widget_Input
{
    public $input_type = 'password';
    public $render_value = true;

    public function __construct($attrs=array())
    {
        $this->render_value = (isset($attrs['render_value'])) ? $attrs['render_value'] : $this->render_value;
        unset($attrs['render_value']);
        parent::__construct($attrs);
    }

    public function render($name, $value, $extra_attrs=array())
    {
        if ($this->render_value === false) {
            $value = '';
        }
        return parent::render($name, $value, $extra_attrs);
    }
}