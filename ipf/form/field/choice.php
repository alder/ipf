<?php

class IPF_Form_Field_Choice extends IPF_Form_Field{
    public $widget = 'IPF_Form_Widget_SelectInput';
    protected $_choices = array();

    function __construct($params=array())
    {
        parent::__construct($params);
        if (isset($params['choices']))
            $this->setChoices($params['choices']);
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
    
    public function setChoices($choices){
        $this->_choices = $choices;
        $this->widget->choices = $choices;
    }
    
    public function validValue($value){
        foreach($this->_choices as $name=>$val)
            if ($value==$val)
                return true;
        return false;
    }
}

