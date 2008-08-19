<?php

class IPF_Form_DB
{
    public $type = '';
    public $column = '';
    public $value;
    public $extra = array();
    public $methods = array();

    function __construct($value='', $column='', $extra=array())
    {
        $this->value = $value;
        $this->column = $column;
        $this->extra = array_merge($this->extra, $extra);
    }

    function formField($def, $form_field='IPF_Form_Field_Varchar')
    {
        $defaults = array(
            'required' => !$def['blank'], 
            'label' => IPF_Utils::humanTitle($def['verbose']), 
            'help_text' => $def['help_text'],
            'type'=>$this->type,
        );
                          
        unset($def['blank'], $def['verbose'], $def['help_text']);
        if (isset($def['default'])) {
            $defaults['initial'] = $def['default'];
            unset($def['default']);
        }
        /*
        if (isset($def['choices'])) {
            $defaults['widget'] = 'IPF_Form_Widget_SelectInput';
            if (isset($def['widget_attrs'])) {
                $def['widget_attrs']['choices'] = $def['choices'];
            } else {
                $def['widget_attrs'] = array('choices' => $def['choices']);
            }
        }
        foreach (array_keys($def) as $key) {
            if (!in_array($key, array('max_length','widget', 'label', 'required', 
                                      'initial', 'choices', 'widget_attrs'))) {
                unset($def[$key]);
            }
        }
        */
        $params = array_merge($defaults, $def);
        return new $form_field($params);
    }
}

