<?php

class IPF_Form_DB_File extends IPF_Form_DB
{
    function formField($def, $form_field='IPF_Form_Field_File')
    {
        $field = parent::formField($def, $form_field);
        $field->uploadTo = @$this->extra['uploadTo'];
        return $field;
    }
}

