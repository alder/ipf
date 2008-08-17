<?php

class IPF_Form_Field_Integer extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_TextInput';
    public $max = null;
    public $min = null;

    public function clean($value)
    {
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            $value = '';
        }
        if (is_array($value)) {
            reset($value);
            while (list($i, $val) = each($value)) {
                if (!preg_match('/[0-9]+/', $val)) {
                    throw new IPF_Exception_Form(__('The value must be an integer.'));
                }
                $this->checkMinMax($val);
                $value[$i] = (int) $val;
            }
            reset($value);
            return $value;
        } else {
            if (!preg_match('/[0-9]+/', $value)) {
                throw new IPF_Exception_Form(__('The value must be an integer.'));
            }
            $this->checkMinMax($value);
        }
        return (int) $value;
    }

    protected function checkMinMax($value)
    {
        if ($this->max !== null and $value > $this->max) {
            throw new IPF_Exception_Form(sprintf(__('Ensure that this value is not greater than %1$d.'), $this->max));
        }
        if ($this->min !== null and $value < $this->min) {
            throw new IPF_Exception_Form(sprintf(__('Ensure that this value is not lower than %1$d.'), $this->min));
        }
    }
}

