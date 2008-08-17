<?php

class IPF_Form_FieldProxy
{
    protected $form = null;

    public function __construct(&$form)
    {
        $this->form = $form;
    }

    public function __get($field)
    {
        return new IPF_Form_BoundField($this->form, $this->form->fields[$field], $field);
    }
}
