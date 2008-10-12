<?php

class IPF_Form_Field_ModelChoice extends IPF_Form_Field_Choice{

    protected $_model;

    function __construct($params=array()){
        parent::__construct($params);
        $this->_model = $params['model'];
        if (isset($params['queryset'])){
            $choices = array('--------'=>'');
            foreach ($params['queryset'] as $item) {
                $choices[(string)$item] = $item->id;
            }
            $this->setChoices($choices);
        }
    }

    public function clean($value){
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            return null;
        }
        $o = $this->_model->getTable()->find($value);
        return $o;
    }
}
