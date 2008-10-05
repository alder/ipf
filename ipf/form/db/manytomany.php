<?php

class IPF_Form_DB_Manytomany extends IPF_Form_DB
{
    public $type = 'manytomany';

    function formField($def, $form_field='IPF_Form_Field_ModelMultipleChoice')
    {
        //print_r($def);
        $list_objects = IPF_ORM::getTable($def['model'])->findAll();
        $choices = array();
        foreach($list_objects as $o){
            $choices[$o->__toString()] = $o->id;
        }
        $def['choices'] = $choices;
        if (!isset($def['widget'])) {
            $def['widget'] = 'IPF_Form_Widget_SelectMultipleInput';
        }
        return parent::formField($def, $form_field);
    }
}
