<?php

class IPF_Form_Field_ModelMultipleChoice extends IPF_Form_Field_MultipleChoice{
    public $widget = 'IPF_Form_Widget_SelectMultipleInput';
    protected $_model;

    function __construct($params=array()){
        parent::__construct($params);
        $this->_model = $params['model'];
    }
}

