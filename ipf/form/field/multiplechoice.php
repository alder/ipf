<?php

class IPF_Form_Field_MultipleChoice extends IPF_Form_Field_Choice{
    public $widget = 'IPF_Form_Widget_SelectMultipleInput';

    public function validValue($value){
        foreach($value as $v){
            $find = false;
            foreach($this->_choices as $name=>$val){
                if ($v==$val){
                    $find = true;
                    break;
                }
            }
            if (!$find)
                return false;
        }
        return true;
    }

    public function clean($value){
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            return '';
        }
        if (!$this->validValue($value))
            throw new IPF_Exception_Form(__('Invalid choice'));
        return $value;
    }

}

