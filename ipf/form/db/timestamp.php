<?php

class IPF_Form_DB_Timestamp extends IPF_Form_DB{
    public $type = 'datetime';
    function formField($def, $form_field='IPF_Form_Field_Datetime'){
        return parent::formField($def, $form_field);
    }
}
