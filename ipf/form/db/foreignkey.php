<?php

class IPF_Form_DB_Foreignkey extends IPF_Form_DB
{
    function formField($def, $form_field='IPF_Form_Field_ModelChoice'){
        $gmodel = new $def['model']();
        $def['queryset'] = $gmodel->getTable()->findAll();
        $def['model'] = $gmodel;
        $def['required'] = true;
        return parent::formField($def, $form_field);
    }
}
