<?php

class IPF_Form_Field_Url extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_TextInput';

    public function clean($value)
    {
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            return '';
        }
        if (!IPF_Utils::isValidUrl($value)) {
            throw new IPF_Exception_Form(__('Enter a valid address.'));
        }
        return $value;
    }
}
