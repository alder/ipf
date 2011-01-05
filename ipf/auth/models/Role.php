<?php

class Role extends BaseRole
{
    public function __toString()
    {
        return $this->name;
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
        $extra['checkgroup_fields'] = array(
            'Permissions' => array('widget'=>'IPF_Auth_Forms_Widget_Permissions'),
        );
        return new IPF_Form_Extra_CheckGroup($data, $extra);
    }
    
    protected function _setupForm($form)
    {
        IPF_Form_Extra_CheckGroup::SetupForm($form);
    }

    public function page_title()   { return 'Group'; }
    public function verbose_name() { return 'Group'; }
}

IPF_Admin_Model::register('Role','AdminRole');
