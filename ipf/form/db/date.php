<?php

class IPF_Form_DB_Date extends IPF_Form_DB{
    public $type = 'date';
    function formField($def, $form_field='IPF_Form_Field_Date'){
        return parent::formField($def, $form_field);
    }
}
