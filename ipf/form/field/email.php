<?php

class IPF_Form_Field_Email extends IPF_Form_Field{
    public $widget = 'IPF_Form_Widget_TextInput';
    public function clean($value){
        parent::clean($value);
        if (in_array($value, $this->empty_values))
            return '';

        if (!IPF_Utils::isEmail($value)) {
            throw new IPF_Exception_Form(__('Enter a valid email address.'));
        }
        return $value;
    }
}
