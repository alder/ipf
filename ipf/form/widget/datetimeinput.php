<?php

class IPF_Form_Widget_DatetimeInput extends IPF_Form_Widget_Input
{
    public $input_type = 'text';
    public $format = 'Y-m-d H:i'; 

    public function render($name, $value, $extra_attrs=array())
    {
        // Internally we use GMT, so we convert back to the current
        // timezone.
        if (strlen($value) > 0) {
            $value = date($this->format, strtotime($value.' GMT'));
        }
        $extra_attrs['class'] = 'datetimeinput';
        return parent::render($name, $value, $extra_attrs);
    }
}
