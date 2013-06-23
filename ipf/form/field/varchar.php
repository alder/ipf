<?php

class IPF_Form_Field_Varchar extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_TextInput';
    public $max_length = null;
    public $min_length = null;

    public function clean($value)
    {
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            $value = '';
        }
        $value_length = mb_strlen($value);
        if ($this->max_length !== null and $value_length > $this->max_length) {
            throw new IPF_Exception_Form(sprintf(__('Ensure this value has at most %1$d characters (it has %2$d).'), $this->max_length, $value_length));
        }
        if ($this->min_length !== null and $value_length < $this->min_length) {
            throw new IPF_Exception_Form(sprintf(__('Ensure this value has at least %1$d characters (it has %2$d).'), $this->min_length, $value_length));
        }
        return $value;
    }

    public function widgetAttrs($widget)
    {
        if ($this->max_length !== null and in_array(get_class($widget), array('IPF_Form_Widget_TextInput', 'IPF_Form_Widget_PasswordInput'))) {
            return array('maxlength'=>$this->max_length);
        }
        return array();
    }
    
    protected function getWidget()
    {
        if ($this->max_length>255)
            return 'IPF_Form_Widget_TextareaInput';
        return $this->widget;
    }
}

