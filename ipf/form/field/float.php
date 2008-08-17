<?php

class IPF_Form_Field_Float extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_TextInput';
    public $max_value = null;
    public $min_value = null;

    public function clean($value)
    {
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            $value = '';
        }
        $_value = $value;
        $value = (float) $value;
        if ((string) $value !== (string) $_value) {
            throw new IPF_Exception_Form(__('Enter a number.'));
        }
        if ($this->max_value !== null and $this->max_value < $value) {
            throw new IPF_Exception_Form(sprintf(__('Ensure this value is less than or equal to %s.'), $this->max_value));
        }
        if ($this->min_value !== null and $this->min_value > $value) {
            throw new IPF_Exception_Form(sprintf(__('Ensure this value is greater than or equal to %s.'), $this->min_value));
        }
        return $value;
    }
}
