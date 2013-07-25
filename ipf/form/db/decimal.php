<?php

class IPF_Form_DB_Decimal extends IPF_Form_DB
{
    function formField($def, $form_field='IPF_Form_Field_Float')
    {
        $def['widget_attrs'] = array('style' => 'width:140px;');
        return parent::formField($def, $form_field);
    }
}

