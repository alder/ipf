<?php

class IPF_Form_Widget_SelectMultipleInput extends IPF_Form_Widget
{
    public $choices = array();

    public function __construct($attrs=array())
    {
        $this->choices = $attrs['choices'];
        unset($attrs['choices']);
        parent::__construct($attrs);
    }

    public function render($name, $value, $extra_attrs=array(),
                           $choices=array())
    {
        $output = array();
        if ($value === null) {
            $value = array();
        }
        $final_attrs = $this->buildAttrs(array('name' => $name.'[]'),
                                         $extra_attrs);
        $output[] = '<select multiple="multiple"'
            .IPF_Form_Widget_Attrs($final_attrs).'>';
        $choices = array_merge($this->choices, $choices);

        foreach ($choices as $option_label=>$option_value) {
            $selected = (in_array($option_value, $value)) ? ' selected="selected"':'';
            $output[] = sprintf('<option value="%s"%s>%s</option>',
                                htmlspecialchars($option_value, ENT_COMPAT, 'UTF-8'),
                                $selected,
                                htmlspecialchars($option_label, ENT_COMPAT, 'UTF-8'));

        }
        $output[] = '</select>';
        return new IPF_Template_SafeString(implode("\n", $output), true);
    }

    public function valueFromFormData($name, $data)
    {
        if (isset($data[$name]) and is_array($data[$name])) {
            return $data[$name];
        }
        return null;
    }

}