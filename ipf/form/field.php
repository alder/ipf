<?php

class IPF_Form_Field
{
    public $class = 'IPF_Form_Field';

    public $widget = 'IPF_Form_Widget_TextInput';
    public $label = '';
    public $required = false;
    public $help_text = '';
    public $initial = '';
    public $choices = null;

    public $hidden_widget = 'IPF_Form_Widget_HiddenInput';
    public $value = ''; /**< Current value of the field. */
    protected $empty_values = array('', null, array());

    function __construct($params=array())
    {
        $default = array();
        foreach ($params as $key=>$in) {
            if ($key !== 'widget_attrs')
                if (isset($this->$key))
                    $default[$key] = $this->$key;
        }
        $m = array_merge($default, $params);
        foreach ($params as $key=>$in) {
            if ($key !== 'widget_attrs')
                $this->$key = $m[$key];
        }
        $widget_name = $this->getWidget();
        if (isset($params['widget_attrs'])) {
            $attrs = $params['widget_attrs'];
        } else {
            $attrs = array();
        }
        $widget = new $widget_name($attrs);
        $attrs = $this->widgetAttrs($widget);
        if (count($attrs)) {
            $widget->attrs = array_merge($widget->attrs, $attrs);
        }
        $this->widget = $widget;
    }

    public function clean($value)
    {
        if ($this->required and in_array($value, $this->empty_values)) {
            throw new IPF_Exception_Form(__('This field is required.'));
        }
        return $value;
    }

    function LateClean($data, &$cleaned_data){
    }

    protected function getWidget(){
        return $this->widget;
    }

    public function widgetAttrs($widget)
    {
        return array();
    }
}

