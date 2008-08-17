<?php

class IPF_Form_DB_Email extends IPF_Form_DB{
    public $type = 'varchar';
    public $extra = array('size' => 200);

    function formField($def, $form_field='IPF_Form_Field_Email'){
        return parent::formField($def, $form_field);
    }
}
