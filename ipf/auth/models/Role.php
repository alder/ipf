<?php

class Role extends BaseRole
{
    public function __toString()
    {
        return $this->name;
    }
}

class IPFAdminRoleForm extends IPF_Form_Model
{
    public function add__Permissions__field()
    {
        if (!IPF_Auth_App::ArePermissionsEnabled())
            return;

        $choices = array();
        foreach (IPF_ORM::getTable('Permission')->findAll() as $o)
            $choices[$o->__toString()] = $o->id;
        ksort($choices);

        $field = new IPF_Form_Field_ModelMultipleChoice(array(
            'required' => false,
            'label' => 'Permissions',
            'help_text' => '',
            'type' => 'manytomany',
            'editable' => true,
            'model' => 'Permission',
            'widget' => 'IPF_Form_Widget_SelectMultipleInputCheckbox',
            'choices' => $choices,
            'widget_attrs' => array('class' => 'checkgroup'),
        ));

        $this->fields['Permissions'] = $field;
    }
}

class AdminRole extends IPF_Admin_Model
{
    public function list_display()
    {
        return array(
            'name',
        );
    }
    
    public function fields()
    {
        return array(
            'name',
            'Permissions',
        );
    }

    function _searchFields()
    {
        return array(
            'name',
        );
    }

    protected function _getForm($model_obj, $data, $extra)
    {
        $extra['model'] = $model_obj;
        return new IPFAdminRoleForm($data, $extra);
    }

    public function page_title()   { return 'Group'; }
    public function verbose_name() { return 'Group'; }
}

IPF_Admin_Model::register('Role', 'AdminRole');

