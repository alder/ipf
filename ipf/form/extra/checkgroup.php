<?php

class IPF_Form_Extra_Checkgroup extends IPF_Form_Extra_AddJs
{
    public $checkgroup_fields = array();

    function initFields($extra=array())
    {
        parent::initFields($extra);

        if (array_key_exists('checkgroup_fields', $extra) && is_array($extra['checkgroup_fields']))
            $this->checkgroup_fields = $extra['checkgroup_fields'];
    }

    static function SetupForm($form, $checkgroup_fields=null)
    {
        if (!$checkgroup_fields)
            $checkgroup_fields = &$form->checkgroup_fields;

        foreach ($checkgroup_fields as $fieldName=>&$params)
        {
    	    $field = $form->fields[$fieldName];
    	    
            if (array_key_exists('label', $params))
                $field->label = $params['label'];
                
            $widget = null;
            if (array_key_exists('widget', $params))
                $widget = $params['widget'];
            if (!$widget)
                $widget = 'IPF_Form_Extra_Widget_CheckboxGroupInput';

            $field->widget = new $widget(array(
                'choices' => $field->widget->choices,
            ));

            if (array_key_exists('class', $field->widget->attrs) && $field->widget->attrs['class'])
                 $field->widget->attrs['class'] .= ' checkgroup';
            else $field->widget->attrs['class']  = 'checkgroup';
        }
    }

    public function render_commonjs()
    {
        if (count($this->checkgroup_fields))
             $result = '<script language="javascript" type="text/javascript" src="'.IPF::get('admin_media_url').'js/extra/checkall.js"></script>';
        else $result = '';

        return implode('', $this->add_js).$result;
    }
}
