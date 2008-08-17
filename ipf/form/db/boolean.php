<?php

class IPF_Form_DB_Boolean extends IPF_Form_DB{
    public $type = 'boolean';
    function formField($def, $form_field='IPF_Form_Field_Boolean'){
        return parent::formField($def, $form_field);
    }
}
